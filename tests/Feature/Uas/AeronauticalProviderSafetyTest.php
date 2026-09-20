<?php

use App\Domains\Uas\AeronauticalInformation\Application\Actions\GenerateMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\SyncAeronauticalInformation;
use App\Domains\Uas\AeronauticalInformation\Application\ProviderRegistry;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\BriefingReadiness;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\ProviderHealth;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderDataset;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalSourceRecord;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use App\Domains\Uas\Missions\Application\Actions\ReleaseMission;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use App\Domains\Uas\Access\Domain\Models\UasRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeferredAimContractProvider extends OperationalAeronauticalTestProvider
{
    public function fetch(ProviderRequest $request): ProviderDataset
    {
        if ($request->payload['delay'] ?? false) {
            app(SyncAeronauticalInformation::class)->execute($this->key(), new ProviderRequest(payload: ['records' => [aimRecord(['source_revision' => '3'])], 'dataset_timestamp' => now()->toISOString(), 'coverage' => $request->payload['coverage']]));
        }

        return parent::fetch($request);
    }
}
class FailedAimContractProvider extends OperationalAeronauticalTestProvider
{
    public function fetch(ProviderRequest $request): ProviderDataset
    {
        throw new RuntimeException('https://user:credential-secret@example.test/feed?token=credential-secret');
    }
}

function aimSafetyPlatformAdmin(): User
{
    $user = User::factory()->create();
    $role = UasRole::query()->create([
        'name' => 'platform_super_admin_'.str()->random(8),
        'label' => 'Platform Super Admin',
        'permissions' => ['platform.super_admin'],
    ]);
    $role->users()->attach($user);

    return $user;
}

it('requires each approval evidence reference even with both flags enabled', function (string $requirement) {
    aimSync();
    config(['aeronautical.providers.operational_test.approval_evidence.'.$requirement => null]);
    $state = app(BriefingReadiness::class)->execute(aimMission());
    expect(collect($state['providers'])->firstWhere('provider', 'operational_test')['health_status'])->toBe('authority_insufficient');
    expect($state['blocking'])->toBeTrue();
})->with(ProviderRegistry::APPROVAL_EVIDENCE);

it('requires operational permission in server configuration', function (string $field) {
    aimSync();
    config(['aeronautical.providers.operational_test.'.$field => false]);
    expect(app(BriefingReadiness::class)->execute(aimMission())['blocking'])->toBeTrue();
})->with(['operational', 'approved', 'enabled']);

it('rejects credentials and signed URLs before raw evidence is persisted', function (array $record) {
    expect(fn () => aimSync([aimRecord($record)]))->toThrow(ValidationException::class);
    expect(AeronauticalSourceRecord::count())->toBe(0);
    expect(UasAuditEntry::all()->toJson())->not->toContain('credential-secret');
})->with([
    'url userinfo' => [['source_url' => 'https://user:credential-secret@example.test/notam']],
    'url query' => [['source_url' => 'https://example.test/notam?key=credential-secret']],
    'raw secret key' => [['authorization' => 'credential-secret']],
    'nested secret' => [['normalized' => ['api_key' => 'credential-secret']]],
    'raw bearer' => [['raw_message' => 'Bearer credential-secret']],
]);

it('sanitizes provider failure at the action API and audit boundaries', function () {
    aimSync();
    config(['aeronautical.providers.operational_test.adapter' => FailedAimContractProvider::class, 'aeronautical.providers.operational_test.client_secret' => 'credential-secret']);
    try {
        app(SyncAeronauticalInformation::class)->execute('operational_test', new ProviderRequest);
        $this->fail('Expected failure');
    } catch (ValidationException $error) {
        expect(json_encode($error->errors()))->not->toContain('credential-secret');
    }
    $mission = aimMission();
    $health = collect(app(ProviderHealth::class)->execute($mission))->firstWhere('provider', 'operational_test');
    expect($health['health_status'])->toBe('sync_failed')->and($health['usable_for_release'])->toBeFalse();
    $this->actingAs(aimSafetyPlatformAdmin())
        ->withHeader('X-YAW-Operator', (string) $mission->uas_operator_id)
        ->getJson("/api/v1/missions/{$mission->id}/briefing")
        ->assertOk()
        ->assertDontSee('credential-secret');
    expect(UasAuditEntry::all()->toJson())->not->toContain('credential-secret');
    expect(ProviderSync::latest('id')->first()->error)->not->toContain('credential-secret');
});

it('does not let a delayed sync supersede a later successful sync', function () {
    aimSync([aimRecord()]);
    config(['aeronautical.providers.operational_test.adapter' => DeferredAimContractProvider::class]);
    $coverage = ProviderSync::first()->coverage;
    expect(fn () => app(SyncAeronauticalInformation::class)->execute('operational_test', new ProviderRequest(payload: ['delay' => true, 'records' => [aimRecord(['source_revision' => '2'])], 'dataset_timestamp' => now()->toISOString(), 'coverage' => $coverage])))->toThrow(ValidationException::class);
    expect(ProviderSync::find(2)->status)->toBe('superseded');
    expect(AeronauticalInformationItem::whereNull('superseded_at')->first()->source_revision)->toBe('3');
    expect(collect(app(ProviderHealth::class)->execute(aimMission()))->firstWhere('provider', 'operational_test')['health_status'])->toBe('healthy');
});

it('rejects regressing and conflicting sequence identifiers', function (array $metadata) {
    aimSync([], ['metadata' => ['dataset_sequence' => 5, 'snapshot_id' => 'snapshot-five']]);
    expect(fn () => aimSync([aimRecord()], ['metadata' => $metadata]))->toThrow(ValidationException::class);
    expect(AeronauticalInformationItem::count())->toBe(0);
})->with([
    'sequence regresses' => [['dataset_sequence' => 4]],
    'sequence reused' => [['dataset_sequence' => 5]],
    'sequence disappears' => [[]],
    'snapshot changes content' => [['dataset_sequence' => 6, 'snapshot_id' => 'snapshot-five']],
]);

it('rejects a lower numeric revision even when its issue timestamp is unchanged', function () {
    $this->freezeTime();
    aimSync([aimRecord(['source_revision' => '3'])]);
    expect(fn () => aimSync([aimRecord(['source_revision' => '2'])]))->toThrow(ValidationException::class);
    expect(AeronauticalInformationItem::whereNull('superseded_at')->first()->source_revision)->toBe('3');
});

it('blocks release for missing stale failed partial and revoked sources', function (string $scenario) {
    $actor = aimSafetyPlatformAdmin();
    $mission = aimMission();
    if ($scenario !== 'unconfigured') {
        aimSync();
        app(GenerateMissionBriefing::class)->execute($mission, $actor);
        expect(app(BriefingReadiness::class)->execute($mission)['blocking'])->toBeFalse();
    }
    if ($scenario === 'stale') {
        $this->travel(61)->minutes();
    }
    if ($scenario === 'failed') {
        try {
            aimSync([['invalid' => true]]);
        } catch (ValidationException) {
        }
    }
    if ($scenario === 'partial') {
        aimSync([], ['coverage' => ['complete' => false]]);
    }
    if ($scenario === 'revoked') {
        config(['aeronautical.providers.operational_test.approved' => false]);
    }
    if ($scenario === 'disabled') {
        config(['aeronautical.providers.operational_test.enabled' => false]);
    }
    expect(app(BriefingReadiness::class)->execute($mission)['blocking'])->toBeTrue();
    expect(fn () => app(ReleaseMission::class)->execute($mission, $actor))->toThrow(ValidationException::class);
    expect($mission->refresh()->lifecycle_state->value)->toBe('approved');
})->with(['unconfigured', 'stale', 'failed', 'partial', 'revoked', 'disabled']);

it('loads source health in two queries regardless of registered provider count', function () {
    aimSync();
    DB::enableQueryLog();
    DB::flushQueryLog();
    app(ProviderHealth::class)->execute();
    expect(DB::getQueryLog())->toHaveCount(2);
    DB::disableQueryLog();
});
