<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Pilots\Application\Queries\CurrentPilotProfile;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;

function remediationPilotRoleUser(array $permissions = ['pilots.self-service']): User
{
    $user = User::factory()->create();
    $role = UasRole::query()->create([
        'name' => 'remediation-'.uniqid(),
        'label' => 'Remote Pilot',
        'permissions' => $permissions,
    ]);
    $role->users()->attach($user);

    return $user;
}

function remediationPilotPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Anele',
        'last_name' => 'Mokoena',
        'preferred_name' => 'Anele',
        'email' => 'anele.remediation@example.com',
        'phone' => '+27110000001',
        'nationality' => 'South African',
        'date_of_birth' => '1992-04-18',
        'sacaa_certificate_number' => 'RPC-REM-001',
        'rpc_category' => 'multi_rotor',
        'ratings' => ['visual_line_of_sight'],
        'language_proficiency' => 'English level 6',
        'notes' => 'Self-service remediation pilot profile.',
    ], $overrides);
}

function remediationLegacyPilot(array $overrides = []): UasPilot
{
    return UasPilot::query()->create(array_merge([
        'user_id' => null,
        'employee_number' => null,
        'first_name' => 'Legacy',
        'last_name' => 'Pilot',
        'preferred_name' => null,
        'email' => null,
        'phone' => null,
        'nationality' => null,
        'date_of_birth' => null,
        'sacaa_certificate_number' => 'RPC-LEGACY-001',
        'rpc_category' => 'unknown',
        'ratings' => [],
        'medical_status' => 'unverified',
        'radiotelephony_qualification' => 'unverified',
        'language_proficiency' => null,
        'training_history' => [],
        'examiner_records' => [],
        'operator_affiliations' => [],
        'supporting_document_references' => [],
        'profile_status' => 'draft',
        'regulatory_source' => 'legacy',
        'regulatory_source_version' => 'legacy',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'legacy',
        'responsible_role' => 'Compliance Manager',
        'notes' => null,
    ], $overrides));
}

it('requires authentication and pilot self-service permission for my pilot routes', function () {
    $this->get('/my/pilot')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/my/pilot')
        ->assertForbidden();
});

it('lets a pilot-role user without a profile create their own pilot profile', function () {
    $user = remediationPilotRoleUser();

    $this->actingAs($user)
        ->post('/my/pilot', remediationPilotPayload())
        ->assertRedirect(route('my.pilot.show'));

    $pilot = UasPilot::query()->firstOrFail();

    expect($pilot->user_id)->toBe($user->id)
        ->and($pilot->first_name)->toBe('Anele')
        ->and($pilot->profile_status->value)->toBe('draft')
        ->and(app(CurrentPilotProfile::class)->resolve($user)?->id)->toBe($pilot->id);
});

it('ignores client-submitted user and privileged status values during self-service creation', function () {
    $user = remediationPilotRoleUser();
    $otherUser = User::factory()->create();

    $this->actingAs($user)
        ->post('/my/pilot', remediationPilotPayload([
            'user_id' => $otherUser->id,
            'employee_number' => 'SHOULD-NOT-PERSIST',
            'profile_status' => 'active',
        ]))
        ->assertRedirect(route('my.pilot.show'));

    $pilot = UasPilot::query()->firstOrFail();

    expect($pilot->user_id)->toBe($user->id)
        ->and($pilot->employee_number)->toBeNull()
        ->and($pilot->profile_status->value)->toBe('draft');
});

it('prevents a user from creating a second pilot profile', function () {
    $user = remediationPilotRoleUser();
    remediationLegacyPilot(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post('/my/pilot', remediationPilotPayload([
            'email' => 'second.remediation@example.com',
            'sacaa_certificate_number' => 'RPC-REM-002',
        ]))
        ->assertInvalid(['pilot_profile']);

    expect(UasPilot::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('does not let one user view or update another user pilot through self-service routes', function () {
    $userA = remediationPilotRoleUser();
    $userB = remediationPilotRoleUser();
    $pilotA = remediationLegacyPilot([
        'user_id' => $userA->id,
        'first_name' => 'Pilot',
        'last_name' => 'A',
        'email' => 'pilot.a@example.com',
        'sacaa_certificate_number' => 'RPC-A',
    ]);
    $pilotB = remediationLegacyPilot([
        'user_id' => $userB->id,
        'first_name' => 'Pilot',
        'last_name' => 'B',
        'email' => 'pilot.b@example.com',
        'sacaa_certificate_number' => 'RPC-B',
    ]);

    $this->actingAs($userA)
        ->get('/my/pilot')
        ->assertOk()
        ->assertSee('Pilot A')
        ->assertDontSee('Pilot B');

    $this->actingAs($userA)
        ->put('/my/pilot', remediationPilotPayload([
            'first_name' => 'Updated A',
            'email' => 'updated.a@example.com',
            'sacaa_certificate_number' => 'RPC-A-UPDATED',
            'user_id' => $userB->id,
            'profile_status' => 'active',
        ]))
        ->assertRedirect(route('my.pilot.show'));

    expect($pilotA->refresh()->first_name)->toBe('Updated A')
        ->and($pilotA->user_id)->toBe($userA->id)
        ->and($pilotA->profile_status->value)->toBe('draft')
        ->and($pilotB->refresh()->first_name)->toBe('Pilot');
});

it('keeps admin pilot management working through admin routes', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)
        ->post('/pilots', [
            ...remediationPilotPayload([
                'email' => 'admin.created@example.com',
                'sacaa_certificate_number' => 'RPC-ADMIN-001',
            ]),
            'user_id' => null,
            'employee_number' => 'ADMIN-PILOT-001',
            'profile_status' => 'draft',
        ])
        ->assertRedirect();

    $pilot = UasPilot::query()->firstOrFail();

    $this->actingAs($admin)
        ->get(route('pilots.show', $pilot))
        ->assertOk();
});

it('supports legacy unlinked pilot records and nulls the link when a user is deleted', function () {
    $legacyPilot = remediationLegacyPilot();

    expect($legacyPilot->user_id)->toBeNull();

    $user = remediationPilotRoleUser();
    $linkedPilot = remediationLegacyPilot([
        'user_id' => $user->id,
        'email' => 'delete.link@example.com',
        'sacaa_certificate_number' => 'RPC-DELETE-LINK',
    ]);

    $user->delete();

    expect($linkedPilot->refresh()->user_id)->toBeNull();
});

it('records audit evidence when the pilot user link is established', function () {
    $user = remediationPilotRoleUser();

    $this->actingAs($user)
        ->post('/my/pilot', remediationPilotPayload())
        ->assertRedirect(route('my.pilot.show'));

    $pilot = UasPilot::query()->firstOrFail();
    $auditEntry = UasAuditEntry::query()
        ->where('action', 'pilot.user.linked')
        ->firstOrFail();

    expect($auditEntry->auditable_type)->toBe(UasPilot::class)
        ->and($auditEntry->auditable_id)->toBe($pilot->id)
        ->and($auditEntry->new_values['user_id'])->toBe($user->id)
        ->and($auditEntry->new_values['link_source'])->toBe('self_service_creation');
});

it('allows a privileged admin to link an existing unowned pilot to a user once', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $targetUser = remediationPilotRoleUser();
    $pilot = remediationLegacyPilot();

    $this->actingAs($admin)
        ->put(route('pilots.user-link.update', $pilot), ['user_id' => $targetUser->id])
        ->assertRedirect(route('pilots.show', $pilot));

    expect($pilot->refresh()->user_id)->toBe($targetUser->id)
        ->and(app(CurrentPilotProfile::class)->resolve($targetUser)?->id)->toBe($pilot->id);

    expect(UasAuditEntry::query()->where('action', 'pilot.user.linked')->exists())->toBeTrue();
});

it('prevents administrative linking when the target user already owns another pilot', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $targetUser = remediationPilotRoleUser();
    remediationLegacyPilot(['user_id' => $targetUser->id]);
    $unownedPilot = remediationLegacyPilot([
        'email' => 'unowned.link@example.com',
        'sacaa_certificate_number' => 'RPC-UNOWNED-LINK',
    ]);

    $this->actingAs($admin)
        ->put(route('pilots.user-link.update', $unownedPilot), ['user_id' => $targetUser->id])
        ->assertInvalid(['user_id']);

    expect($unownedPilot->refresh()->user_id)->toBeNull();
});


it('does not allow pilot self service to declare verified medical or radiotelephony state', function () {
    $user = remediationPilotRoleUser();

    $this->actingAs($user)
        ->post('/my/pilot', remediationPilotPayload([
            'medical_status' => 'valid',
            'radiotelephony_qualification' => 'unrestricted',
        ]))
        ->assertInvalid(['medical_status', 'radiotelephony_qualification']);

    expect(UasPilot::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('preserves administrator verified compliance state when a pilot edits personal details', function () {
    $user = remediationPilotRoleUser();
    $pilot = remediationLegacyPilot([
        'user_id' => $user->id,
        'email' => 'verified.self.service@example.com',
        'sacaa_certificate_number' => 'RPC-VERIFIED-SELF',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'unrestricted',
    ]);

    $this->actingAs($user)
        ->put('/my/pilot', remediationPilotPayload([
            'first_name' => 'Updated',
            'email' => 'verified.self.service@example.com',
            'sacaa_certificate_number' => 'RPC-VERIFIED-SELF',
        ]))
        ->assertRedirect(route('my.pilot.show'));

    expect($pilot->refresh()->first_name)->toBe('Updated')
        ->and($pilot->medical_status->value)->toBe('valid')
        ->and($pilot->radiotelephony_qualification->value)->toBe('unrestricted');
});
