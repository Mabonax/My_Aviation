<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function gisMissionAssignmentOperator(): UasOperator
{
    return UasOperator::query()->create([
        'legal_entity' => 'gisMissionAssignment Operator (Pty) Ltd',
        'trading_name' => 'gisMissionAssignment Operator',
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

function gisMissionAssignmentUser(array $permissions = ['gis.view', 'gis.create', 'gis.update'], ?UasOperator $operator = null, string $membershipRole = UasOperatorMembership::ROLE_OPERATIONS_MANAGER): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'gis_mission_manager_'.str()->random(8),
        'label' => 'GIS Mission Manager',
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

function gisMissionProject(User $user, array $overrides = []): UasGisProject
{
    return UasGisProject::query()->create([
        'project_code' => 'GIS-MSN-001',
        'name' => 'Regional mapping programme',
        'project_type' => 'environmental_mapping',
        'area_name' => 'Watercourse corridor',
        'lifecycle_state' => 'authorise',
        'source_reference' => 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41',
        'source_version' => 'FRS v1.0, dated 2026-09-09',
        'evidence_required' => 'Project brief, mission authorisation and mapping output evidence',
        'responsible_role' => 'GIS Lead',
        'created_by' => $user->id,
        'updated_by' => $user->id,
        ...$overrides,
    ]);
}

function gisOperationalMission(?UasOperator $operator = null, array $overrides = []): UasMission
{
    return UasMission::query()->create([
        'mission_number' => 'MIS-GIS-001',
        'uas_operator_id' => $operator?->id,
        'purpose' => 'Watercourse imagery capture',
        'client_project' => 'Regional mapping programme',
        'location' => 'Watercourse corridor',
        'latitude' => -25.9992,
        'longitude' => 28.1263,
        'operation_category' => 'mapping',
        'planned_start_at' => now()->addDay(),
        'planned_end_at' => now()->addDay()->addHours(2),
        'maximum_altitude_ft' => 400,
        'planned_distance_km' => 4.25,
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'lifecycle_state' => 'approved',
        'release_gate_state' => 'green',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-001 and FR-MIS-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission planning and operational release workflow for VMT UAS operations.',
        'responsible_role' => 'Operations Manager',
        ...$overrides,
    ]);
}

function gisMissionAssignmentPayload(UasMission $mission, array $overrides = []): array
{
    return [
        'uas_mission_id' => $mission->id,
        'mapping_objective' => 'Capture nadir imagery for derived environmental layers',
        'capture_plan' => 'Fly approved grid lines and retain source image set, processing settings and field notes.',
        'expected_outputs' => ['imagery', 'orthomosaic', 'spatial_layer'],
        'field_verification_required' => 'required',
        'evidence_notes' => 'Field verification notes must be attached before reporting.',
        'status' => 'planned',
        ...$overrides,
    ];
}

it('requires GIS update permission to assign missions to projects', function () {
    $this->withoutVite();

    $operator = gisMissionAssignmentOperator();
    $viewer = gisMissionAssignmentUser(['gis.view'], $operator, UasOperatorMembership::ROLE_REMOTE_PILOT);
    $project = gisMissionProject($viewer);
    $mission = gisOperationalMission($operator);

    $this->actingAs($viewer)->get("/gis-projects/{$project->id}/missions/create")->assertForbidden();
    $this->actingAs($viewer)->post("/gis-projects/{$project->id}/missions", gisMissionAssignmentPayload($mission))->assertForbidden();
});

it('assigns an operational mission to a GIS project with mapping evidence intent', function () {
    $operator = gisMissionAssignmentOperator();
    $user = gisMissionAssignmentUser([], $operator);
    $project = gisMissionProject($user);
    $mission = gisOperationalMission($operator);

    $this->actingAs($user)
        ->post("/gis-projects/{$project->id}/missions", gisMissionAssignmentPayload($mission))
        ->assertRedirect(route('gis-projects.show', $project));

    $assignment = UasGisProjectMission::query()->firstOrFail();

    expect($assignment->project->is($project))->toBeTrue()
        ->and($assignment->mission->is($mission))->toBeTrue()
        ->and($assignment->expected_outputs)->toBe(['imagery', 'orthomosaic', 'spatial_layer'])
        ->and($assignment->field_verification_required)->toBe('required')
        ->and($assignment->assigned_by)->toBe($user->id);

    $audit = UasAuditEntry::query()->where('action', 'gis_project_mission.assigned')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasGisProjectMission::class)
        ->and($audit->auditable_id)->toBe($assignment->id)
        ->and($audit->requirement_id)->toBe('FR-GIS-002');
});

it('prevents one mission being assigned to multiple GIS projects', function () {
    $operator = gisMissionAssignmentOperator();
    $user = gisMissionAssignmentUser([], $operator);
    $project = gisMissionProject($user);
    $otherProject = gisMissionProject($user, ['project_code' => 'GIS-MSN-002']);
    $mission = gisOperationalMission($operator);

    UasGisProjectMission::query()->create([
        'uas_gis_project_id' => $project->id,
        ...gisMissionAssignmentPayload($mission),
        'assigned_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post("/gis-projects/{$otherProject->id}/missions", gisMissionAssignmentPayload($mission))
        ->assertInvalid(['uas_mission_id']);
});

it('validates mapping objective capture plan outputs and field verification state', function () {
    $operator = gisMissionAssignmentOperator();
    $user = gisMissionAssignmentUser([], $operator);
    $project = gisMissionProject($user);
    $mission = gisOperationalMission($operator);

    $this->actingAs($user)
        ->post("/gis-projects/{$project->id}/missions", gisMissionAssignmentPayload($mission, [
            'mapping_objective' => '',
            'capture_plan' => '',
            'expected_outputs' => [],
            'field_verification_required' => 'unchecked',
            'status' => 'silent',
        ]))
        ->assertInvalid(['mapping_objective', 'capture_plan', 'expected_outputs', 'field_verification_required', 'status']);
});

it('exposes assignable missions and project mission summaries through Inertia', function () {
    $this->withoutVite();

    $operator = gisMissionAssignmentOperator();
    $user = gisMissionAssignmentUser([], $operator);
    $project = gisMissionProject($user);
    $mission = gisOperationalMission($operator);

    $this->actingAs($user)
        ->get("/gis-projects/{$project->id}/missions/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/missions/create')
            ->where('project.project_code', 'GIS-MSN-001')
            ->where('options.missions.0.mission_number', 'MIS-GIS-001')
            ->where('options.outputs.orthomosaic', 'Orthomosaic')
        );

    UasGisProjectMission::query()->create([
        'uas_gis_project_id' => $project->id,
        ...gisMissionAssignmentPayload($mission),
        'assigned_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get("/gis-projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/show')
            ->where('project.project_missions.0.mission.mission_number', 'MIS-GIS-001')
            ->where('project.project_missions.0.mapping_objective', 'Capture nadir imagery for derived environmental layers')
        );
});
