<?php

use App\Domains\Uas\AeronauticalInformation\Application\Actions\AcknowledgeMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\AssessAeronauticalInformationForMission;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\GenerateMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\SyncAeronauticalInformation;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\BriefingReadiness;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\MissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalSourceRecord;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use App\Domains\Uas\Missions\Application\Actions\ReleaseMission;
use App\Domains\Uas\Missions\Application\Queries\MissionComplianceSummary;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->withoutVite();
});

function aimPlatformAdmin(): User
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

it('preserves source data and imports duplicate records idempotently', function () {
    $record = aimRecord();
    aimSync([$record]);
    $sync = aimSync([$record]);
    expect(AeronauticalInformationItem::count())->toBe(1)->and(AeronauticalSourceRecord::count())->toBe(1)->and($sync->records_created)->toBe(0);
    $item = AeronauticalInformationItem::first();
    expect($item->source->raw_payload)->toBe($record)->and($item->interpretation['q_code'])->toBe('QWALW')->and($item->source->checksum)->toBe(hash('sha256', json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));
});

it('creates revisions and cancellation history without overwriting raw source', function () {
    $record = aimRecord();
    aimSync([$record]);
    $old = AeronauticalInformationItem::first();
    aimSync([aimRecord(['source_revision' => '2', 'title' => 'Changed synthetic record'])]);
    expect($old->refresh()->superseded_at)->not->toBeNull()->and($old->source->raw_payload)->toBe($record);
    aimSync([aimRecord(['source_identifier' => 'TEST-CANCEL', 'source_revision' => '3', 'normalized' => ['status' => 'cancelled', 'replaces_identifier' => $old->source_identifier]])]);
    expect(AeronauticalInformationItem::whereNull('superseded_at')->where('status', 'active')->count())->toBe(0)->and(AeronauticalSourceRecord::count())->toBe(3);
});

it('never accepts an authority claim from manual or fixture payloads', function (string $provider) {
    $sync = app(SyncAeronauticalInformation::class)->execute($provider, new ProviderRequest(payload: ['dataset_timestamp' => now()->toISOString(), 'records' => [aimRecord(['usable_for_release' => true, 'source_classification' => 'official_live'])], 'coverage' => ['complete' => true]]));
    expect($sync->usable_for_release)->toBeFalse()->and(AeronauticalInformationItem::first()->usable_for_release)->toBeFalse()->and($sync->coverage)->toBe([]);
})->with(['manual', 'fixture']);

it('imports SACAA publication references without claiming operational feed access', function () {
    $record = aimRecord(['information_type' => 'AIP_SUPPLEMENT', 'source_url' => 'https://www.caa.co.za/example-reference.pdf']);
    $sync = app(SyncAeronauticalInformation::class)->execute('sacaa_publications', new ProviderRequest(payload: ['dataset_timestamp' => now()->toISOString(), 'records' => [$record]]));
    expect($sync->usable_for_release)->toBeFalse()->and(AeronauticalInformationItem::first()->source_classification)->toBe('official_publication');
});

it('rejects partial datasets atomically and retains a failed sync audit', function () {
    expect(fn () => aimSync([aimRecord(), ['title' => 'Invalid']]))->toThrow(ValidationException::class);
    expect(AeronauticalInformationItem::count())->toBe(0)->and(AeronauticalSourceRecord::count())->toBe(0)->and(ProviderSync::first()->status)->toBe('failed');
    expect(UasAuditEntry::where('action', 'aeronautical.sync.failed')->exists())->toBeTrue();
});

it('rejects a dataset older than the accepted source timestamp', function () {
    aimSync();
    expect(fn () => aimSync([aimRecord()], ['dataset_timestamp' => now()->subDay()->toISOString()]))->toThrow(ValidationException::class);
    expect(AeronauticalInformationItem::count())->toBe(0);
});

it('evaluates horizontal vertical and temporal overlap conservatively', function (array $recordChanges, array $missionChanges, bool $relevant) {
    aimSync([aimRecord($recordChanges)]);
    $result = app(AssessAeronauticalInformationForMission::class)->execute(aimMission($missionChanges), AeronauticalInformationItem::first());
    expect($result['relevant'])->toBe($relevant);
})->with([
    'point overlap' => [[], [], true],
    'point outside' => [['normalized' => ['latitude' => -25]], [], false],
    'above maximum' => [['normalized' => ['lower_limit_value' => 700]], [], false],
    'metres overlap' => [['normalized' => ['lower_limit_value' => 100, 'lower_limit_unit' => 'M']], [], true],
    'expired' => [['effective_from' => '2020-01-01', 'effective_until' => '2020-02-01'], [], false],
    'future' => [['effective_from' => '2099-01-01', 'effective_until' => '2099-02-01'], [], false],
    'radius intersects' => [['normalized' => ['latitude' => -24.03, 'radius_nm' => 0]], ['flight_radius_m' => 4000], true],
    'route crosses circle' => [['normalized' => ['radius_nm' => 0.1]], ['latitude' => null, 'longitude' => null, 'flight_route' => [['latitude' => -24, 'longitude' => 26.95], ['latitude' => -24, 'longitude' => 27.05]]], true],
    'polygon contains circle' => [[], ['latitude' => null, 'longitude' => null, 'mission_polygon' => [['latitude' => -24.1, 'longitude' => 26.9], ['latitude' => -24.1, 'longitude' => 27.1], ['latitude' => -23.9, 'longitude' => 27.1], ['latitude' => -23.9, 'longitude' => 26.9]]], true],
    'unknown altitude datum' => [[], ['aeronautical_context' => []], true],
]);

it('does not compare flight levels with AGL or infer missing geometry as clear', function () {
    aimSync([aimRecord(['normalized' => ['lower_limit_value' => 100, 'lower_limit_unit' => 'FL', 'lower_limit_reference' => 'FL', 'latitude' => null, 'longitude' => null]])]);
    $assessment = app(AssessAeronauticalInformationForMission::class)->execute(aimMission(), AeronauticalInformationItem::first());
    expect($assessment['horizontal_overlap'])->toBeNull()->and($assessment['vertical_overlap'])->toBeNull()->and($assessment['release_effect'])->toBe('block');
});

it('distinguishes complete empty data from unavailable and stale sources', function () {
    $actor = aimPlatformAdmin();
    $mission = aimMission();
    $unavailable = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    expect($unavailable->overall_status)->toBe('red')->and($unavailable->snapshot['empty_data_message'])->toContain('unresolved');
    aimSync();
    $fresh = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    expect($fresh->overall_status)->toBe('green')->and($fresh->snapshot['empty_data_message'])->toContain('complete, current');
    $this->travel(61)->minutes();
    $state = app(BriefingReadiness::class)->execute($mission);
    expect($state['status'])->toBe('red')->and($state['freshness'])->toBe('stale')->and($state['current'])->toBeFalse();
});

it('requires spatial and temporal coverage even when a provider returns zero notices', function () {
    aimSync([], ['coverage' => ['complete' => true, 'information_types' => ['NOTAM'], 'bbox' => [20, -30, 30, -20], 'valid_from' => now()->subHour()->toISOString(), 'valid_until' => now()->toISOString()]]);
    $state = app(BriefingReadiness::class)->execute(aimMission());
    expect($state['status'])->toBe('red')->and($state['providers'][4]['coverage_complete'] ?? false)->toBeFalse();
});

it('stores immutable snapshots and requires acknowledgement of warnings', function () {
    aimSync([aimRecord(), aimRecord(['source_identifier' => 'TEST-FAR', 'normalized' => ['latitude' => -25]])]);
    $actor = aimPlatformAdmin();
    $mission = aimMission();
    $briefing = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    $original = $briefing->refresh()->toArray();
    expect($briefing->items()->count())->toBe(1)->and($briefing->overall_status)->toBe('amber')->and(app(BriefingReadiness::class)->execute($mission)['status'])->toBe('red');
    app(AcknowledgeMissionBriefing::class)->execute($mission, $briefing, $actor);
    app(AcknowledgeMissionBriefing::class)->execute($mission, $briefing, $actor);
    expect($briefing->refresh()->toArray())->toBe($original)->and($briefing->acknowledgements()->count())->toBe(1)->and(app(BriefingReadiness::class)->execute($mission)['status'])->toBe('amber');
    expect(fn () => $briefing->update(['overall_status' => 'green']))->toThrow(LogicException::class);
    expect(fn () => $briefing->items()->first()->update(['snapshot' => []]))->toThrow(LogicException::class);
    expect(fn () => AeronauticalSourceRecord::first()->update(['raw_message' => 'mutated']))->toThrow(LogicException::class);
});

it('rejects acknowledgement of hard blockers and records blocked release attempts', function () {
    aimSync([aimRecord(['normalized' => ['hazard' => 'restriction']])]);
    $actor = aimPlatformAdmin();
    $mission = aimMission();
    $briefing = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    expect(fn () => app(AcknowledgeMissionBriefing::class)->execute($mission, $briefing, $actor))->toThrow(ValidationException::class);
    expect(fn () => app(ReleaseMission::class)->execute($mission, $actor))->toThrow(ValidationException::class);
    expect(UasAuditEntry::where('action', 'aeronautical.release.blocked')->exists())->toBeTrue()->and($mission->refresh()->lifecycle_state->value)->toBe('approved');
});

it('invalidates previous briefings when mission geometry or a source changes', function () {
    aimSync([aimRecord()]);
    $actor = aimPlatformAdmin();
    $mission = aimMission();
    $old = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    $original = $old->items()->first()->snapshot;
    $mission->update(['maximum_altitude_ft' => 500]);
    expect(app(BriefingReadiness::class)->execute($mission)['current'])->toBeFalse();
    $current = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    expect($current->revision)->toBe(2);
    expect(fn () => app(AcknowledgeMissionBriefing::class)->execute($mission, $old, $actor))->toThrow(ValidationException::class);
    aimSync([aimRecord(['source_revision' => '2'])]);
    expect(app(BriefingReadiness::class)->execute($mission)['current'])->toBeFalse()->and($old->items()->first()->snapshot)->toBe($original);
});

it('blocks default release when the official source has never been configured', function () {
    $mission = aimMission();
    $actor = aimPlatformAdmin();
    $summary = app(MissionComplianceSummary::class)->execute($mission);
    expect($summary['aeronautical_information']['freshness'])->toBe('unavailable');
    expect(fn () => app(ReleaseMission::class)->execute($mission, $actor))->toThrow(ValidationException::class);
    expect(UasAuditEntry::where('action', 'aeronautical.release.stale_or_unavailable')->exists())->toBeTrue();
});

it('returns identical web and API briefing state through the shared query', function () {
    aimSync([aimRecord()]);
    $actor = aimPlatformAdmin();
    $mission = aimMission();
    $briefing = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    $expected = app(MissionBriefing::class)->execute($mission, $actor);
    $sharedQuery = Mockery::mock(MissionBriefing::class, [app(BriefingReadiness::class)])->makePartial();
    $sharedQuery->shouldReceive('execute')->twice()->passthru();
    $this->app->instance(MissionBriefing::class, $sharedQuery);
    Sanctum::actingAs($actor);
    $this->getJson("/api/v1/missions/{$mission->id}/briefing")->assertOk()->assertJsonPath('data.compliance', $expected['compliance'])->assertJsonPath('data.briefing.id', $briefing->id)->assertJsonPath('meta.contract_version', 'v1.0');
    $this->actingAs($actor)->get("/missions/{$mission->id}/briefing")->assertInertia(fn ($page) => $page->component('aeronautical-information/briefing')->where('compliance', $expected['compliance']));
});

it('authenticates and authorizes register detail generation and acknowledgement endpoints', function () {
    $mission = aimMission();
    $this->getJson('/api/v1/aeronautical-information')->assertUnauthorized();
    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/aeronautical-information')->assertForbidden();
    $this->getJson("/api/v1/missions/{$mission->id}/briefing")->assertStatus(409);
    $this->postJson("/api/v1/missions/{$mission->id}/briefing")->assertForbidden();
    $actor = aimPlatformAdmin();
    Sanctum::actingAs($actor);
    aimSync([aimRecord()]);
    $this->getJson('/api/v1/aeronautical-information?type=NOTAM')->assertOk()->assertJsonPath('data.items.total', 1);
    $item = AeronauticalInformationItem::first();
    $this->getJson("/api/v1/aeronautical-information/{$item->id}")->assertOk()->assertJsonPath('data.item.source_identifier', 'TEST-A0001/26');
    $response = $this->postJson("/api/v1/missions/{$mission->id}/briefing")->assertCreated();
    $id = $response->json('data.briefing.id');
    $this->postJson("/api/v1/missions/{$mission->id}/briefing/{$id}/acknowledge", ['reviewed' => false])->assertUnprocessable();
    $this->postJson("/api/v1/missions/{$mission->id}/briefing/{$id}/acknowledge", ['reviewed' => true])->assertOk()->assertJsonPath('data.compliance.acknowledged', true);
    $other = aimMission();
    $this->postJson("/api/v1/missions/{$other->id}/briefing/{$id}/acknowledge", ['reviewed' => true])->assertNotFound();
});
