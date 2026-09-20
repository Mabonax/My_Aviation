<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Batteries\Application\Queries\MissionBatteryReport;
use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Batteries\Domain\Models\UasMissionBatteryUsage;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function batteryUser(array $permissions = ['missions.view', 'missions.create', 'missions.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'battery_operator_'.str()->random(8),
        'label' => 'Battery Operator',
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

function batteryOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'Battery Operator '.str()->upper(str()->random(5)),
        'trading_name' => 'Battery Operator',
        'status' => 'active',
        'accountable_manager' => 'Battery Accountable Manager',
        'responsible_person_flight_operations' => 'Battery Flight Operations',
        'responsible_person_aircraft' => 'Battery Aircraft Lead',
        'regulatory_source' => 'FR-BAT-001 tenancy fixture',
        'regulatory_source_version' => 'v1',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'Battery management tenant verification.',
        'responsible_role' => 'Operations Manager',
    ]);
}

function batteryAircraft(array $overrides = []): UasAircraft
{
    return UasAircraft::query()->create([
        'registration' => 'ZU-BAT-'.str()->upper(str()->random(4)),
        'manufacturer' => 'DJI',
        'model' => 'Matrice 350 RTK',
        'serial_number' => 'AC-BAT-'.str()->upper(str()->random(8)),
        'aircraft_category' => 'uas',
        'operational_status' => 'active',
        ...$overrides,
    ]);
}

function batteryMission(?UasOperator $operator = null, array $overrides = []): UasMission
{
    return UasMission::query()->create([
        'uas_operator_id' => $operator?->id,
        'mission_number' => 'MIS-BAT-'.str()->upper(str()->random(5)),
        'purpose' => 'Battery management proof',
        'client_project' => 'Phase 2 verification',
        'location' => 'Battery test range',
        'operation_category' => 'inspection',
        'uas_aircraft_id' => null,
        'uas_pilot_id' => null,
        'planned_start_at' => now()->addHour(),
        'planned_end_at' => now()->addHours(2),
        'maximum_altitude_ft' => 300,
        'planned_distance_km' => 1.5,
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
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-BAT-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Battery management verification mission.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function batteryPayload(array $overrides = []): array
{
    return [
        'battery_uid' => 'BAT-'.str()->upper(str()->random(6)),
        'manufacturer' => 'DJI',
        'model' => 'TB65',
        'serial_number' => 'SN-BAT-'.str()->upper(str()->random(8)),
        'compatible_uas_aircraft_id' => null,
        'cycle_count' => 89,
        'maximum_cycles' => 100,
        'acquisition_date' => '2026-01-15',
        'damage_incidents' => null,
        'retirement_status' => 'active',
        'charge_history' => ['2026-09-09 full charge'],
        'evidence_references' => ['battery-register-row-1'],
        ...$overrides,
    ];
}

function storedBattery(array $overrides = []): UasBattery
{
    return UasBattery::query()->create([
        ...batteryPayload(),
        'health_status' => 'serviceable',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-BAT-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Electric UAS battery inventory, health, cycles, charge history and retirement management.',
        ...$overrides,
    ]);
}

it('requires the relevant mission permissions for battery inventory and usage routes', function () {
    $this->withoutVite();

    $operator = batteryOperator();
    $mission = batteryMission($operator);
    $viewer = batteryUser(['missions.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($viewer)->get('/batteries')->assertOk();
    $this->actingAs($viewer)->get('/batteries/create')->assertForbidden();
    $this->actingAs($viewer)->post('/batteries', batteryPayload())->assertForbidden();
    $this->actingAs($viewer)->get("/missions/{$mission->id}/batteries/create")->assertForbidden();
    $this->actingAs($viewer)->post("/missions/{$mission->id}/batteries", ['uas_battery_id' => 1])->assertForbidden();
});

it('creates battery inventory records with derived health and audit evidence for FR-BAT-001', function () {
    $operator = batteryOperator();
    $user = batteryUser([], $operator);
    $aircraft = batteryAircraft(['registration' => 'ZU-BAT1']);

    $this->actingAs($user)
        ->post('/batteries', batteryPayload([
            'battery_uid' => 'BAT-001',
            'serial_number' => 'TB65-0001',
            'compatible_uas_aircraft_id' => $aircraft->id,
            'cycle_count' => 90,
            'maximum_cycles' => 100,
        ]))
        ->assertRedirect(route('batteries.index'));

    $battery = UasBattery::query()->where('battery_uid', 'BAT-001')->firstOrFail();

    expect($battery->manufacturer)->toBe('DJI')
        ->and($battery->serial_number)->toBe('TB65-0001')
        ->and($battery->compatible_uas_aircraft_id)->toBe($aircraft->id)
        ->and($battery->health_status)->toBe('cycle_watch')
        ->and($battery->charge_history)->toBe(['2026-09-09 full charge'])
        ->and($battery->regulatory_source)->toContain('FR-BAT-001');

    $audit = UasAuditEntry::query()->where('action', 'battery.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasBattery::class)
        ->and($audit->auditable_id)->toBe($battery->id)
        ->and($audit->requirement_id)->toBe('FR-BAT-001');
});

it('records mission battery usage, increments cycles and exposes mission battery summaries', function () {
    $this->withoutVite();

    $operator = batteryOperator();
    $user = batteryUser([], $operator);
    $aircraft = batteryAircraft(['registration' => 'ZU-BAT2']);
    $mission = batteryMission($operator, ['uas_aircraft_id' => $aircraft->id]);
    $battery = storedBattery([
        'battery_uid' => 'BAT-002',
        'serial_number' => 'TB65-0002',
        'compatible_uas_aircraft_id' => $aircraft->id,
        'cycle_count' => 10,
        'maximum_cycles' => 100,
    ]);

    $this->actingAs($user)
        ->post("/missions/{$mission->id}/batteries", [
            'uas_battery_id' => $battery->id,
            'cycles_added' => 2,
            'state_of_charge_start' => 96,
            'state_of_charge_end' => 31,
            'used_at' => '2026-09-10 10:30:00',
            'notes' => 'Primary battery pair used for sortie.',
        ])
        ->assertRedirect(route('missions.show', $mission));

    $usage = UasMissionBatteryUsage::query()->firstOrFail();
    $battery->refresh();

    expect($usage->uas_mission_id)->toBe($mission->id)
        ->and($usage->recorded_by)->toBe($user->id)
        ->and($usage->cycles_added)->toBe(2)
        ->and($usage->state_of_charge_start)->toBe(96)
        ->and($usage->state_of_charge_end)->toBe(31)
        ->and($battery->cycle_count)->toBe(12)
        ->and($battery->last_used_at->toDateString())->toBe('2026-09-10')
        ->and($battery->health_status)->toBe('serviceable');

    $report = app(MissionBatteryReport::class)->execute($mission->refresh());

    expect($report['summary']['total_batteries'])->toBe(1)
        ->and($report['summary']['cycles_added'])->toBe(2)
        ->and($report['summary']['attention_required'])->toBe(0)
        ->and($report['usages'][0]['battery_uid'])->toBe('BAT-002');

    $audit = UasAuditEntry::query()->where('action', 'battery_usage.recorded')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasMissionBatteryUsage::class)
        ->and($audit->auditable_id)->toBe($usage->id)
        ->and($audit->requirement_id)->toBe('FR-BAT-001');

    $this->actingAs($user)
        ->get("/missions/{$mission->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/show')
            ->where('batteries.summary.total_batteries', 1)
            ->where('batteries.usages.0.battery_uid', 'BAT-002')
        );
});

it('blocks mission usage when a battery is incompatible with the mission aircraft', function () {
    $operator = batteryOperator();
    $user = batteryUser([], $operator);
    $missionAircraft = batteryAircraft(['registration' => 'ZU-BAT3']);
    $otherAircraft = batteryAircraft(['registration' => 'ZU-BAT4']);
    $mission = batteryMission($operator, ['uas_aircraft_id' => $missionAircraft->id]);
    $battery = storedBattery(['compatible_uas_aircraft_id' => $otherAircraft->id]);

    $this->actingAs($user)
        ->from("/missions/{$mission->id}/batteries/create")
        ->post("/missions/{$mission->id}/batteries", [
            'uas_battery_id' => $battery->id,
            'cycles_added' => 1,
        ])
        ->assertRedirect("/missions/{$mission->id}/batteries/create")
        ->assertInvalid(['uas_battery_id']);
});

it('exposes battery inventory and mission battery capture pages through Inertia', function () {
    $this->withoutVite();

    $operator = batteryOperator();
    $user = batteryUser([], $operator);
    $aircraft = batteryAircraft(['registration' => 'ZU-BAT5']);
    $mission = batteryMission($operator, ['uas_aircraft_id' => $aircraft->id]);
    storedBattery([
        'battery_uid' => 'BAT-005',
        'serial_number' => 'TB65-0005',
        'compatible_uas_aircraft_id' => $aircraft->id,
    ]);

    $this->actingAs($user)
        ->get('/batteries')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('batteries/index')
            ->where('batteries.0.battery_uid', 'BAT-005')
        );

    $this->actingAs($user)
        ->get('/batteries/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('batteries/create')
            ->where('aircraft.0.label', 'ZU-BAT5 Matrice 350 RTK')
        );

    $this->actingAs($user)
        ->get("/missions/{$mission->id}/batteries/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/batteries/create')
            ->where('batteries.available_batteries.0.label', 'BAT-005 / TB65-0005')
        );
});