<?php

namespace App\Domains\Uas\AeronauticalInformation\Infrastructure\Repositories;

use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalRepositoryInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalSourceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EloquentAeronauticalRepository implements AeronauticalRepositoryInterface
{
    public function lockDataset(): void
    {
        DB::table('uas_aeronautical_dataset_state')->where('id', 1)->lockForUpdate()->first();
    }

    public function datasetHash(): string
    {
        return hash('sha256', (string) DB::table('uas_aeronautical_dataset_state')->where('id', 1)->value('revision'));
    }

    public function advanceDataset(): int
    {
        DB::table('uas_aeronautical_dataset_state')->where('id', 1)->increment('revision');

        return (int) DB::table('uas_aeronautical_dataset_state')->where('id', 1)->value('revision');
    }

    public function currentItems(): Collection
    {
        return AeronauticalInformationItem::query()->with('source')->whereNull('superseded_at')->where('status', 'active')->orderBy('id')->get();
    }

    public function storeRevision(array $source, array $interpretation): array
    {
        $existing = AeronauticalInformationItem::query()->where('identity_hash', $interpretation['identity_hash'])->first();
        if ($existing) {
            if ($existing->usable_for_release !== $interpretation['usable_for_release']) {
                throw ValidationException::withMessages(['record' => 'Existing interpretation authority changed. A governed new source revision is required.']);
            }

            return ['item' => $existing, 'created' => false, 'superseded' => []];
        }
        $previous = AeronauticalInformationItem::query()->where('provider', $source['provider'])
            ->whereIn('source_identifier', array_filter([$source['source_identifier'], $interpretation['interpretation']['replaces_identifier'] ?? null]))
            ->whereNull('superseded_at')->get();
        $predecessor = $interpretation['interpretation']['replaces_identifier'] ?? null;
        if ($predecessor && ! $previous->contains('source_identifier', $predecessor)) {
            throw ValidationException::withMessages(['record' => 'Replacement or cancellation predecessor is unknown or already closed.']);
        }
        $history = AeronauticalInformationItem::query()->where('provider', $source['provider'])->where('source_identifier', $source['source_identifier'])->latest('id')->first();
        if ($history && ! $previous->contains('id', $history->id)) {
            throw ValidationException::withMessages(['record' => 'A closed source identifier cannot be silently reactivated.']);
        }
        if ($history) {
            $incoming = $source['source_revision'];
            $oldRevision = $history->source_revision;
            if ($incoming === $oldRevision || (ctype_digit($incoming) && ctype_digit($oldRevision) && version_compare($incoming, $oldRevision, '<='))
                || ((! ctype_digit($incoming) || ! ctype_digit($oldRevision)) && (! $history->issued_at || empty($source['issued_at']) || CarbonImmutable::parse($source['issued_at'])->lte($history->issued_at)))) {
                throw ValidationException::withMessages(['record' => 'Source revision regressed or its ordering is ambiguous.']);
            }
        }
        foreach ($previous as $old) {
            if ($old->issued_at && (empty($source['issued_at']) || CarbonImmutable::parse($source['issued_at'])->lt($old->issued_at))) {
                throw ValidationException::withMessages(['issued_at' => 'A source revision cannot supersede a newer issued record.']);
            }
        }
        $raw = AeronauticalSourceRecord::query()->firstOrCreate(['identity_hash' => $source['identity_hash']], $source);
        $item = AeronauticalInformationItem::query()->create([
            ...$interpretation, 'uuid' => (string) Str::uuid(), 'source_record_id' => $raw->id,
            'supersedes_id' => $previous->first()?->id,
        ]);
        foreach ($previous as $old) {
            $old->update(['superseded_at' => now()]);
        }

        return ['item' => $item, 'created' => true, 'superseded' => $previous->all()];
    }
}
