<?php

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Models\User;

function pilotPayload(array $overrides = []): array
{
    return array_merge([
        'employee_number' => 'VMT-PIL-001',
        'first_name' => 'Anele',
        'last_name' => 'Mokoena',
        'preferred_name' => 'Anele',
        'email' => 'anele.mokoena@example.com',
        'phone' => '+27110000001',
        'nationality' => 'South African',
        'date_of_birth' => '1992-04-18',
        'sacaa_certificate_number' => 'RPC-001',
        'rpc_category' => 'multi_rotor',
        'ratings' => ['visual_line_of_sight'],
        'medical_status' => 'unverified',
        'radiotelephony_qualification' => 'restricted',
        'language_proficiency' => 'English level 6',
        'profile_status' => 'draft',
        'notes' => 'Initial Phase 1 pilot profile.',
    ], $overrides);
}

it('requires authentication to view pilot profiles', function () {
    $this->get('/pilots')->assertRedirect('/login');
});

it('creates a pilot profile with regulatory traceability metadata', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($user)->post('/pilots', pilotPayload());

    $pilot = UasPilot::query()->firstOrFail();

    $response->assertRedirect(route('pilots.show', $pilot));
    expect($pilot->first_name)->toBe('Anele')
        ->and($pilot->sacaa_certificate_number)->toBe('RPC-001')
        ->and($pilot->ratings)->toBe(['visual_line_of_sight'])
        ->and($pilot->regulatory_source)->toContain('FR-PIL-001')
        ->and($pilot->regulatory_source_version)->toBe('FRS v1.0, dated 2026-09-09')
        ->and($pilot->responsible_role)->toBe('Compliance Manager')
        ->and($pilot->created_by)->toBe($user->id)
        ->and($pilot->updated_by)->toBe($user->id);

    $auditEntry = UasAuditEntry::query()->firstOrFail();

    expect($auditEntry->action)->toBe('pilot.profile.created')
        ->and($auditEntry->auditable_type)->toBe(UasPilot::class)
        ->and($auditEntry->auditable_id)->toBe($pilot->id)
        ->and($auditEntry->requirement_id)->toBe('FR-PIL-001')
        ->and($auditEntry->previous_values)->toBeNull()
        ->and($auditEntry->new_values['first_name'])->toBe('Anele');
});

it('updates a pilot profile through the application action contract', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    $pilot = UasPilot::query()->create([
        ...pilotPayload(),
        'regulatory_source' => 'legacy',
        'regulatory_source_version' => 'legacy',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'legacy',
        'responsible_role' => 'legacy',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->put("/pilots/{$pilot->id}", pilotPayload([
            'first_name' => 'Lerato',
            'email' => 'lerato@example.com',
            'profile_status' => 'active',
        ]))
        ->assertRedirect(route('pilots.show', $pilot));

    $pilot->refresh();

    expect($pilot->first_name)->toBe('Lerato')
        ->and($pilot->email)->toBe('lerato@example.com')
        ->and($pilot->profile_status->value)->toBe('active')
        ->and($pilot->regulatory_source)->toContain('FR-PIL-001');

    $auditEntry = UasAuditEntry::query()->where('action', 'pilot.profile.updated')->firstOrFail();

    expect($auditEntry->previous_values['first_name'])->toBe('Anele')
        ->and($auditEntry->new_values['first_name'])->toBe('Lerato')
        ->and($auditEntry->requirement_id)->toBe('FR-PIL-001');
});

it('rejects duplicate pilot certificate numbers', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    UasPilot::query()->create([
        ...pilotPayload(),
        'regulatory_source' => 'Civil Aviation Regulations Part 71; UAS Compliance & Operations Platform FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Remote pilot profile master record for South African UAS operations managed in the VMT platform.',
        'responsible_role' => 'Compliance Manager',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/pilots', pilotPayload([
            'employee_number' => 'VMT-PIL-002',
            'email' => 'second@example.com',
        ]))
        ->assertInvalid(['sacaa_certificate_number']);
});

it('validates required pilot identity fields', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($user)
        ->post('/pilots', pilotPayload([
            'first_name' => '',
            'last_name' => '',
            'rpc_category' => 'unsupported',
        ]))
        ->assertInvalid(['first_name', 'last_name', 'rpc_category']);
});




it('enforces the pilot profile policy contract server side', function () {
    $user = User::factory()->create();
    $role = UasRole::query()->create([
        'name' => 'pilot-policy-manager',
        'label' => 'Pilot Policy Manager',
        'permissions' => ['pilots.view', 'pilots.create', 'pilots.update'],
    ]);
    $role->users()->attach($user);

    $pilot = UasPilot::query()->create([
        ...pilotPayload(),
        'regulatory_source' => 'Civil Aviation Regulations Part 71; UAS Compliance & Operations Platform FRS FR-PIL-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Remote pilot profile master record for South African UAS operations managed in the VMT platform.',
        'responsible_role' => 'Compliance Manager',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    expect($user->can('viewAny', UasPilot::class))->toBeTrue()
        ->and($user->can('create', UasPilot::class))->toBeTrue()
        ->and($user->can('view', $pilot))->toBeTrue()
        ->and($user->can('update', $pilot))->toBeTrue()
        ->and($user->can('delete', $pilot))->toBeFalse();
});
