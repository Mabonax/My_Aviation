<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function regulatoryFormUser(array $permissions = ['regulations.view', 'regulations.create', 'regulations.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'regulatory_form_manager_'.str()->random(8),
        'label' => 'Regulatory Form Manager',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function regulatoryFormPayload(array $overrides = []): array
{
    return [
        'form_code' => 'SACAA-UAS-F101',
        'form_title' => 'UAS operating certificate application',
        'regulatory_area' => 'Part 101',
        'revision' => '2026-01',
        'effective_date' => '2026-09-11',
        'source_reference' => 'SACAA controlled form catalogue import pending official source verification',
        'source_url' => 'https://www.caa.co.za/',
        'required_transaction' => 'UASOC application',
        'status' => 'active',
        'verified_at' => '2026-09-11 08:00:00',
        ...$overrides,
    ];
}

it('requires regulatory permissions for form register routes', function () {
    $this->withoutVite();

    $viewer = regulatoryFormUser(['regulations.view']);
    $form = RegulatoryForm::query()->create(regulatoryFormPayload());

    $this->actingAs($viewer)->get('/regulatory-forms')->assertOk();
    $this->actingAs($viewer)->get('/regulatory-forms/create')->assertForbidden();
    $this->actingAs($viewer)->post('/regulatory-forms', regulatoryFormPayload(['revision' => '2026-02']))->assertForbidden();
    $this->actingAs($viewer)->get("/regulatory-forms/{$form->id}/supersede")->assertForbidden();
});

it('creates a source controlled SACAA form record with FR-FRM-001 audit evidence', function () {
    $user = regulatoryFormUser();

    $this->actingAs($user)
        ->post('/regulatory-forms', regulatoryFormPayload())
        ->assertRedirect();

    $form = RegulatoryForm::query()->where('form_code', 'SACAA-UAS-F101')->firstOrFail();

    expect($form->regulatory_area)->toBe('Part 101')
        ->and($form->revision)->toBe('2026-01')
        ->and($form->required_transaction)->toBe('UASOC application')
        ->and($form->status)->toBe('active')
        ->and($form->verified_at)->not->toBeNull();

    $audit = UasAuditEntry::query()->where('action', 'regulatory_form.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(RegulatoryForm::class)
        ->and($audit->auditable_id)->toBe($form->id)
        ->and($audit->requirement_id)->toBe('FR-FRM-001');
});

it('creates a new form revision without changing historical catalogue details', function () {
    $user = regulatoryFormUser();
    $previous = RegulatoryForm::query()->create(regulatoryFormPayload([
        'form_title' => 'Original UAS operating certificate application',
        'revision' => '2025-01',
    ]));

    $this->actingAs($user)
        ->post("/regulatory-forms/{$previous->id}/supersede", regulatoryFormPayload([
            'form_title' => 'Updated UAS operating certificate application',
            'revision' => '2026-02',
            'effective_date' => '2026-10-01',
        ]))
        ->assertRedirect();

    $previous->refresh();
    $next = RegulatoryForm::query()->where('revision', '2026-02')->firstOrFail();

    expect($previous->form_title)->toBe('Original UAS operating certificate application')
        ->and($previous->status)->toBe('superseded')
        ->and($previous->superseded_date->toDateString())->toBe('2026-10-01')
        ->and($next->previous_form_id)->toBe($previous->id)
        ->and($next->form_title)->toBe('Updated UAS operating certificate application')
        ->and($next->status)->toBe('active');

    expect(UasAuditEntry::query()->where('action', 'regulatory_form.superseded')->where('requirement_id', 'FR-FRM-001')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'regulatory_form.version_created')->where('requirement_id', 'FR-FRM-001')->exists())->toBeTrue();
});

it('validates form catalogue identity, source and status fields', function () {
    $user = regulatoryFormUser();
    RegulatoryForm::query()->create(regulatoryFormPayload(['form_code' => 'SACAA-DUP', 'revision' => 'REV-1']));

    $this->actingAs($user)
        ->post('/regulatory-forms', regulatoryFormPayload([
            'form_code' => 'SACAA-DUP',
            'form_title' => '',
            'revision' => 'REV-1',
            'effective_date' => '',
            'source_reference' => '',
            'source_url' => 'not-a-url',
            'required_transaction' => '',
            'status' => 'silently_replaced',
        ]))
        ->assertInvalid(['form_title', 'revision', 'effective_date', 'source_reference', 'source_url', 'required_transaction', 'status']);
});

it('exposes regulatory form register pages through Inertia', function () {
    $this->withoutVite();

    $user = regulatoryFormUser();
    $previous = RegulatoryForm::query()->create(regulatoryFormPayload(['revision' => '2025-01']));
    $next = RegulatoryForm::query()->create(regulatoryFormPayload(['previous_form_id' => $previous->id, 'revision' => '2026-01', 'effective_date' => '2026-10-01']));
    $previous->update(['status' => 'superseded', 'superseded_date' => '2026-10-01']);

    $this->actingAs($user)
        ->get('/regulatory-forms')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/forms/index')
            ->where('forms.0.form_code', 'SACAA-UAS-F101')
        );

    $this->actingAs($user)
        ->get("/regulatory-forms/{$previous->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/forms/show')
            ->where('form.form_code', 'SACAA-UAS-F101')
            ->where('form.superseding_forms.0.revision', '2026-01')
        );

    $this->actingAs($user)
        ->get("/regulatory-forms/{$next->id}/supersede")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/forms/supersede')
            ->where('form.revision', '2026-01')
        );
});
