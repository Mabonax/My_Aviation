<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function externalIntegrationUser(array $permissions = ['regulations.view', 'regulations.create', 'regulations.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'external_integration_manager_'.str()->random(8),
        'label' => 'External Integration Manager',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function externalIntegrationPayload(array $overrides = []): array
{
    return [
        'name' => 'SACAA e-Services UASOC submission',
        'authority' => 'SACAA',
        'classification' => 'document_based',
        'regulatory_area' => 'Part 101',
        'supported_process' => 'UASOC application submission',
        'authoritative_url' => 'https://www.caa.co.za/',
        'evidence_required' => 'Uploaded submission receipt and authority correspondence',
        'workflow_notes' => 'Open the authoritative process, submit the prepared pack externally and retain receipt evidence in the operator case.',
        'api_assumption_blocked' => true,
        'status' => 'active',
        'verified_at' => '2026-09-11 09:00:00',
        ...$overrides,
    ];
}

it('requires regulatory permissions for external integration routes', function () {
    $this->withoutVite();

    $viewer = externalIntegrationUser(['regulations.view']);
    $integration = RegulatoryExternalIntegration::query()->create(externalIntegrationPayload());

    $this->actingAs($viewer)->get('/regulatory-external-integrations')->assertOk();
    $this->actingAs($viewer)->get('/regulatory-external-integrations/create')->assertForbidden();
    $this->actingAs($viewer)->post('/regulatory-external-integrations', externalIntegrationPayload(['supported_process' => 'UASLA renewal']))->assertForbidden();
    $this->actingAs($viewer)->put("/regulatory-external-integrations/{$integration->id}/status", ['status' => 'suspended'])->assertForbidden();
});

it('captures classified external regulatory processes with FR-EXT-001 audit evidence', function () {
    $user = externalIntegrationUser();

    $this->actingAs($user)
        ->post('/regulatory-external-integrations', externalIntegrationPayload())
        ->assertRedirect();

    $integration = RegulatoryExternalIntegration::query()->where('supported_process', 'UASOC application submission')->firstOrFail();

    expect($integration->authority)->toBe('SACAA')
        ->and($integration->classification)->toBe('document_based')
        ->and($integration->api_assumption_blocked)->toBeTrue()
        ->and($integration->evidence_required)->toContain('submission receipt')
        ->and($integration->verified_at)->not->toBeNull();

    $audit = UasAuditEntry::query()->where('action', 'regulatory_external_integration.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(RegulatoryExternalIntegration::class)
        ->and($audit->auditable_id)->toBe($integration->id)
        ->and($audit->requirement_id)->toBe('FR-EXT-001');
});

it('forces api classifications to keep undocumented api assumptions blocked', function () {
    $user = externalIntegrationUser();

    $this->actingAs($user)
        ->post('/regulatory-external-integrations', externalIntegrationPayload([
            'classification' => 'api',
            'supported_process' => 'Authority API status lookup',
            'api_assumption_blocked' => false,
        ]))
        ->assertRedirect();

    $integration = RegulatoryExternalIntegration::query()->where('supported_process', 'Authority API status lookup')->firstOrFail();

    expect($integration->classification)->toBe('api')
        ->and($integration->api_assumption_blocked)->toBeTrue();
});

it('updates external integration lifecycle status with audit evidence', function () {
    $user = externalIntegrationUser();
    $integration = RegulatoryExternalIntegration::query()->create(externalIntegrationPayload());

    $this->actingAs($user)
        ->put("/regulatory-external-integrations/{$integration->id}/status", ['status' => 'suspended'])
        ->assertRedirect();

    $integration->refresh();

    expect($integration->status)->toBe('suspended')
        ->and(UasAuditEntry::query()->where('action', 'regulatory_external_integration.status_updated')->where('requirement_id', 'FR-EXT-001')->exists())->toBeTrue();
});

it('validates classification source workflow and unique authority process pairing', function () {
    $user = externalIntegrationUser();
    RegulatoryExternalIntegration::query()->create(externalIntegrationPayload());

    $this->actingAs($user)
        ->post('/regulatory-external-integrations', externalIntegrationPayload([
            'name' => '',
            'classification' => 'undocumented_direct_api',
            'supported_process' => 'UASOC application submission',
            'authoritative_url' => 'not-a-url',
            'evidence_required' => '',
            'workflow_notes' => '',
            'status' => 'silently_integrated',
        ]))
        ->assertInvalid(['name', 'classification', 'supported_process', 'authoritative_url', 'evidence_required', 'workflow_notes', 'status']);
});

it('exposes external integration register pages through Inertia', function () {
    $this->withoutVite();

    $user = externalIntegrationUser();
    $integration = RegulatoryExternalIntegration::query()->create(externalIntegrationPayload());

    $this->actingAs($user)
        ->get('/regulatory-external-integrations')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/external-integrations/index')
            ->where('integrations.0.name', 'SACAA e-Services UASOC submission')
        );

    $this->actingAs($user)
        ->get('/regulatory-external-integrations/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/external-integrations/create')
            ->where('options.classifications.document_based', 'Document-based')
        );

    $this->actingAs($user)
        ->get("/regulatory-external-integrations/{$integration->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/external-integrations/show')
            ->where('integration.api_assumption_blocked', true)
            ->where('options.statuses.suspended', 'Suspended')
        );
});
