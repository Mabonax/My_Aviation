<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function regulatoryUser(array $permissions = ['regulations.view', 'regulations.create', 'regulations.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'regulatory_manager_'.str()->random(8),
        'label' => 'Regulatory Manager',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function regulatoryPayload(array $overrides = []): array
{
    return [
        'requirement_id' => 'REG-P101-001',
        'regulation_part' => 'Part 101',
        'clause_reference' => '101.01',
        'title' => 'UAS operational competency requirement',
        'requirement_text' => 'Remote pilots must comply with documented operational competency controls.',
        'responsible_party' => 'Compliance Manager',
        'applicability' => 'Commercial UAS operations controlled by the operator.',
        'system_control' => 'Regulatory requirement register and linked compliance controls.',
        'evidence_required' => 'Competency record and operations evidence.',
        'frequency' => 'annual',
        'validity_period' => '12 months',
        'retention_period' => '5 years',
        'effective_date' => '2026-09-09',
        'official_source' => 'SACAA Part 101 source reference pending controlled import',
        'source_version' => 'FRS v1.0',
        'status' => 'active',
        ...$overrides,
    ];
}

it('requires regulatory permissions for requirement register routes', function () {
    $this->withoutVite();

    $viewer = regulatoryUser(['regulations.view']);
    $requirement = RegulatoryRequirement::query()->create(regulatoryPayload());

    $this->actingAs($viewer)->get('/regulatory-requirements')->assertOk();
    $this->actingAs($viewer)->get('/regulatory-requirements/create')->assertForbidden();
    $this->actingAs($viewer)->post('/regulatory-requirements', regulatoryPayload(['requirement_id' => 'REG-P101-002']))->assertForbidden();
    $this->actingAs($viewer)->get("/regulatory-requirements/{$requirement->id}/supersede")->assertForbidden();
});

it('creates structured regulatory requirements with FR-REG-001 audit evidence', function () {
    $user = regulatoryUser();

    $this->actingAs($user)
        ->post('/regulatory-requirements', regulatoryPayload())
        ->assertRedirect();

    $requirement = RegulatoryRequirement::query()->where('requirement_id', 'REG-P101-001')->firstOrFail();

    expect($requirement->regulation_part)->toBe('Part 101')
        ->and($requirement->responsible_party)->toBe('Compliance Manager')
        ->and($requirement->evidence_required)->toBe('Competency record and operations evidence.')
        ->and($requirement->status)->toBe('active');

    $audit = UasAuditEntry::query()->where('action', 'regulatory_requirement.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(RegulatoryRequirement::class)
        ->and($audit->auditable_id)->toBe($requirement->id)
        ->and($audit->requirement_id)->toBe('FR-REG-001');
});

it('creates a new regulatory version without mutating historical requirement text', function () {
    $user = regulatoryUser();
    $previous = RegulatoryRequirement::query()->create(regulatoryPayload([
        'requirement_id' => 'REG-P101-OLD',
        'requirement_text' => 'Original requirement text must remain available.',
        'source_version' => 'FRS v1.0',
    ]));

    $this->actingAs($user)
        ->post("/regulatory-requirements/{$previous->id}/supersede", regulatoryPayload([
            'requirement_id' => 'REG-P101-NEW',
            'requirement_text' => 'Updated requirement text for the new version.',
            'source_version' => 'FRS v1.1',
            'effective_date' => '2026-10-01',
        ]))
        ->assertRedirect();

    $previous->refresh();
    $next = RegulatoryRequirement::query()->where('requirement_id', 'REG-P101-NEW')->firstOrFail();

    expect($previous->requirement_text)->toBe('Original requirement text must remain available.')
        ->and($previous->status)->toBe('superseded')
        ->and($previous->superseded_date->toDateString())->toBe('2026-10-01')
        ->and($next->previous_requirement_id)->toBe($previous->id)
        ->and($next->requirement_text)->toBe('Updated requirement text for the new version.')
        ->and($next->source_version)->toBe('FRS v1.1')
        ->and($next->status)->toBe('active');

    expect(UasAuditEntry::query()->where('action', 'regulatory_requirement.superseded')->where('requirement_id', 'FR-REG-002')->exists())->toBeTrue()
        ->and(UasAuditEntry::query()->where('action', 'regulatory_requirement.version_created')->where('requirement_id', 'FR-REG-002')->exists())->toBeTrue();
});

it('validates regulatory requirement identity and source fields', function () {
    $user = regulatoryUser();
    RegulatoryRequirement::query()->create(regulatoryPayload(['requirement_id' => 'REG-DUPLICATE']));

    $this->actingAs($user)
        ->post('/regulatory-requirements', regulatoryPayload([
            'requirement_id' => 'REG-DUPLICATE',
            'title' => '',
            'effective_date' => '',
            'official_source' => '',
            'source_version' => '',
            'status' => 'silently_replaced',
        ]))
        ->assertInvalid(['requirement_id', 'title', 'effective_date', 'official_source', 'source_version', 'status']);
});

it('exposes regulatory requirement register pages through Inertia', function () {
    $this->withoutVite();

    $user = regulatoryUser();
    $previous = RegulatoryRequirement::query()->create(regulatoryPayload(['requirement_id' => 'REG-VISIBLE-OLD', 'source_version' => 'FRS v1.0']));
    $next = RegulatoryRequirement::query()->create(regulatoryPayload(['previous_requirement_id' => $previous->id, 'requirement_id' => 'REG-VISIBLE-NEW', 'source_version' => 'FRS v1.1']));
    $previous->update(['status' => 'superseded', 'superseded_date' => '2026-10-01']);

    $this->actingAs($user)
        ->get('/regulatory-requirements')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/requirements/index')
            ->where('requirements.0.requirement_id', 'REG-VISIBLE-NEW')
        );

    $this->actingAs($user)
        ->get("/regulatory-requirements/{$previous->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/requirements/show')
            ->where('requirement.requirement_id', 'REG-VISIBLE-OLD')
            ->where('requirement.superseding_requirements.0.requirement_id', 'REG-VISIBLE-NEW')
        );

    $this->actingAs($user)
        ->get("/regulatory-requirements/{$next->id}/supersede")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('regulations/requirements/supersede')
            ->where('requirement.requirement_id', 'REG-VISIBLE-NEW')
        );
});
