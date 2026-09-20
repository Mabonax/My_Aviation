<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Services\GisProjectLifecycle;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function gisProjectOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'gisProject Operator (Pty) Ltd',
        'trading_name' => 'gisProject Operator',
        'status' => 'active',
        'accountable_manager' => 'GIS Accountable Manager',
        'responsible_person_flight_operations' => 'GIS Flight Ops',
        'responsible_person_aircraft' => 'GIS Aircraft Lead',
        'regulatory_source' => 'TR-010 GIS tenancy fixture',
        'regulatory_source_version' => 'v1',
        'regulatory_effective_date' => '2026-09-21',
        'regulatory_applicability' => 'GIS tenant isolation verification',
        'responsible_role' => 'Accountable Manager',
    ]);
}

function gisProjectUser(array $permissions = ['gis.view', 'gis.create', 'gis.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'gis_project_manager_'.str()->random(8),
        'label' => 'GIS Project Manager',
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

function gisProjectPayload(array $overrides = []): array
{
    return [
        'project_code' => 'GIS-2026-001',
        'name' => 'Community infrastructure mapping',
        'project_type' => 'infrastructure_mapping',
        'client_or_stakeholder' => 'Municipal planning office',
        'area_name' => 'Midrand service corridor',
        'location_search_query' => 'Midrand service corridor',
        'centroid_latitude' => -25.9992,
        'centroid_longitude' => 28.1263,
        'area_boundary' => [
            ['latitude' => -25.9992, 'longitude' => 28.1263],
            ['latitude' => -25.9980, 'longitude' => 28.1300],
            ['latitude' => -26.0010, 'longitude' => 28.1310],
        ],
        'source_reference' => 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41',
        'source_version' => 'FRS v1.0, dated 2026-09-09',
        'data_governance_notes' => 'Retain source imagery references and derived layer provenance before reporting.',
        'evidence_required' => 'Project brief, authorised mission records, dataset provenance and final report evidence',
        'responsible_role' => 'GIS Lead',
        ...$overrides,
    ];
}

it('requires gis permissions for project routes', function () {
    $this->withoutVite();

    $operator = gisProjectOperator();
    $viewer = gisProjectUser(['gis.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);
    $project = UasGisProject::query()->create([
        ...gisProjectPayload(),
        'created_by' => $viewer->id,
        'updated_by' => $viewer->id,
    ]);

    $this->get('/gis-projects')->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->get('/gis-projects')->assertForbidden();
    $this->actingAs($viewer)->get('/gis-projects')->assertOk();
    $this->actingAs($viewer)->get('/gis-projects/create')->assertForbidden();
    $this->actingAs($viewer)->post('/gis-projects', gisProjectPayload(['project_code' => 'GIS-2026-002']))->assertForbidden();
    $this->actingAs($viewer)->put("/gis-projects/{$project->id}/transition", ['lifecycle_state' => 'authorise'])->assertForbidden();
});

it('creates a GIS project spine with source and audit evidence', function () {
    $operator = gisProjectOperator();
    $user = gisProjectUser([], $operator);

    $response = $this->actingAs($user)->post('/gis-projects', gisProjectPayload());

    $project = UasGisProject::query()->where('project_code', 'GIS-2026-001')->firstOrFail();

    $response->assertRedirect(route('gis-projects.show', $project));

    expect($project->name)->toBe('Community infrastructure mapping')
        ->and($project->project_type)->toBe('infrastructure_mapping')
        ->and($project->lifecycle_state)->toBe('plan')
        ->and($project->area_boundary)->toHaveCount(3)
        ->and($project->source_reference)->toContain('Phase 5')
        ->and($project->created_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'gis_project.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasGisProject::class)
        ->and($audit->auditable_id)->toBe($project->id)
        ->and($audit->requirement_id)->toBe('FR-GIS-001');
});

it('enforces documented GIS project lifecycle order', function () {
    $lifecycle = app(GisProjectLifecycle::class);

    expect($lifecycle->canTransition('plan', 'authorise'))->toBeTrue()
        ->and($lifecycle->canTransition('plan', 'fly'))->toBeFalse()
        ->and($lifecycle->canTransition('map', 'analyse'))->toBeTrue()
        ->and($lifecycle->canTransition('closed', 'cancelled'))->toBeFalse()
        ->and($lifecycle->canTransition('cancelled', 'plan'))->toBeFalse();
});

it('transitions GIS project workflow state with audit evidence', function () {
    $operator = gisProjectOperator();
    $user = gisProjectUser([], $operator);
    $project = UasGisProject::query()->create([
        ...gisProjectPayload(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->put("/gis-projects/{$project->id}/transition", ['lifecycle_state' => 'authorise'])
        ->assertRedirect();

    $project->refresh();

    expect($project->lifecycle_state)->toBe('authorise')
        ->and($project->updated_by)->toBe($user->id)
        ->and(UasAuditEntry::query()->where('action', 'gis_project.lifecycle_transitioned')->where('requirement_id', 'FR-GIS-001')->exists())->toBeTrue();

    $this->actingAs($user)
        ->put("/gis-projects/{$project->id}/transition", ['lifecycle_state' => 'map'])
        ->assertInvalid(['lifecycle_state']);
});

it('validates project identity type source evidence and coordinates', function () {
    $operator = gisProjectOperator();
    $user = gisProjectUser([], $operator);
    UasGisProject::query()->create([
        ...gisProjectPayload(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/gis-projects', gisProjectPayload([
            'project_code' => 'GIS-2026-001',
            'name' => '',
            'project_type' => 'unclassified_mapping',
            'area_name' => '',
            'centroid_latitude' => -95,
            'centroid_longitude' => 181,
            'source_reference' => '',
            'evidence_required' => '',
            'responsible_role' => '',
        ]))
        ->assertInvalid(['project_code', 'name', 'project_type', 'area_name', 'centroid_latitude', 'centroid_longitude', 'source_reference', 'evidence_required', 'responsible_role']);
});

it('exposes GIS project register pages through Inertia', function () {
    $this->withoutVite();

    $operator = gisProjectOperator();
    $user = gisProjectUser([], $operator);
    $project = UasGisProject::query()->create([
        ...gisProjectPayload(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/gis-projects')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/index')
            ->where('projects.0.project_code', 'GIS-2026-001')
        );

    $this->actingAs($user)
        ->get('/gis-projects/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/create')
            ->where('options.project_types.infrastructure_mapping', 'Infrastructure mapping')
        );

    $this->actingAs($user)
        ->get("/gis-projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/show')
            ->where('project.project_code', 'GIS-2026-001')
            ->where('options.states.authorise', 'Authorise')
        );
});
