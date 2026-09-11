<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Geography\Domain\Services\MissionSpatialRuleEvaluator;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function spatialRuleMission(array $overrides = []): UasMission
{
    return UasMission::query()->create([
        'mission_number' => 'MIS-SPATIAL-001',
        'purpose' => 'Spatial rule evaluation proof',
        'client_project' => 'Phase 2 verification',
        'location' => 'Configured sample overlay area',
        'latitude' => null,
        'longitude' => null,
        'takeoff_point' => ['latitude' => -26.0300, 'longitude' => 28.1200, 'label' => 'Launch'],
        'landing_point' => ['latitude' => -26.0320, 'longitude' => 28.1230, 'label' => 'Recovery'],
        'mission_polygon' => [
            ['latitude' => -26.0300, 'longitude' => 28.1200],
            ['latitude' => -26.0300, 'longitude' => 28.1300],
            ['latitude' => -26.0400, 'longitude' => 28.1300],
            ['latitude' => -26.0400, 'longitude' => 28.1200],
        ],
        'flight_route' => [],
        'flight_radius_m' => null,
        'operation_category' => 'inspection',
        'uas_aircraft_id' => null,
        'uas_pilot_id' => null,
        'planned_start_at' => now()->addDay(),
        'planned_end_at' => now()->addDay()->addHour(),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 1.2,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => null,
        'airspace_assessment' => null,
        'approvals' => [],
        'risk_assessment' => [],
        'emergency_arrangements' => null,
        'lifecycle_state' => 'draft',
        'release_gate_state' => 'amber',
        'release_gate_results' => ['state' => 'amber', 'checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-GEO-003',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission geometry rule evaluation.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function spatialRuleUser(): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'spatial_rule_viewer',
        'label' => 'Spatial Rule Viewer',
        'permissions' => ['missions.view'],
    ]);

    $role->users()->attach($user);

    return $user;
}

it('flags configured overlay intersections that require authorisation for FR-GEO-003', function () {
    $mission = spatialRuleMission();

    $result = app(MissionSpatialRuleEvaluator::class)->evaluate($mission);
    $authorisationMatch = collect($result['matches'])->where('result', 'authorisation_required')->first();

    expect($result['state'])->toBe('authorisation_required')
        ->and($result['summary'])->toContain('configured aviation overlay')
        ->and($result['matches'])->not->toBeEmpty()
        ->and(collect($result['matches'])->pluck('zone_type')->intersect(['restricted_airspace', 'approved_operating_zone'])->isNotEmpty())->toBeTrue()
        ->and($authorisationMatch)->not->toBeNull()
        ->and($authorisationMatch['source'])->toHaveKeys(['publisher', 'source_url', 'source_version', 'authoritative']);
});

it('does not evaluate missions without captured geometry', function () {
    $mission = spatialRuleMission([
        'takeoff_point' => null,
        'landing_point' => null,
        'mission_polygon' => [],
        'latitude' => null,
        'longitude' => null,
    ]);

    $result = app(MissionSpatialRuleEvaluator::class)->evaluate($mission);

    expect($result['state'])->toBe('insufficient_geometry')
        ->and($result['matches'])->toBe([]);
});

it('exposes spatial rule review results on the mission show page', function () {
    $this->withoutVite();

    $user = spatialRuleUser();
    $mission = spatialRuleMission();

    $this->actingAs($user)
        ->get("/missions/{$mission->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/show')
            ->has('spatialRuleReview.matches')
            ->where('spatialRuleReview.state', 'authorisation_required')
        );
});