<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

function apiUser(array $permissions = [], array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    if ($permissions !== []) {
        $role = UasRole::query()->create([
            'name' => 'api-v1-'.uniqid(),
            'label' => 'API V1 Test Role',
            'permissions' => $permissions,
        ]);

        $role->users()->attach($user);
    }

    return $user;
}

function apiOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create(array_merge([
        'legal_entity' => 'API Operator '.str()->upper(str()->random(5)),
        'trading_name' => null,
        'registration_number' => 'API-'.str()->upper(str()->random(6)),
        'uasoc_number' => 'UASOC-API-'.str()->upper(str()->random(4)),
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
        'regulatory_applicability' => 'API V1 operator tenancy test record.',
        'responsible_role' => 'Accountable Manager',
    ], $overrides));
}

function apiPilot(array $overrides = []): UasPilot
{
    return UasPilot::query()->create(array_merge([
        'first_name' => 'API',
        'last_name' => 'Pilot',
        'email' => fake()->unique()->safeEmail(),
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'Civil Aviation Regulations Part 71; FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'API V1 pilot test record.',
        'responsible_role' => 'Compliance Manager',
    ], $overrides));
}

function apiAircraft(array $overrides = []): UasAircraft
{
    return UasAircraft::query()->create(array_merge([
        'registration' => 'ZT-API-'.str()->upper(str()->random(5)),
        'manufacturer' => 'VMT',
        'model' => 'Surveyor Two',
        'serial_number' => 'SN-API-'.str()->upper(str()->random(8)),
        'operational_status' => 'active_serviceable',
    ], $overrides));
}

function apiMission(UasOperator $operator, array $overrides = []): UasMission
{
    return UasMission::query()->create(array_merge([
        'mission_number' => 'MIS-API-'.str()->upper(str()->random(6)),
        'purpose' => 'API scoped inspection',
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
        'regulatory_applicability' => 'API V1 mission test record.',
        'responsible_role' => 'Operations Manager',
    ], $overrides));
}

it('returns the API V1 envelope for unauthenticated requests', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'unauthenticated')
        ->assertJsonPath('meta.contract_version', 'v1.0');
});

it('issues and revokes Sanctum tokens through the API V1 auth contract', function () {
    $user = User::factory()->create(['email' => 'api.login@example.com']);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'api.login@example.com',
        'password' => 'password',
        'device_name' => 'Pest API Device',
    ]);

    $login
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('meta.contract_version', 'v1.0');

    expect($login->json('data.access_token'))->toBeString()->not->toBeEmpty();
    expect(DB::table('personal_access_tokens')->count())->toBe(1);

    $this->withToken($login->json('data.access_token'))
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(DB::table('personal_access_tokens')->count())->toBe(0);
});

it('returns current user, pilot and active operator memberships', function () {
    $user = apiUser();
    $pilot = apiPilot(['user_id' => $user->id, 'first_name' => 'Linked', 'last_name' => 'Pilot']);
    $activeOperator = apiOperator(['legal_entity' => 'Active API Operator']);
    $suspendedOperator = apiOperator(['legal_entity' => 'Suspended API Operator']);

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $activeOperator->id,
        'user_id' => $user->id,
        'membership_role' => 'operations_manager',
        'status' => 'active',
    ]);
    UasOperatorMembership::query()->create([
        'uas_operator_id' => $suspendedOperator->id,
        'user_id' => $user->id,
        'membership_role' => 'operations_manager',
        'status' => 'suspended',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('meta.contract_version', 'v1.0');

    $this->getJson('/api/v1/me/pilot')
        ->assertOk()
        ->assertJsonPath('data.pilot.id', $pilot->id)
        ->assertJsonPath('data.pilot.display_name', 'Linked Pilot');

    $this->getJson('/api/v1/me/operators')
        ->assertOk()
        ->assertJsonPath('data.operators.0.legal_entity', 'Active API Operator')
        ->assertJsonMissing(['legal_entity' => 'Suspended API Operator']);
});

it('scopes aircraft and missions to active operator memberships', function () {
    $member = apiUser();
    $operatorA = apiOperator(['legal_entity' => 'API Operator A']);
    $operatorB = apiOperator(['legal_entity' => 'API Operator B']);
    $aircraftA = apiAircraft(['registration' => 'ZT-API-A']);
    $aircraftB = apiAircraft(['registration' => 'ZT-API-B']);
    apiMission($operatorA, ['mission_number' => 'MIS-API-A', 'purpose' => 'Operator A mission']);
    apiMission($operatorB, ['mission_number' => 'MIS-API-B', 'purpose' => 'Operator B mission']);

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operatorA->id,
        'user_id' => $member->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ]);
    $operatorA->aircraft()->attach($aircraftA->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    $operatorB->aircraft()->attach($aircraftB->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);

    Sanctum::actingAs($member);

    $this->getJson('/api/v1/aircraft')
        ->assertOk()
        ->assertJsonPath('data.aircraft.0.registration', 'ZT-API-A')
        ->assertJsonMissing(['registration' => 'ZT-API-B']);

    $this->getJson('/api/v1/missions')
        ->assertOk()
        ->assertJsonPath('data.missions.0.mission_number', 'MIS-API-A')
        ->assertJsonMissing(['mission_number' => 'MIS-API-B']);
});

it('allows a global API user to see all operators aircraft and missions', function () {
    $admin = apiUser([], ['role' => 'super_admin']);
    $operatorA = apiOperator(['legal_entity' => 'Global API Operator A']);
    $operatorB = apiOperator(['legal_entity' => 'Global API Operator B']);
    $globalAircraftA = apiAircraft(['registration' => 'ZT-GLOBAL-A']);
    $globalAircraftB = apiAircraft(['registration' => 'ZT-GLOBAL-B']);
    $operatorA->aircraft()->attach($globalAircraftA->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    $operatorB->aircraft()->attach($globalAircraftB->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    apiMission($operatorA, ['mission_number' => 'MIS-GLOBAL-API-A']);
    apiMission($operatorB, ['mission_number' => 'MIS-GLOBAL-API-B']);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/me/operators')
        ->assertOk()
        ->assertJsonFragment(['legal_entity' => 'Global API Operator A'])
        ->assertJsonFragment(['legal_entity' => 'Global API Operator B'])
        ->assertJsonPath('data.operators.0.membership_status', 'global');

    $this->withHeader('X-YAW-Operator', (string) $operatorA->id)
        ->getJson('/api/v1/aircraft')
        ->assertOk()
        ->assertJsonFragment(['registration' => 'ZT-GLOBAL-A'])
        ->assertJsonMissing(['registration' => 'ZT-GLOBAL-B']);

    $this->withHeader('X-YAW-Operator', (string) $operatorB->id)
        ->getJson('/api/v1/missions')
        ->assertOk()
        ->assertJsonFragment(['mission_number' => 'MIS-GLOBAL-API-B'])
        ->assertJsonMissing(['mission_number' => 'MIS-GLOBAL-API-A']);
});

it('returns the API V1 envelope for validation failures', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'not-an-email',
        'password' => '',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'validation_failed')
        ->assertJsonPath('meta.contract_version', 'v1.0')
        ->assertJsonValidationErrors(['email', 'password']);
});


it('resolves a single active operator automatically and exposes the canonical header', function () {
    $user = apiUser();
    $operator = apiOperator(['legal_entity' => 'Context Operator']);

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operator->id,
        'user_id' => $user->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me/operator-context')
        ->assertOk()
        ->assertJsonPath('data.operator_context.operator.id', $operator->id)
        ->assertJsonPath('data.operator_context.membership.role', 'remote_pilot')
        ->assertJsonPath('data.operator_context.selection_required', false)
        ->assertJsonPath('data.operator_context.header', 'X-YAW-Operator');
});

it('requires explicit selection when a user belongs to multiple operators', function () {
    $user = apiUser();
    $operatorA = apiOperator(['legal_entity' => 'Context A']);
    $operatorB = apiOperator(['legal_entity' => 'Context B']);

    foreach ([$operatorA, $operatorB] as $operator) {
        UasOperatorMembership::query()->create([
            'uas_operator_id' => $operator->id,
            'user_id' => $user->id,
            'membership_role' => 'remote_pilot',
            'status' => 'active',
        ]);
    }

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me/operator-context')
        ->assertOk()
        ->assertJsonPath('data.operator_context.operator', null)
        ->assertJsonPath('data.operator_context.selection_required', true);

    $this->withHeader('X-YAW-Operator', (string) $operatorB->id)
        ->getJson('/api/v1/me/operator-context')
        ->assertOk()
        ->assertJsonPath('data.operator_context.operator.id', $operatorB->id)
        ->assertJsonPath('data.operator_context.selection_required', false);
});

it('rejects an operator context outside the authenticated users active memberships', function () {
    $user = apiUser();
    $allowed = apiOperator();
    $forbidden = apiOperator();

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $allowed->id,
        'user_id' => $user->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ]);

    Sanctum::actingAs($user);

    $this->withHeader('X-YAW-Operator', (string) $forbidden->id)
        ->getJson('/api/v1/me/operator-context')
        ->assertForbidden()
        ->assertJsonPath('error', 'operator_context_forbidden');
});


it('requires an operator context for operational API requests with multiple memberships', function () {
    $user = apiUser();
    $operatorA = apiOperator();
    $operatorB = apiOperator();

    foreach ([$operatorA, $operatorB] as $operator) {
        UasOperatorMembership::query()->create([
            'uas_operator_id' => $operator->id,
            'user_id' => $user->id,
            'membership_role' => 'remote_pilot',
            'status' => 'active',
        ]);
    }

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/aircraft')->assertStatus(409);
    $this->getJson('/api/v1/missions')->assertStatus(409);
    $this->getJson('/api/v1/defects')->assertStatus(409);
    $this->getJson('/api/v1/batteries')->assertStatus(409);
    $this->getJson('/api/v1/gis-projects')->assertStatus(409);
    $this->getJson('/api/v1/compliance/findings')->assertStatus(409);
    $this->getJson('/api/v1/evidence-documents')->assertStatus(409);
});

it('does not expose a mission from another active membership while operating in the selected tenant', function () {
    $user = apiUser();
    $operatorA = apiOperator();
    $operatorB = apiOperator();
    $missionA = apiMission($operatorA);
    $missionB = apiMission($operatorB);

    foreach ([$operatorA, $operatorB] as $operator) {
        UasOperatorMembership::query()->create([
            'uas_operator_id' => $operator->id,
            'user_id' => $user->id,
            'membership_role' => 'remote_pilot',
            'status' => 'active',
        ]);
    }

    Sanctum::actingAs($user);

    $this->withHeader('X-YAW-Operator', (string) $operatorA->id)
        ->getJson('/api/v1/missions/'.$missionA->id)
        ->assertOk();

    $this->withHeader('X-YAW-Operator', (string) $operatorA->id)
        ->getJson('/api/v1/missions/'.$missionB->id)
        ->assertNotFound();
});
