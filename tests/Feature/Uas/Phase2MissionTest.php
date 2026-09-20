<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Domain\Services\MissionLifecycle;
use App\Domains\Uas\Missions\Domain\Services\MissionGeometry;
use App\Domains\Uas\Missions\Domain\Services\MissionReleaseGate;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Operators\Application\Actions\ApprovePilotForOperator;
use App\Models\User;

function missionPayload(array $overrides = []): array
{
    return [
        'purpose' => 'Linear infrastructure inspection',
        'client_project' => 'Pipeline corridor survey',
        'location' => 'Midrand test range',
        'latitude' => -25.9992,
        'longitude' => 28.1263,
        'operation_category' => 'inspection',
        'uas_aircraft_id' => $overrides['uas_aircraft_id'] ?? null,
        'uas_pilot_id' => $overrides['uas_pilot_id'] ?? null,
        'planned_start_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'planned_end_at' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 3.5,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => 'Wind below internal limit. Visibility good.',
        'airspace_assessment' => 'Uncontrolled airspace; local site review captured.',
        'risk_assessment' => ['overall' => 'low', 'controls' => ['crew briefing', 'emergency landing area']],
        'emergency_arrangements' => 'Abort route and emergency landing zone briefed.',
        ...$overrides,
    ];
}

function missionOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Mission Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'MIS-'.str()->upper(str()->random(5)),
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'regulatory_source' => 'YAW TR-010 mission tenancy verification',
        'regulatory_source_version' => 'TR-010',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Phase 2 mission tenant-aware fixture.',
        'responsible_role' => 'Accountable Manager',
    ]);
}

function missionUser(array $permissions = ['missions.view', 'missions.create'], ?UasOperator $operator = null): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'operations_manager',
        'label' => 'Operations Manager',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    if ($operator) {
        UasOperatorMembership::query()->create([
            'uas_operator_id' => $operator->id,
            'user_id' => $user->id,
            'membership_role' => UasOperatorMembership::ROLE_OPERATIONS_MANAGER,
            'status' => UasOperatorMembership::STATUS_ACTIVE,
            'source' => UasOperatorMembership::SOURCE_ADMIN,
            'activated_at' => now(),
        ]);
    }

    return $user;
}

function missionPilotAndAircraft(string $aircraftStatus = 'active_serviceable', string $certificateStatus = 'valid', ?string $certificateExpiry = null, ?UasOperator $operator = null, ?User $operatorUser = null): array
{
    $pilot = UasPilot::query()->create([
        'first_name' => 'Anele',
        'last_name' => 'Mokoena',
        'email' => fake()->unique()->safeEmail(),
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; UAS Compliance & Operations Platform FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Remote pilot profile master record for South African UAS operations managed in the VMT platform.',
        'responsible_role' => 'Compliance Manager',
    ]);

    PilotCertificate::query()->create([
        'uas_pilot_id' => $pilot->id,
        'certificate_number' => 'RPC-MIS-'.fake()->unique()->numberBetween(1000, 9999),
        'issue_date' => now()->subYear()->toDateString(),
        'expiry_date' => $certificateExpiry ?? now()->addYear()->toDateString(),
        'status' => $certificateStatus,
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
    ]);

    $aircraft = UasAircraft::query()->create([
        'registration' => 'ZT-MIS-'.fake()->unique()->numberBetween(100, 999),
        'manufacturer' => 'VMT',
        'model' => 'Surveyor Two',
        'serial_number' => 'SN-MIS-'.fake()->unique()->numberBetween(1000, 9999),
        'operational_status' => $aircraftStatus,
    ]);

    AircraftRegistration::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'registration_number' => $aircraft->registration,
        'lifecycle_state' => 'initial',
        'issue_date' => now()->subMonth()->toDateString(),
    ]);

    if ($operator) {
        $operator->aircraft()->attach($aircraft->id, [
            'assignment_role' => 'operated_aircraft',
            'status' => 'active',
        ]);

        if ($operatorUser) {
            $pilot->forceFill(['user_id' => $operatorUser->id])->save();
            app(ApprovePilotForOperator::class)->execute(
                $operator,
                $pilot,
                UasOperatorMembership::ROLE_REMOTE_PILOT,
                $operatorUser,
            );
        }
    }

    AircraftApproval::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'approval_type' => 'uasla',
        'approval_number' => 'UASLA-MIS-'.fake()->unique()->numberBetween(1000, 9999),
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'status' => 'valid',
    ]);

    return [$pilot, $aircraft];
}

it('requires mission permissions for Phase 2 mission routes', function () {
    $this->get('/missions')->assertRedirect('/login');

    $user = User::factory()->create();

    $this->actingAs($user)->get('/missions')->assertForbidden();
});

it('creates a mission record with lifecycle, release gate and audit evidence', function () {
    $this->withoutVite();

    $operator = missionOperator();
    $user = missionUser([], $operator);
    [$pilot, $aircraft] = missionPilotAndAircraft(operator: $operator, operatorUser: $user);

    $response = $this->actingAs($user)->post('/missions', missionPayload([
        'uas_operator_id' => $operator->id,
        'uas_pilot_id' => $pilot->id,
        'uas_aircraft_id' => $aircraft->id,
    ]));

    $mission = UasMission::query()->firstOrFail();

    $response->assertRedirect(route('missions.show', $mission));

    expect($mission->purpose)->toBe('Linear infrastructure inspection')
        ->and($mission->lifecycle_state)->toBe(MissionLifecycleState::Draft)
        ->and($mission->release_gate_state)->toBe('red')
        ->and($mission->release_gate_results['checks'])->toHaveCount(8)
        ->and($mission->regulatory_source)->toContain('FR-MIS-001')
        ->and($mission->responsible_role)->toBe('Operations Manager');

    $audit = UasAuditEntry::query()->where('action', 'mission.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasMission::class)
        ->and($audit->auditable_id)->toBe($mission->id)
        ->and($audit->requirement_id)->toBe('FR-MIS-001');

    $this->actingAs($user)->get('/missions')->assertOk();
    $this->actingAs($user)->get("/missions/{$mission->id}")->assertOk();
});

it('distinguishes regulatory release blocks from internal policy attention', function () {
    [$pilot, $aircraft] = missionPilotAndAircraft('grounded', 'valid', now()->addDays(30)->toDateString());

    $mission = UasMission::query()->create([
        'mission_number' => 'MIS-GATE-001',
        ...missionPayload([
            'uas_pilot_id' => $pilot->id,
            'uas_aircraft_id' => $aircraft->id,
            'risk_assessment' => null,
        ]),
        'lifecycle_state' => 'draft',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-001 and FR-MIS-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission planning and operational release workflow for VMT UAS operations.',
        'responsible_role' => 'Operations Manager',
    ]);

    $result = app(MissionReleaseGate::class)->evaluate($mission);

    expect($result['state'])->toBe('red')
        ->and(collect($result['checks'])->where('result', 'red')->pluck('basis')->contains('regulatory'))->toBeTrue()
        ->and(collect($result['checks'])->where('result', 'amber')->pluck('basis')->contains('internal_policy'))->toBeTrue();
});


it('captures mission map geometry for FR-GEO-001', function () {
    $operator = missionOperator();
    $user = missionUser([], $operator);
    [$pilot, $aircraft] = missionPilotAndAircraft(operator: $operator, operatorUser: $user);

    $response = $this->actingAs($user)->post('/missions', missionPayload([
        'uas_operator_id' => $operator->id,
        'uas_pilot_id' => $pilot->id,
        'uas_aircraft_id' => $aircraft->id,
        'location_search_query' => 'Midrand test range gate',
        'takeoff_point' => ['latitude' => -25.9992, 'longitude' => 28.1263, 'label' => 'Launch pad'],
        'landing_point' => ['latitude' => -25.9989, 'longitude' => 28.1281, 'label' => 'Recovery zone'],
        'mission_polygon' => [
            ['latitude' => -25.9992, 'longitude' => 28.1263],
            ['latitude' => -25.9990, 'longitude' => 28.1290],
            ['latitude' => -25.9978, 'longitude' => 28.1280],
        ],
        'flight_route' => [
            ['latitude' => -25.9992, 'longitude' => 28.1263],
            ['latitude' => -25.9988, 'longitude' => 28.1272],
            ['latitude' => -25.9989, 'longitude' => 28.1281],
        ],
        'flight_radius_m' => 500,
    ]));

    $mission = UasMission::query()->firstOrFail();

    $response->assertRedirect(route('missions.show', $mission));

    $summary = app(MissionGeometry::class)->summary(
        $mission->mission_polygon,
        $mission->flight_route,
        $mission->takeoff_point,
        $mission->landing_point,
        $mission->flight_radius_m,
    );

    expect($mission->location_search_query)->toBe('Midrand test range gate')
        ->and($mission->takeoff_point['label'])->toBe('Launch pad')
        ->and($mission->landing_point['label'])->toBe('Recovery zone')
        ->and($mission->mission_polygon)->toHaveCount(3)
        ->and($mission->flight_route)->toHaveCount(3)
        ->and($mission->flight_radius_m)->toBe(500)
        ->and($summary['ready_for_map_review'])->toBeTrue();
});

it('validates mission geometry coordinate bounds', function () {
    $operator = missionOperator();
    $user = missionUser([], $operator);

    $this->actingAs($user)
        ->post('/missions', missionPayload([
            'uas_operator_id' => $operator->id,
            'takeoff_point' => ['latitude' => -95, 'longitude' => 28.1263],
            'flight_radius_m' => 0,
        ]))
        ->assertInvalid(['takeoff_point.latitude', 'flight_radius_m']);
});
it('enforces the documented mission lifecycle order', function () {
    $lifecycle = app(MissionLifecycle::class);

    expect($lifecycle->canTransition('draft', 'planning'))->toBeTrue()
        ->and($lifecycle->canTransition('draft', 'ready_for_flight'))->toBeFalse()
        ->and($lifecycle->canTransition('approved', 'ready_for_flight'))->toBeTrue()
        ->and($lifecycle->canTransition('closed', 'planning'))->toBeFalse();
});
