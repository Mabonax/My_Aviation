<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function personaPilotUser(): User
{
    $user = User::factory()->create();
    $role = UasRole::query()->create([
        'name' => 'persona-pilot-'.uniqid(),
        'label' => 'Remote Pilot',
        'permissions' => ['pilots.self-service'],
    ]);
    $role->users()->attach($user);

    return $user;
}

test('pilot persona receives pilot capabilities without platform administration capabilities', function () {
    $user = personaPilotUser();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('uiCapabilities.persona', 'pilot')
            ->where('uiCapabilities.pilot_self_service', true)
            ->where('uiCapabilities.platform_admin', false)
            ->where('pilotOnboardingRequired', true)
            ->where('pilotWorkspace', null)
            ->where('summary', null));
});

test('pilot dashboard returns only the authenticated pilot personal workspace', function () {
    $user = personaPilotUser();

    UasPilot::query()->create([
        'user_id' => $user->id,
        'first_name' => 'Pilot',
        'last_name' => 'Persona',
        'email' => 'pilot.persona@example.com',
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'unverified',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'draft',
        'regulatory_source' => 'persona audit',
        'regulatory_source_version' => '1',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'pilot',
        'responsible_role' => 'Pilot',
    ]);

    UasPilot::query()->create([
        'first_name' => 'Foreign',
        'last_name' => 'Pilot',
        'email' => 'foreign.persona@example.com',
        'rpc_category' => 'multi_rotor',
        'medical_status' => 'valid',
        'radiotelephony_qualification' => 'restricted',
        'profile_status' => 'active',
        'regulatory_source' => 'persona audit',
        'regulatory_source_version' => '1',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'pilot',
        'responsible_role' => 'Compliance Manager',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pilotOnboardingRequired', false)
            ->where('pilotWorkspace.pilot.first_name', 'Pilot')
            ->where('pilotWorkspace.compliance.medical_status', 'unverified')
            ->where('summary', null));
});

test('platform administrator retains platform capability and compliance summary', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('uiCapabilities.persona', 'platform_admin')
            ->where('uiCapabilities.platform_admin', true)
            ->has('summary'));
});
