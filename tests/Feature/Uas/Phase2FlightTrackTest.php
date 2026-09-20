<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Tracks\Application\Queries\MissionTrackReport;
use App\Domains\Uas\Tracks\Domain\Models\UasFlightTrack;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function trackOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'track Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'TRA-'.str()->upper(str()->random(5)),
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'regulatory_source' => 'YAW TR-010 track tenancy verification',
        'regulatory_source_version' => 'TR-010',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Phase 2 tenant-aware test fixture.',
        'responsible_role' => 'Accountable Manager',
    ]);
}

function trackMission(array $overrides = [], ?UasOperator $operator = null): UasMission
{
    return UasMission::query()->create([
        'mission_number' => 'MIS-TRK-001',
        'purpose' => 'Flight track proof',
        'client_project' => 'Phase 2 verification',
        'location' => 'Track test range',
        'operation_category' => 'inspection',
        'uas_operator_id' => $operator?->id,
        'uas_aircraft_id' => null,
        'uas_pilot_id' => null,
        'planned_start_at' => now()->subHours(2),
        'planned_end_at' => now()->subHour(),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 1.2,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => null,
        'airspace_assessment' => null,
        'approvals' => [],
        'risk_assessment' => [],
        'emergency_arrangements' => null,
        'lifecycle_state' => 'in_progress',
        'release_gate_state' => 'green',
        'release_gate_results' => ['state' => 'green', 'checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-TRK-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Flight track verification mission.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function trackUser(array $permissions = ['missions.view', 'missions.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'track_recorder',
        'label' => 'Track Recorder',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    if ($operator) {
        UasOperatorMembership::query()->create([
            'uas_operator_id' => $operator->id,
            'user_id' => $user->id,
            'membership_role' => $membershipRole,
            'status' => UasOperatorMembership::STATUS_ACTIVE,
            'source' => UasOperatorMembership::SOURCE_ADMIN,
            'activated_at' => now(),
        ]);
    }

    return $user;
}

function trackPayload(array $overrides = []): array
{
    return [
        'source_type' => 'telemetry_import',
        'track_reference' => 'GCS-EXPORT-001',
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'ended_at' => now()->subHour()->format('Y-m-d H:i:s'),
        'points' => [
            ['latitude' => -26.0300, 'longitude' => 28.1200, 'altitude_ft' => 320, 'recorded_at' => now()->subHours(2)->format('Y-m-d H:i:s')],
            ['latitude' => -26.0310, 'longitude' => 28.1240, 'altitude_ft' => 360, 'recorded_at' => now()->subMinutes(90)->format('Y-m-d H:i:s')],
            ['latitude' => -26.0320, 'longitude' => 28.1280, 'altitude_ft' => 345, 'recorded_at' => now()->subHour()->format('Y-m-d H:i:s')],
        ],
        'anomalies' => ['Brief GNSS accuracy warning at midpoint.'],
        'notes' => 'Imported from ground control station export.',
        ...$overrides,
    ];
}

it('requires mission update permission for flight track routes', function () {
    $this->withoutVite();

    $operator = trackOperator();
    $mission = trackMission([], $operator);
    $viewer = trackUser(['missions.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($viewer)->get("/missions/{$mission->id}/tracks/create")->assertForbidden();
    $this->actingAs($viewer)->post("/missions/{$mission->id}/tracks", trackPayload())->assertForbidden();
});

it('records flight track telemetry summary and audit evidence for FR-TRK-001', function () {
    $operator = trackOperator();
    $user = trackUser([], $operator);
    $mission = trackMission([], $operator);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/tracks", trackPayload())
        ->assertRedirect(route('missions.show', $mission));

    $track = UasFlightTrack::query()->firstOrFail();

    expect($track->uas_mission_id)->toBe($mission->id)
        ->and($track->captured_by)->toBe($user->id)
        ->and($track->source_type)->toBe('telemetry_import')
        ->and($track->track_reference)->toBe('GCS-EXPORT-001')
        ->and($track->point_count)->toBe(3)
        ->and((float) $track->total_distance_km)->toBeGreaterThan(0)
        ->and($track->max_altitude_ft)->toBe(360)
        ->and($track->anomalies)->toHaveCount(1)
        ->and($track->regulatory_source)->toContain('FR-TRK-001');

    $audit = UasAuditEntry::query()->where('action', 'flight_track.recorded')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasFlightTrack::class)
        ->and($audit->auditable_id)->toBe($track->id)
        ->and($audit->requirement_id)->toBe('FR-TRK-001');
});

it('validates that a flight track has at least two bounded points', function () {
    $operator = trackOperator();
    $user = trackUser([], $operator);
    $mission = trackMission([], $operator);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/tracks", trackPayload([
            'points' => [['latitude' => -95, 'longitude' => 28.1200]],
        ]))
        ->assertInvalid(['points', 'points.0.latitude']);
});

it('exposes track capture options and mission track summary on mission screens', function () {
    $this->withoutVite();

    $operator = trackOperator();
    $user = trackUser([], $operator);
    $mission = trackMission([], $operator);

    $this->actingAs($user)
        ->get("/missions/{$mission->id}/tracks/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/tracks/create')
            ->has('sourceTypes.telemetry_import')
            ->where('tracks.summary.total', 0)
        );

    $this->actingAs($user)->post("/missions/{$mission->id}/tracks", trackPayload());

    $report = app(MissionTrackReport::class)->execute($mission->refresh());

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['total_points'])->toBe(3)
        ->and($report['summary']['anomaly_count'])->toBe(1);

    $this->actingAs($user)
        ->get("/missions/{$mission->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/show')
            ->where('tracks.summary.total', 1)
            ->where('tracks.tracks.0.track_reference', 'GCS-EXPORT-001')
        );
});