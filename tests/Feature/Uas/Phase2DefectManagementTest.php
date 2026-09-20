<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Defects\Application\Queries\MissionDefectReport;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Domain\Services\MissionReleaseGate;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function defectOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Defect Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'DEF-'.str()->upper(str()->random(5)),
        'status' => 'active',
        'accountable_manager' => 'Defect Accountable Manager',
        'responsible_person_flight_operations' => 'Defect Flight Operations',
        'responsible_person_aircraft' => 'Defect Aircraft Lead',
        'safety_manager' => 'Defect Safety Manager',
        'security_coordinator' => 'Defect Security Coordinator',
        'regulatory_source' => 'YAW Phase 2 defect tenancy verification',
        'regulatory_source_version' => 'TR-010',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Defect test operator scope.',
        'responsible_role' => 'Accountable Manager',
    ]);
}

function defectUser(array $permissions = ['missions.view', 'missions.create', 'missions.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'defect_operator_'.str()->random(8),
        'label' => 'Defect Operator',
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

function defectAircraft(array $overrides = [], ?UasOperator $operator = null): UasAircraft
{
    $aircraft = UasAircraft::query()->create([
        'registration' => 'ZU-DEF-'.str()->upper(str()->random(4)),
        'manufacturer' => 'VMT',
        'model' => 'Surveyor Two',
        'serial_number' => 'SN-DEF-'.str()->upper(str()->random(8)),
        'aircraft_category' => 'uas',
        'operational_status' => 'active_serviceable',
        ...$overrides,
    ]);

    if ($operator) {
        $operator->aircraft()->attach($aircraft->id, [
            'assignment_role' => 'operated_aircraft',
            'status' => 'active',
        ]);
    }

    return $aircraft;
}

function defectPilot(): UasPilot
{
    $pilot = UasPilot::query()->create([
        'first_name' => 'Naledi',
        'last_name' => 'Dlamini',
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
        'certificate_number' => 'RPC-DEF-'.fake()->unique()->numberBetween(1000, 9999),
        'issue_date' => now()->subYear()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'status' => 'valid',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
    ]);

    return $pilot;
}

function defectMission(UasAircraft $aircraft, array $overrides = [], ?UasOperator $operator = null): UasMission
{
    $pilot = defectPilot();

    AircraftRegistration::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'registration_number' => $aircraft->registration,
        'lifecycle_state' => 'initial',
        'issue_date' => now()->subMonth()->toDateString(),
    ]);

    AircraftApproval::query()->create([
        'uas_aircraft_id' => $aircraft->id,
        'approval_type' => 'uasla',
        'approval_number' => 'UASLA-DEF-'.fake()->unique()->numberBetween(1000, 9999),
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'status' => 'valid',
    ]);

    return UasMission::query()->create([
        'mission_number' => 'MIS-DEF-'.str()->upper(str()->random(5)),
        'purpose' => 'Defect management proof',
        'client_project' => 'Phase 2 verification',
        'location' => 'Defect test range',
        'operation_category' => 'inspection',
        'uas_operator_id' => $operator?->id,
        'uas_aircraft_id' => $aircraft->id,
        'uas_pilot_id' => $pilot->id,
        'planned_start_at' => now()->addHour(),
        'planned_end_at' => now()->addHours(2),
        'maximum_altitude_ft' => 300,
        'planned_distance_km' => 1.5,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => null,
        'airspace_assessment' => null,
        'approvals' => [],
        'risk_assessment' => ['overall' => 'low'],
        'emergency_arrangements' => null,
        'lifecycle_state' => 'in_progress',
        'release_gate_state' => 'green',
        'release_gate_results' => ['state' => 'green', 'checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-DEF-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Defect management verification mission.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function defectPayload(array $overrides = []): array
{
    return [
        'source' => 'post_flight',
        'severity' => 'ground_aircraft',
        'title' => 'Motor arm crack observed',
        'description' => 'Visible crack on the front-left motor arm after landing inspection.',
        'immediate_action' => 'Aircraft removed from service pending maintenance review.',
        'reported_at' => '2026-09-10 12:00:00',
        'evidence_references' => ['post-flight-photo-001'],
        ...$overrides,
    ];
}

it('requires mission permissions for defect routes', function () {
    $this->withoutVite();

    $operator = defectOperator();
    $aircraft = defectAircraft([], $operator);
    $mission = defectMission($aircraft, [], $operator);
    $viewer = defectUser(['missions.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($viewer)->get('/defects')->assertOk();
    $this->actingAs($viewer)->get('/defects/create')->assertForbidden();
    $this->actingAs($viewer)->post('/defects', defectPayload(['uas_aircraft_id' => $aircraft->id]))->assertForbidden();
    $this->actingAs($viewer)->get("/missions/{$mission->id}/defects/create")->assertForbidden();
    $this->actingAs($viewer)->post("/missions/{$mission->id}/defects", defectPayload())->assertForbidden();
});

it('reports a mission-linked grounding defect, audits it and blocks aircraft serviceability', function () {
    $operator = defectOperator();
    $user = defectUser([], $operator);
    $aircraft = defectAircraft(['registration' => 'ZU-DEF1'], $operator);
    $mission = defectMission($aircraft, [], $operator);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/defects", defectPayload())
        ->assertRedirect(route('missions.show', $mission));

    $defect = UasAircraftDefect::query()->firstOrFail();
    $aircraft->refresh();

    expect($defect->defect_number)->toStartWith('DEF-')
        ->and($defect->uas_mission_id)->toBe($mission->id)
        ->and($defect->uas_aircraft_id)->toBe($aircraft->id)
        ->and($defect->reported_by)->toBe($user->id)
        ->and($defect->source)->toBe('post_flight')
        ->and($defect->severity)->toBe('ground_aircraft')
        ->and($defect->serviceability_impact)->toBe('grounded')
        ->and($defect->evidence_references)->toBe(['post-flight-photo-001'])
        ->and($aircraft->operational_status)->toBe('grounded');

    $gate = app(MissionReleaseGate::class)->evaluate($mission->refresh());

    expect($gate['state'])->toBe('red')
        ->and(collect($gate['checks'])->where('label', 'Aircraft')->first()['result'])->toBe('red');

    $audit = UasAuditEntry::query()->where('action', 'defect.reported')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasAircraftDefect::class)
        ->and($audit->auditable_id)->toBe($defect->id)
        ->and($audit->requirement_id)->toBe('FR-DEF-001');
});

it('reports top-level inspection defects and applies flight restricted serviceability impact', function () {
    $operator = defectOperator();
    $user = defectUser([], $operator);
    $aircraft = defectAircraft(['registration' => 'ZU-DEF2'], $operator);

    $this->actingAs($user)
        ->post('/defects', defectPayload([
            'uas_aircraft_id' => $aircraft->id,
            'source' => 'inspection',
            'severity' => 'flight_restricted',
            'title' => 'Payload mount vibration',
        ]))
        ->assertRedirect(route('defects.index'));

    $defect = UasAircraftDefect::query()->firstOrFail();

    expect($defect->source)->toBe('inspection')
        ->and($defect->serviceability_impact)->toBe('flight_restricted')
        ->and($aircraft->refresh()->operational_status)->toBe('flight_restricted');
});

it('validates documented defect source and severity values', function () {
    $operator = defectOperator();
    $user = defectUser([], $operator);
    $aircraft = defectAircraft();

    $this->actingAs($user)
        ->post('/defects', defectPayload([
            'uas_aircraft_id' => $aircraft->id,
            'source' => 'dispatch',
            'severity' => 'critical',
            'title' => '',
        ]))
        ->assertInvalid(['source', 'severity', 'title']);
});

it('exposes defect reports through Inertia and mission defect summaries', function () {
    $this->withoutVite();

    $operator = defectOperator();
    $user = defectUser([], $operator);
    $aircraft = defectAircraft(['registration' => 'ZU-DEF5'], $operator);
    $mission = defectMission($aircraft, [], $operator);

    $this->actingAs($user)
        ->get('/defects/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('defects/create')
            ->has('sources.post_flight')
            ->has('severities.ground_aircraft')
        );

    $this->actingAs($user)
        ->get("/missions/{$mission->id}/defects/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('defects/create')
            ->where('mission.mission_number', $mission->mission_number)
        );

    $this->actingAs($user)->post("/missions/{$mission->id}/defects", defectPayload(['severity' => 'minor']));

    $report = app(MissionDefectReport::class)->execute($mission->refresh());

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['open'])->toBe(1)
        ->and($report['summary']['serviceability_impacts'])->toBe(0)
        ->and($report['defects'][0]['title'])->toBe('Motor arm crack observed');

    $this->actingAs($user)
        ->get('/defects')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('defects/index')
            ->where('defects.0.title', 'Motor arm crack observed')
        );

    $this->actingAs($user)
        ->get("/missions/{$mission->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/show')
            ->where('defects.summary.total', 1)
            ->where('defects.defects.0.title', 'Motor arm crack observed')
        );
});
