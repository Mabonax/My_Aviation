<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function tenancyUser(array $permissions = [], array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    if ($permissions !== []) {
        $role = UasRole::query()->create([
            'name' => 'tenancy-'.uniqid(),
            'label' => 'Tenancy Test Role',
            'permissions' => $permissions,
        ]);

        $role->users()->attach($user);
    }

    return $user;
}

function tenancyOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create(array_merge([
        'legal_entity' => 'Operator '.str()->upper(str()->random(5)),
        'trading_name' => null,
        'registration_number' => 'TEN-'.str()->upper(str()->random(6)),
        'uasoc_number' => 'UASOC-TEN-'.str()->upper(str()->random(4)),
        'certificate_issue_date' => '2026-01-01',
        'certificate_expiry_date' => '2027-01-01',
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations Lead',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'operating_bases' => ['Midrand'],
        'approved_aircraft' => [],
        'approved_pilots' => [],
        'operations_specifications' => ['VLOS'],
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Operator tenancy and access scoping remediation.',
        'responsible_role' => 'Accountable Manager',
    ], $overrides));
}

function tenancyPilot(array $overrides = []): UasPilot
{
    return UasPilot::query()->create(array_merge([
        'first_name' => 'Tenant',
        'last_name' => 'Pilot',
        'email' => fake()->unique()->safeEmail(),
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Pilot assignment tenancy test record.',
        'responsible_role' => 'Compliance Manager',
    ], $overrides));
}

function tenancyAircraft(array $overrides = []): UasAircraft
{
    return UasAircraft::query()->create(array_merge([
        'registration' => 'ZT-TEN-'.str()->upper(str()->random(5)),
        'manufacturer' => 'VMT',
        'model' => 'Surveyor Two',
        'serial_number' => 'SN-TEN-'.str()->upper(str()->random(8)),
        'operational_status' => 'active_serviceable',
    ], $overrides));
}

function tenancyMission(UasOperator $operator, array $overrides = []): UasMission
{
    return UasMission::query()->create(array_merge([
        'mission_number' => 'MIS-TEN-'.str()->upper(str()->random(6)),
        'purpose' => 'Tenant scoped inspection',
        'location' => 'Midrand',
        'operation_category' => 'inspection',
        'uas_operator_id' => $operator->id,
        'planned_start_at' => now()->addDay(),
        'planned_end_at' => now()->addDay()->addHour(),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 2.5,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => 'Clear',
        'airspace_assessment' => 'Local site reviewed.',
        'risk_assessment' => ['overall' => 'low'],
        'emergency_arrangements' => 'Abort area briefed.',
        'lifecycle_state' => 'draft',
        'release_gate_state' => 'green',
        'release_gate_results' => ['checks' => []],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission tenancy test record.',
        'responsible_role' => 'Operations Manager',
    ], $overrides));
}

function tenancyMissionPayload(array $overrides = []): array
{
    return array_merge([
        'purpose' => 'Tenant scoped inspection',
        'location' => 'Midrand',
        'operation_category' => 'inspection',
        'planned_start_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'planned_end_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 2.5,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'weather' => 'Clear',
        'airspace_assessment' => 'Local site reviewed.',
        'risk_assessment' => ['overall' => 'low'],
        'emergency_arrangements' => 'Abort area briefed.',
    ], $overrides);
}

it('lets an administrator add a user to an operator with a validated membership role', function () {
    $admin = tenancyUser(['operators.view', 'operators.update']);
    $member = tenancyUser();
    $operator = tenancyOperator(['legal_entity' => 'Membership Operator']);

    $this->actingAs($admin)
        ->post(route('operators.memberships.store', $operator), [
            'user_id' => $member->id,
            'membership_role' => 'operations_manager',
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('uas_operator_memberships', [
        'uas_operator_id' => $operator->id,
        'user_id' => $member->id,
        'membership_role' => 'operations_manager',
        'status' => 'active',
    ]);
});

it('prevents duplicate active memberships and rejects invalid roles', function () {
    $admin = tenancyUser(['operators.view', 'operators.update']);
    $member = tenancyUser();
    $operator = tenancyOperator();

    $this->actingAs($admin)->post(route('operators.memberships.store', $operator), [
        'user_id' => $member->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('operators.memberships.store', $operator), [
        'user_id' => $member->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ])->assertInvalid(['user_id']);

    $this->actingAs($admin)->post(route('operators.memberships.store', $operator), [
        'user_id' => tenancyUser()->id,
        'membership_role' => 'invalid_role',
        'status' => 'active',
    ])->assertInvalid(['membership_role']);

    expect(UasOperatorMembership::query()->where('user_id', $member->id)->where('status', 'active')->count())->toBe(1);
});

it('removes normal operator access when a membership is suspended', function () {
    $admin = tenancyUser(['operators.view', 'operators.update']);
    $member = tenancyUser();
    $operator = tenancyOperator();
    $membership = UasOperatorMembership::query()->create([
        'uas_operator_id' => $operator->id,
        'user_id' => $member->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ]);

    $this->actingAs($member)->get(route('operators.show', $operator))->assertOk();

    $this->actingAs($admin)
        ->put(route('operator-memberships.status.update', $membership), ['status' => 'suspended'])
        ->assertRedirect();

    $this->actingAs($member)->get(route('operators.show', $operator))->assertForbidden();
});

it('isolates operator pages, aircraft and missions between active memberships', function () {
    $this->withoutVite();

    $member = tenancyUser();
    $operatorA = tenancyOperator(['legal_entity' => 'Operator A']);
    $operatorB = tenancyOperator(['legal_entity' => 'Operator B']);
    $aircraftA = tenancyAircraft(['registration' => 'ZT-A-001']);
    $aircraftB = tenancyAircraft(['registration' => 'ZT-B-001']);
    $pilotA = tenancyPilot(['email' => 'pilot.a@example.com']);
    $pilotB = tenancyPilot(['email' => 'pilot.b@example.com']);

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operatorA->id,
        'user_id' => $member->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ]);
    $operatorA->aircraft()->attach($aircraftA->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    $operatorB->aircraft()->attach($aircraftB->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    $operatorA->pilots()->attach($pilotA->id, ['assignment_role' => 'remote_pilot', 'status' => 'active']);
    $operatorB->pilots()->attach($pilotB->id, ['assignment_role' => 'remote_pilot', 'status' => 'active']);
    tenancyMission($operatorA, ['mission_number' => 'MIS-A-001', 'purpose' => 'Operator A mission']);
    tenancyMission($operatorB, ['mission_number' => 'MIS-B-001', 'purpose' => 'Operator B mission']);

    $this->actingAs($member)->get(route('operators.show', $operatorA))->assertOk();
    $this->actingAs($member)->get(route('operators.show', $operatorB))->assertForbidden();

    $this->actingAs($member)
        ->get(route('aircraft.index'))
        ->assertOk()
        ->assertSee('ZT-A-001')
        ->assertDontSee('ZT-B-001');

    $this->actingAs($member)
        ->get(route('missions.index'))
        ->assertOk()
        ->assertSee('MIS-A-001')
        ->assertDontSee('MIS-B-001');
});

it('allows a global administrator to access multiple operators and scoped records', function () {
    $this->withoutVite();

    $admin = tenancyUser([], ['role' => 'super_admin']);
    $operatorA = tenancyOperator(['legal_entity' => 'Global Operator A']);
    $operatorB = tenancyOperator(['legal_entity' => 'Global Operator B']);
    tenancyMission($operatorA, ['mission_number' => 'MIS-GLOBAL-A']);
    tenancyMission($operatorB, ['mission_number' => 'MIS-GLOBAL-B']);

    $this->actingAs($admin)->get(route('operators.show', $operatorA))->assertOk();
    $this->actingAs($admin)->get(route('operators.show', $operatorB))->assertOk();
    $this->actingAs($admin)->get(route('missions.index'))->assertOk()->assertSee('MIS-GLOBAL-A')->assertSee('MIS-GLOBAL-B');
});

it('keeps pilot self-service available after operator membership scoping', function () {
    $user = tenancyUser(['pilots.self-service']);

    $this->actingAs($user)
        ->post(route('my.pilot.store'), [
            'first_name' => 'Self',
            'last_name' => 'Service',
            'email' => 'self.service@example.com',
            'sacaa_certificate_number' => 'RPC-TEN-SELF',
            'rpc_category' => 'multi_rotor',
            'medical_status' => 'unverified',
            'radiotelephony_qualification' => 'restricted',
        ])
        ->assertRedirect(route('my.pilot.show'));
});

it('records audit entries for membership lifecycle and operator assignments', function () {
    $admin = tenancyUser(['operators.view', 'operators.update']);
    $member = tenancyUser();
    $operator = tenancyOperator();
    $pilot = tenancyPilot();
    $aircraft = tenancyAircraft();

    $this->actingAs($admin)->post(route('operators.memberships.store', $operator), [
        'user_id' => $member->id,
        'membership_role' => 'operations_manager',
        'status' => 'active',
    ])->assertRedirect();

    $membership = UasOperatorMembership::query()->firstOrFail();

    $this->actingAs($admin)->put(route('operator-memberships.status.update', $membership), ['status' => 'suspended'])->assertRedirect();
    $this->actingAs($admin)->put(route('operator-memberships.status.update', $membership), ['status' => 'ended'])->assertRedirect();
    $this->actingAs($admin)->post(route('operators.pilots.store', $operator), ['uas_pilot_id' => $pilot->id])->assertRedirect();
    $this->actingAs($admin)->post(route('operators.aircraft.store', $operator), ['uas_aircraft_id' => $aircraft->id])->assertRedirect();

    expect(UasAuditEntry::query()->where('action', 'membership.created')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'membership.activated')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'membership.suspended')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'membership.ended')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'pilot.assigned_to_operator')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'aircraft.assigned_to_operator')->exists())->toBeTrue();
});

it('blocks membership users from creating a mission with unauthorized operator pilot or aircraft combinations', function () {
    $member = tenancyUser();
    $operatorA = tenancyOperator();
    $operatorB = tenancyOperator();
    $pilotA = tenancyPilot(['email' => 'combo.a@example.com']);
    $pilotB = tenancyPilot(['email' => 'combo.b@example.com']);
    $aircraftA = tenancyAircraft(['registration' => 'ZT-COMBO-A']);
    $aircraftB = tenancyAircraft(['registration' => 'ZT-COMBO-B']);

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operatorA->id,
        'user_id' => $member->id,
        'membership_role' => 'operations_manager',
        'status' => 'active',
    ]);
    $operatorA->pilots()->attach($pilotA->id, ['assignment_role' => 'remote_pilot', 'status' => 'active']);
    $operatorA->aircraft()->attach($aircraftA->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    $operatorB->pilots()->attach($pilotB->id, ['assignment_role' => 'remote_pilot', 'status' => 'active']);
    $operatorB->aircraft()->attach($aircraftB->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);

    $this->actingAs($member)
        ->post(route('missions.store'), tenancyMissionPayload([
            'uas_operator_id' => $operatorA->id,
            'uas_pilot_id' => $pilotB->id,
            'uas_aircraft_id' => $aircraftB->id,
        ]))
        ->assertInvalid(['uas_pilot_id', 'uas_aircraft_id']);
});

it('keeps legacy operator JSON approval data readable for administrators', function () {
    $this->withoutVite();

    $admin = tenancyUser([], ['role' => 'super_admin']);
    $operator = tenancyOperator([
        'legal_entity' => 'Legacy JSON Operator',
        'approved_pilots' => [11, 12],
        'approved_aircraft' => [21],
    ]);

    $this->actingAs($admin)
        ->get(route('operators.show', $operator))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/show')
            ->where('operator.legal_entity', 'Legacy JSON Operator')
            ->where('operator.approved_aircraft.0', 21)
            ->where('operator.approved_pilots.0', 11)
        );
});
