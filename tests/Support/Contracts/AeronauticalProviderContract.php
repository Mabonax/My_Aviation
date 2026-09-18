<?php

namespace Tests\Support\Contracts;

use App\Domains\Uas\AeronauticalInformation\Application\Actions\GenerateMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\BriefingReadiness;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalInformationProviderInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalSourceRecord;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Reuse with a mocked official transport by implementing these two harness methods. Never use a live production feed in this suite. */
trait AeronauticalProviderContract
{
    abstract protected function provider(): AeronauticalInformationProviderInterface;

    abstract protected function ingest(array $records = [], array $envelope = []): ProviderSync;

    public function test_provider_identity_is_stable_and_matches_capabilities(): void
    {
        $this->assertSame($this->provider()->key(), $this->provider()->capabilities()->providerId);
        $this->assertEquals($this->provider()->capabilities(), $this->provider()->capabilities());
    }

    public function test_authority_cannot_self_elevate_without_server_evidence(): void
    {
        config(['aeronautical.providers.'.$this->provider()->key().'.approval_evidence' => []]);
        $sync = $this->ingest([aimRecord(['usable_for_release' => true, 'source_classification' => 'official_live'])]);
        $this->assertFalse($sync->usable_for_release);
        $this->assertFalse(AeronauticalInformationItem::first()->usable_for_release);
        $this->assertTrue(app(BriefingReadiness::class)->execute(aimMission())['blocking']);
    }

    public function test_malformed_record_is_rejected_atomically(): void
    {
        try {
            $this->ingest([aimRecord(), ['bad' => 'record']]);
            $this->fail('Malformed record accepted');
        } catch (ValidationException) {
        }
        $this->assertSame(0, AeronauticalSourceRecord::count());
        $this->assertSame('failed', ProviderSync::latest('id')->first()->status);
    }

    public function test_unknown_information_type_fails_closed(): void
    {
        $this->expectException(ValidationException::class);
        $this->ingest([aimRecord(['information_type' => 'UNKNOWN_FUTURE_TYPE'])]);
    }

    public function test_checksum_and_duplicate_dataset_are_idempotent(): void
    {
        $record = aimRecord();
        $envelope = ['dataset_timestamp' => now()->toISOString(), 'metadata' => ['dataset_sequence' => 1, 'snapshot_id' => 'contract-snapshot']];
        $first = $this->ingest([$record], $envelope);
        $second = $this->ingest([$record], $envelope);
        $this->assertSame(1, AeronauticalSourceRecord::count());
        $this->assertSame(1, AeronauticalInformationItem::count());
        $this->assertSame($first->id, $second->duplicate_of_id);
        $this->assertEquals($first->completion_revision, $second->completion_revision);
        $this->assertSame(hash('sha256', json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)), AeronauticalSourceRecord::first()->checksum);
    }

    public function test_source_dates_are_normalized_to_utc(): void
    {
        $this->ingest([aimRecord(['issued_at' => '2026-01-01T10:00:00+02:00'])]);
        $this->assertSame('2026-01-01 08:00:00', AeronauticalInformationItem::first()->issued_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_incremental_update_cannot_assert_snapshot_completeness(): void
    {
        $this->ingest([], ['metadata' => ['dataset_sequence' => 1, 'provider_cursor' => 'cursor-one']]);
        $sync = $this->ingest([], ['dataset_mode' => 'incremental', 'metadata' => ['dataset_sequence' => 2, 'previous_cursor' => 'cursor-one', 'provider_cursor' => 'cursor-two']]);
        $this->assertFalse($sync->coverage['complete']);
        $state = app(BriefingReadiness::class)->execute(aimMission());
        $this->assertTrue($state['blocking']);
        $this->assertSame('coverage_insufficient', collect($state['providers'])->firstWhere('provider', $this->provider()->key())['health_status']);
    }

    public function test_incremental_without_a_continuous_base_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->ingest([], ['dataset_mode' => 'incremental', 'metadata' => ['previous_cursor' => 'missing', 'provider_cursor' => 'new']]);
    }

    public function test_incomplete_coverage_cannot_establish_readiness(): void
    {
        $this->ingest([], ['coverage' => ['complete' => false]]);
        $this->assertTrue(app(BriefingReadiness::class)->execute(aimMission())['blocking']);
    }

    public function test_cancellation_and_replacement_preserve_briefing_evidence(): void
    {
        $record = aimRecord();
        $this->ingest([$record]);
        $mission = aimMission();
        $actor = User::factory()->create(['role' => 'super_admin']);
        $briefing = app(GenerateMissionBriefing::class)->execute($mission, $actor);
        $snapshot = $briefing->items()->first()->snapshot;
        $old = AeronauticalInformationItem::first();
        $this->ingest([aimRecord(['source_identifier' => 'TEST-REPLACEMENT', 'normalized' => ['replaces_identifier' => $record['source_identifier']]])]);
        $replacement = AeronauticalInformationItem::latest('id')->first();
        $this->assertNotNull($old->refresh()->superseded_at);
        $this->assertNull($replacement->superseded_at);
        $cancel = aimRecord(['source_identifier' => 'TEST-CANCELLATION', 'normalized' => ['status' => 'cancelled', 'replaces_identifier' => 'TEST-REPLACEMENT']]);
        $this->ingest([$cancel]);
        $this->ingest([$cancel]);
        $this->assertSame(3, AeronauticalSourceRecord::count());
        $this->assertSame(0, AeronauticalInformationItem::whereNull('superseded_at')->where('status', 'active')->count());
        $this->assertSame($snapshot, $briefing->items()->first()->snapshot);
        $current = app(GenerateMissionBriefing::class)->execute($mission, $actor);
        $this->assertSame(0, $current->items()->count());
        $this->assertSame('green', $current->overall_status);
    }

    public function test_unknown_predecessor_is_rejected_without_changing_current_items(): void
    {
        $this->ingest([aimRecord()]);
        try {
            $this->ingest([aimRecord(['source_identifier' => 'TEST-NEW', 'normalized' => ['replaces_identifier' => 'UNKNOWN']])]);
            $this->fail('Unknown predecessor accepted');
        } catch (ValidationException) {
        }
        $this->assertSame(1, AeronauticalInformationItem::count());
        $this->assertNull(AeronauticalInformationItem::first()->superseded_at);
    }

    public function test_opaque_sync_metadata_is_encrypted_and_never_serialized(): void
    {
        $sync = $this->ingest([], ['metadata' => ['provider_cursor' => 'cursor-secret-contract', 'source_transaction_id' => 'transaction-secret-contract']]);
        $this->assertStringNotContainsString('cursor-secret-contract', $sync->getRawOriginal('sync_metadata'));
        $this->assertSame('cursor-secret-contract', $sync->refresh()->sync_metadata['provider_cursor']);
        $this->assertArrayNotHasKey('sync_metadata', $sync->toArray());
        $audit = UasAuditEntry::all()->toJson();
        $this->assertStringNotContainsString('cursor-secret-contract', $audit);
        $this->assertStringNotContainsString('transaction-secret-contract', $audit);
    }
}
