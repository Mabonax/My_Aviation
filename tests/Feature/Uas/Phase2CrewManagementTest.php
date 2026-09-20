<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Crew\Domain\Models\UasMissionCrewMember;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crewOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Crew Operator '.str()->upper(str()->random(5)),
        'registration_number' => 'CREW-'.str()->upper(str()->random(5)),
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'regulatory_source' => 'YAW TR-010 crew tenancy verification',
        'regulatory_source_version' => 'TR-010',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Phase 2 tenant-aware test fixture.',
        'responsible_role' => 'Accountable Manager',
    ]);
}

function crewMission(array $overrides = [], ?UasOperator $operator = null): UasMission
{
    return UasMission::query()->create([
        'mission_number' => 'MIS-CREW-001',
        'purpose' => 'Crew management proof',
        'client_project' => 'Phase 2 verification',
        'location' => 'Crew test range',
        'operation_category' => 'inspection',
        'uas_operator_id' => $operator?->id,
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
        'lifecycle_state' => 'planning',
        'release_gate_state' => 'amber',
        'release_gate_results' => ['state' => 'amber', 'checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CREW-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission crew assignment verification mission.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function crewUser(array $permissions = ['missions.view', 'missions.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'crew_manager',
        'label' => 'Crew Manager',
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

function crewPilot(): UasPilot
{
    return UasPilot::query()->create([
        'first_name' => 'Lebo',
        'last_name' => 'Nkosi',
        'email' => fake()->unique()->safeEmail(),
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Pilot option for crew management tests.',
        'responsible_role' => 'Compliance Manager',
    ]);
}

function crewPayload(array $overrides = []): array
{
    return [
        'crew_role' => 'observer',
        'display_name' => 'Mpho Dlamini',
        'email' => 'mpho.crew@example.test',
        'phone' => '+27110000000',
        'uas_pilot_id' => null,
        'user_id' => null,
        'briefing_status' => 'briefed',
        'competency_status' => 'verified',
        'acceptance_status' => 'accepted',
        'emergency_contact_name' => 'Nomsa Dlamini',
        'emergency_contact_phone' => '+27119999999',
        'notes' => 'Observer assigned to eastern perimeter.',
        ...$overrides,
    ];
}

it('requires mission update permission for crew assignment routes', function () {
    $this->withoutVite();

    $operator = crewOperator();
    $mission = crewMission([], $operator);
    $viewer = crewUser(['missions.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($viewer)->get("/missions/{$mission->id}/crew/create")->assertForbidden();
    $this->actingAs($viewer)->post("/missions/{$mission->id}/crew", crewPayload())->assertForbidden();
});

it('assigns a dedicated mission crew member with briefing competency acceptance and audit evidence', function () {
    $user = crewUser();
    $pilot = crewPilot();
    $linkedUser = User::factory()->create(['name' => 'Crew Linked User']);
    $mission = crewMission();

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/crew", crewPayload([
            'crew_role' => 'visual_observer',
            'uas_pilot_id' => $pilot->id,
            'user_id' => $linkedUser->id,
        ]))
        ->assertRedirect(route('missions.show', $mission));

    $member = UasMissionCrewMember::query()->firstOrFail();

    expect($member->uas_mission_id)->toBe($mission->id)
        ->and($member->assigned_by)->toBe($user->id)
        ->and($member->uas_pilot_id)->toBe($pilot->id)
        ->and($member->user_id)->toBe($linkedUser->id)
        ->and($member->crew_role)->toBe('visual_observer')
        ->and($member->briefing_status)->toBe('briefed')
        ->and($member->competency_status)->toBe('verified')
        ->and($member->acceptance_status)->toBe('accepted')
        ->and($member->emergency_contact_name)->toBe('Nomsa Dlamini')
        ->and($member->regulatory_source)->toContain('FR-CREW-001');

    $audit = UasAuditEntry::query()->where('action', 'mission_crew.assigned')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasMissionCrewMember::class)
        ->and($audit->auditable_id)->toBe($member->id)
        ->and($audit->requirement_id)->toBe('FR-CREW-001');
});

it('exposes crew assignment options and mission crew summary on mission screens', function () {
    $this->withoutVite();

    $user = crewUser();
    crewPilot();
    $mission = crewMission();

    $this->actingAs($user)
        ->get("/missions/{$mission->id}/crew/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/crew/create')
            ->has('options.crew_roles.visual_observer')
            ->has('options.pilots', 1)
            ->where('crew.summary.total', 0)
        );

    $this->actingAs($user)->post("/missions/{$mission->id}/crew", crewPayload());

    $this->actingAs($user)
        ->get("/missions/{$mission->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/show')
            ->where('crew.summary.total', 1)
            ->where('crew.summary.briefed', 1)
            ->where('crew.summary.accepted', 1)
            ->where('crew.members.0.display_name', 'Mpho Dlamini')
        );
});

it('tracks crew attention when briefing acceptance or competency evidence is incomplete', function () {
    $operator = crewOperator();
    $user = crewUser([], $operator);
    $mission = crewMission([], $operator);

    $this->actingAs($user)->post("/missions/{$mission->id}/crew", crewPayload([
        'briefing_status' => 'pending',
        'competency_status' => 'expired',
        'acceptance_status' => 'pending',
    ]));

    $report = app(App\Domains\Uas\Crew\Application\Queries\MissionCrewReport::class)->execute($mission->refresh());

    expect($report['summary']['total'])->toBe(1)
        ->and($report['summary']['attention_required'])->toBe(1)
        ->and($report['summary']['competency_verified'])->toBe(0);
});