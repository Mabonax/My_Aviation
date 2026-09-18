<?php

use App\Domains\Uas\AeronauticalInformation\Application\Actions\AcknowledgeMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\AssessAeronauticalInformationForMission;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\GenerateMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\SyncAeronauticalInformation;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\BriefingReadiness;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\Geography\Domain\Services\RegionalGeometryOverlap;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

it('detects a route crossing a source polygon without any endpoint inside', function () {
    $mission = aimMission(['latitude' => null, 'longitude' => null, 'flight_route' => [['latitude' => -24, 'longitude' => 26.8], ['latitude' => -24, 'longitude' => 27.2]]]);
    $shape = ['type' => 'Polygon', 'coordinates' => [[[26.95, -24.05], [27.05, -24.05], [27.05, -23.95], [26.95, -23.95], [26.95, -24.05]]]];
    expect(app(RegionalGeometryOverlap::class)->evaluate($mission, $shape, null, null, 0, 0))->toBeTrue();
});

it('does not use bounding box overlap as a false positive polygon intersection', function () {
    $mission = aimMission(['latitude' => -23.91, 'longitude' => 27.09]);
    $shape = ['type' => 'Polygon', 'coordinates' => [[[26.9, -24.1], [27.1, -24.1], [26.9, -23.9], [26.9, -24.1]]]];
    expect(app(RegionalGeometryOverlap::class)->evaluate($mission, $shape, null, null, 0, 0))->toBeFalse();
});

it('excludes distant regional notices but preserves uncertainty for unsupported geometry', function () {
    $mission = aimMission();
    $geometry = app(RegionalGeometryOverlap::class);
    expect($geometry->evaluate($mission, null, -33.9, 18.4, 100, 500))->toBeFalse()
        ->and($geometry->evaluate($mission, ['type' => 'MultiPolygon', 'coordinates' => []], null, null, 0, 500))->toBeNull();
});

it('keeps permanent and incomplete NOTAM fields rather than dropping their relevance', function () {
    aimSync([aimRecord(['effective_until' => null, 'normalized' => ['permanent' => true, 'hazard' => 'restriction']])]);
    $result = app(AssessAeronauticalInformationForMission::class)->execute(aimMission(), AeronauticalInformationItem::first());
    expect($result['temporal_overlap'])->toBeTrue()->and($result['release_effect'])->toBe('block');
});

it('rejects an out of order source revision even within a newer dataset', function () {
    aimSync([aimRecord()]);
    expect(fn () => aimSync([aimRecord(['source_revision' => 'older', 'issued_at' => now()->subDays(2)->toISOString()])]))->toThrow(ValidationException::class);
    expect(AeronauticalInformationItem::count())->toBe(1)->and(AeronauticalInformationItem::first()->superseded_at)->toBeNull();
});

it('makes a previously fresh source unavailable after synchronization fails', function () {
    aimSync();
    $mission = aimMission();
    $actor = User::factory()->create(['role' => 'super_admin']);
    app(GenerateMissionBriefing::class)->execute($mission, $actor);
    expect(app(BriefingReadiness::class)->execute($mission)['status'])->toBe('green');
    try {
        aimSync([['bad' => 'record']]);
    } catch (ValidationException) {
    }
    expect(app(BriefingReadiness::class)->execute($mission)['freshness'])->toBe('unavailable')->and(app(BriefingReadiness::class)->execute($mission)['status'])->toBe('red');
});

it('invalidates snapshots when configured buffers change', function () {
    aimSync();
    $mission = aimMission();
    $actor = User::factory()->create(['role' => 'super_admin']);
    app(GenerateMissionBriefing::class)->execute($mission, $actor);
    config(['aeronautical.horizontal_buffer_m' => 1000]);
    expect(app(BriefingReadiness::class)->execute($mission)['current'])->toBeFalse();
});

it('retains the sealed briefing after a mission is released', function () {
    aimSync([aimRecord()]);
    $mission = aimMission();
    $actor = User::factory()->create(['role' => 'super_admin']);
    $briefing = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    app(AcknowledgeMissionBriefing::class)->execute($mission, $briefing, $actor);
    $mission->update(['lifecycle_state' => 'ready_for_flight']);
    expect(fn () => app(GenerateMissionBriefing::class)->execute($mission, $actor))->toThrow(ValidationException::class);
    expect(fn () => app(AcknowledgeMissionBriefing::class)->execute($mission, $briefing, $actor))->toThrow(ValidationException::class);
    expect($briefing->items()->count())->toBe(1);
});

it('enforces operator scope for all briefing endpoints', function () {
    $actor = User::factory()->create();
    $a = UasOperator::query()->create(['legal_entity' => 'Synthetic Operator A', 'registration_number' => 'AIM-A', 'status' => 'active', 'accountable_manager' => 'Test manager', 'responsible_person_flight_operations' => 'Test operations', 'responsible_person_aircraft' => 'Test aircraft', 'regulatory_source' => 'TEST ONLY FR-AIM', 'regulatory_source_version' => '1.0', 'regulatory_effective_date' => '2026-09-15', 'regulatory_applicability' => 'Test operator scope', 'responsible_role' => 'Test manager']);
    $b = UasOperator::query()->create(['legal_entity' => 'Synthetic Operator B', 'registration_number' => 'AIM-B', 'status' => 'active', 'accountable_manager' => 'Test manager', 'responsible_person_flight_operations' => 'Test operations', 'responsible_person_aircraft' => 'Test aircraft', 'regulatory_source' => 'TEST ONLY FR-AIM', 'regulatory_source_version' => '1.0', 'regulatory_effective_date' => '2026-09-15', 'regulatory_applicability' => 'Test operator scope', 'responsible_role' => 'Test manager']);
    UasOperatorMembership::query()->create(['uas_operator_id' => $a->id, 'user_id' => $actor->id, 'status' => 'active', 'membership_role' => 'operations_manager']);
    $own = aimMission(['uas_operator_id' => $a->id]);
    $foreign = aimMission(['uas_operator_id' => $b->id]);
    aimSync();
    Sanctum::actingAs($actor);
    $this->getJson("/api/v1/missions/{$own->id}/briefing")->assertOk();
    $id = $this->postJson("/api/v1/missions/{$own->id}/briefing")->assertCreated()->json('data.briefing.id');
    $this->getJson("/api/v1/missions/{$foreign->id}/briefing")->assertForbidden();
    $this->postJson("/api/v1/missions/{$foreign->id}/briefing")->assertForbidden();
    $this->postJson("/api/v1/missions/{$foreign->id}/briefing/{$id}/acknowledge", ['reviewed' => true])->assertForbidden();
});

it('does not allow public source summaries to satisfy the required operational source', function () {
    $sync = app(SyncAeronauticalInformation::class)->execute('manual', new ProviderRequest(payload: ['records' => [], 'dataset_timestamp' => now()->toISOString()]));
    config(['aeronautical.required_providers' => ['manual']]);
    $mission = aimMission();
    $actor = User::factory()->create(['role' => 'super_admin']);
    $briefing = app(GenerateMissionBriefing::class)->execute($mission, $actor);
    expect($sync->usable_for_release)->toBeFalse()->and($briefing->overall_status)->toBe('red');
});

it('treats malformed provider geometries as uncertain rather than clear airspace', function (array $shape) {
    expect(app(RegionalGeometryOverlap::class)->evaluate(aimMission(), $shape, null, null, 0, 500))->toBeNull();
})->with([
    'non-scalar geometry type' => [['type' => [], 'coordinates' => []]],
    'malformed point' => [['type' => 'Point', 'coordinates' => ['bad', -24]]],
    'malformed polygon ring' => [['type' => 'Polygon', 'coordinates' => ['bad']]],
]);

it('does not let a matching FIR override known disjoint local geometry', function () {
    aimSync([aimRecord(['normalized' => ['latitude' => -33.9, 'longitude' => 18.4, 'fir_code' => 'FAJA']])]);
    $mission = aimMission(['aeronautical_context' => ['altitude_reference' => 'AGL', 'fir_codes' => ['FAJA']]]);
    $result = app(AssessAeronauticalInformationForMission::class)->execute($mission, AeronauticalInformationItem::first());
    expect($result['fir_match'])->toBeTrue()->and($result['horizontal_overlap'])->toBeFalse()->and($result['relevant'])->toBeFalse();
});
