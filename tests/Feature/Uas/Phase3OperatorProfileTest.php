<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function operatorUser(array $permissions = ['operators.view', 'operators.create', 'operators.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_ADMINISTRATOR): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'operator_admin_'.str()->random(8),
        'label' => 'Operator Admin',
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

function operatorAircraft(): UasAircraft
{
    return UasAircraft::query()->create([
        'registration' => 'ZU-OPS-'.str()->upper(str()->random(4)),
        'manufacturer' => 'VMT',
        'model' => 'Surveyor Two',
        'serial_number' => 'SN-OPS-'.str()->upper(str()->random(8)),
        'operational_status' => 'active_serviceable',
    ]);
}

function operatorPilot(): UasPilot
{
    return UasPilot::query()->create([
        'first_name' => 'Kabelo',
        'last_name' => 'Maseko',
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
}

function operatorPayload(array $overrides = []): array
{
    return [
        'legal_entity' => 'VMT UAS Operations (Pty) Ltd',
        'trading_name' => 'VMT UAS',
        'registration_number' => '2026/OPS/001',
        'uasoc_number' => 'UASOC-001',
        'certificate_issue_date' => '2026-01-10',
        'certificate_expiry_date' => '2027-01-09',
        'status' => 'active',
        'accountable_manager' => 'John Mabona',
        'responsible_person_flight_operations' => 'Flight Ops Lead',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Lead',
        'security_coordinator' => 'Security Lead',
        'operating_bases' => ['Midrand Base', 'Pretoria Field Office'],
        'approved_aircraft' => [],
        'approved_pilots' => [],
        'operations_specifications' => ['VLOS inspection', 'Day operations'],
        'evidence_references' => ['operator-file-001'],
        ...$overrides,
    ];
}

function storedOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create([
        ...operatorPayload(),
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'UAS operator profile, certificate holder identity, post holders, operating bases, approved aircraft, approved pilots and OpsSpecs.',
        'responsible_role' => 'Accountable Manager',
        ...$overrides,
    ]);
}

it('requires operator permissions for operator profile routes', function () {
    $this->withoutVite();

    $operator = storedOperator();
    $viewer = operatorUser(['operators.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);

    $this->actingAs($viewer)->get('/operators')->assertOk();
    $this->actingAs($viewer)->get("/operators/{$operator->id}")->assertOk();
    $this->actingAs($viewer)->get('/operators/create')->assertForbidden();
    $this->actingAs($viewer)->post('/operators', operatorPayload())->assertForbidden();
    $this->actingAs($viewer)->get("/operators/{$operator->id}/edit")->assertForbidden();
    $this->actingAs($viewer)->put("/operators/{$operator->id}", operatorPayload())->assertForbidden();
});

it('creates an operator profile with FR-OPS-001 traceability and audit evidence', function () {
    $user = operatorUser();
    $aircraft = operatorAircraft();
    $pilot = operatorPilot();

    $this->actingAs($user)
        ->post('/operators', operatorPayload([
            'approved_aircraft' => [$aircraft->id],
            'approved_pilots' => [$pilot->id],
        ]))
        ->assertRedirect();

    $operator = UasOperator::query()->firstOrFail();

    expect($operator->legal_entity)->toBe('VMT UAS Operations (Pty) Ltd')
        ->and($operator->uasoc_number)->toBe('UASOC-001')
        ->and($operator->status)->toBe('active')
        ->and($operator->approved_aircraft)->toBe([$aircraft->id])
        ->and($operator->approved_pilots)->toBe([$pilot->id])
        ->and($operator->operations_specifications)->toBe(['VLOS inspection', 'Day operations'])
        ->and($operator->regulatory_source)->toContain('FR-OPS-001')
        ->and($operator->responsible_role)->toBe('Accountable Manager')
        ->and($operator->created_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.profile.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasOperator::class)
        ->and($audit->auditable_id)->toBe($operator->id)
        ->and($audit->requirement_id)->toBe('FR-OPS-001');
});

it('updates an operator profile and records an audit trail', function () {
    $operator = storedOperator(['legal_entity' => 'Original Operator', 'uasoc_number' => 'UASOC-UPD']);
    $user = operatorUser([], $operator);

    $this->actingAs($user)
        ->put("/operators/{$operator->id}", operatorPayload([
            'legal_entity' => 'Updated Operator',
            'registration_number' => '2026/OPS/002',
            'uasoc_number' => 'UASOC-UPD',
            'status' => 'renewal_due',
        ]))
        ->assertRedirect(route('operators.show', $operator));

    $operator->refresh();

    expect($operator->legal_entity)->toBe('Updated Operator')
        ->and($operator->status)->toBe('renewal_due')
        ->and($operator->updated_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'operator.profile.updated')->firstOrFail();

    expect($audit->requirement_id)->toBe('FR-OPS-001')
        ->and($audit->previous_values['legal_entity'])->toBe('Original Operator')
        ->and($audit->new_values['legal_entity'])->toBe('Updated Operator');
});

it('validates operator identity, certificate dates and approval references', function () {
    $user = operatorUser();

    $this->actingAs($user)
        ->post('/operators', operatorPayload([
            'legal_entity' => '',
            'certificate_issue_date' => '2027-01-10',
            'certificate_expiry_date' => '2026-01-09',
            'status' => 'unknown_state',
            'approved_aircraft' => [999999],
        ]))
        ->assertInvalid(['legal_entity', 'certificate_expiry_date', 'status', 'approved_aircraft.0']);
});

it('exposes operator profile screens through Inertia', function () {
    $this->withoutVite();

    $aircraft = operatorAircraft();
    $pilot = operatorPilot();
    $operator = storedOperator([
        'legal_entity' => 'Visible Operator',
        'approved_aircraft' => [$aircraft->id],
        'approved_pilots' => [$pilot->id],
    ]);
    $user = operatorUser([], $operator);

    $this->actingAs($user)
        ->get('/operators')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/index')
            ->where('operators.0.legal_entity', 'Visible Operator')
        );

    $this->actingAs($user)
        ->get('/operators/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/create')
            ->has('options.statuses.active')
            ->where('options.aircraft.0.label', $aircraft->registration.' Surveyor Two')
            ->where('options.pilots.0.label', $pilot->display_name)
        );

    $this->actingAs($user)
        ->get("/operators/{$operator->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/show')
            ->where('operator.legal_entity', 'Visible Operator')
            ->where('operator.approved_aircraft.0', $aircraft->id)
        );

    $this->actingAs($user)
        ->get("/operators/{$operator->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('operators/edit')
            ->where('operator.legal_entity', 'Visible Operator')
        );
});