<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Geography\Domain\Models\UasGisDataset;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Geography\Domain\Models\UasGisSpatialLayer;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function gisDatasetUser(array $permissions = ['gis.view', 'gis.create', 'gis.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'gis_dataset_manager_'.str()->random(8),
        'label' => 'GIS Dataset Manager',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function gisDatasetProjectMission(User $user): UasGisProjectMission
{
    $project = UasGisProject::query()->create([
        'project_code' => 'GIS-DATA-001',
        'name' => 'Dataset capture programme',
        'project_type' => 'environmental_mapping',
        'area_name' => 'Wetland reserve',
        'lifecycle_state' => 'capture',
        'source_reference' => 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41',
        'source_version' => 'FRS v1.0, dated 2026-09-09',
        'evidence_required' => 'Project brief, mission authorisation and mapping output evidence',
        'responsible_role' => 'GIS Lead',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $mission = UasMission::query()->create([
        'mission_number' => 'MIS-DATA-001',
        'purpose' => 'Wetland imagery capture',
        'location' => 'Wetland reserve',
        'operation_category' => 'mapping',
        'operation_visibility' => 'vlos',
        'day_night' => 'day',
        'lifecycle_state' => 'completed',
        'release_gate_state' => 'green',
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-001 and FR-MIS-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission planning and operational release workflow for VMT UAS operations.',
        'responsible_role' => 'Operations Manager',
    ]);

    return UasGisProjectMission::query()->create([
        'uas_gis_project_id' => $project->id,
        'uas_mission_id' => $mission->id,
        'mapping_objective' => 'Create orthomosaic and wetland indicator layer',
        'capture_plan' => 'Capture nadir imagery and retain processing settings.',
        'expected_outputs' => ['imagery', 'orthomosaic', 'spatial_layer'],
        'field_verification_required' => 'required',
        'evidence_notes' => 'Field verification required before report.',
        'status' => 'captured',
        'assigned_by' => $user->id,
    ]);
}

function gisDatasetPayload(array $overrides = []): array
{
    return [
        'dataset_code' => 'DATA-2026-001',
        'title' => 'Wetland orthomosaic source set',
        'dataset_type' => 'orthomosaic',
        'capture_source' => 'UAS mission capture',
        'storage_uri' => 'vault://gis/wetland/orthomosaic.tif',
        'checksum' => 'sha256:example',
        'coordinate_reference_system' => 'WGS84',
        'resolution_cm' => 4.5,
        'captured_at' => '2026-09-12 08:00:00',
        'processed_at' => '2026-09-12 10:00:00',
        'processing_status' => 'processed',
        'quality_status' => 'passed',
        'provenance_notes' => 'Dataset derived from assigned mission imagery and retained processing settings.',
        'evidence_notes' => 'Processing log and quality screenshot retained.',
        'layers' => [
            [
                'layer_name' => 'Wetland boundary draft',
                'layer_type' => 'environmental_indicator',
                'geometry_type' => 'polygon',
                'source_uri' => 'vault://gis/wetland/wetland-boundary.geojson',
                'analysis_notes' => 'Initial boundary extracted from orthomosaic.',
                'status' => 'review',
            ],
        ],
        ...$overrides,
    ];
}

it('requires GIS update permission to capture datasets', function () {
    $this->withoutVite();

    $viewer = gisDatasetUser(['gis.view']);
    $projectMission = gisDatasetProjectMission($viewer);

    $this->actingAs($viewer)->get("/gis-project-missions/{$projectMission->id}/datasets/create")->assertForbidden();
    $this->actingAs($viewer)->post("/gis-project-missions/{$projectMission->id}/datasets", gisDatasetPayload())->assertForbidden();
});

it('captures a geospatial dataset with spatial layers and FR-GIS-003 audit evidence', function () {
    $user = gisDatasetUser();
    $projectMission = gisDatasetProjectMission($user);

    $this->actingAs($user)
        ->post("/gis-project-missions/{$projectMission->id}/datasets", gisDatasetPayload())
        ->assertRedirect(route('gis-projects.show', $projectMission->project));

    $dataset = UasGisDataset::query()->with('layers')->where('dataset_code', 'DATA-2026-001')->firstOrFail();

    expect($dataset->projectMission->is($projectMission))->toBeTrue()
        ->and($dataset->dataset_type)->toBe('orthomosaic')
        ->and($dataset->processing_status)->toBe('processed')
        ->and($dataset->quality_status)->toBe('passed')
        ->and($dataset->layers)->toHaveCount(1)
        ->and($dataset->layers->first()->layer_type)->toBe('environmental_indicator');

    $audit = UasAuditEntry::query()->where('action', 'gis_dataset.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasGisDataset::class)
        ->and($audit->auditable_id)->toBe($dataset->id)
        ->and($audit->requirement_id)->toBe('FR-GIS-003');
});

it('validates dataset provenance processing state and layer metadata', function () {
    $user = gisDatasetUser();
    $projectMission = gisDatasetProjectMission($user);
    UasGisDataset::query()->create([
        'uas_gis_project_mission_id' => $projectMission->id,
        ...gisDatasetPayload(['layers' => []]),
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post("/gis-project-missions/{$projectMission->id}/datasets", gisDatasetPayload([
            'dataset_code' => 'DATA-2026-001',
            'title' => '',
            'dataset_type' => 'spreadsheet_guess',
            'capture_source' => '',
            'storage_uri' => '',
            'resolution_cm' => -1,
            'processed_at' => '2026-09-12 07:00:00',
            'processing_status' => 'silent',
            'quality_status' => 'unknown',
            'provenance_notes' => '',
            'layers' => [
                [
                    'layer_name' => '',
                    'layer_type' => 'unmapped',
                    'geometry_type' => 'hexbin',
                    'status' => 'hidden',
                ],
            ],
        ]))
        ->assertInvalid(['dataset_code', 'title', 'dataset_type', 'capture_source', 'storage_uri', 'resolution_cm', 'processed_at', 'processing_status', 'quality_status', 'provenance_notes', 'layers.0.layer_name', 'layers.0.layer_type', 'layers.0.geometry_type', 'layers.0.status']);
});

it('exposes dataset creation options and project dataset summaries through Inertia', function () {
    $this->withoutVite();

    $user = gisDatasetUser();
    $projectMission = gisDatasetProjectMission($user);

    $this->actingAs($user)
        ->get("/gis-project-missions/{$projectMission->id}/datasets/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/datasets/create')
            ->where('projectMission.mapping_objective', 'Create orthomosaic and wetland indicator layer')
            ->where('options.dataset_types.orthomosaic', 'Orthomosaic')
            ->where('options.layer_types.environmental_indicator', 'Environmental indicator')
        );

    $dataset = UasGisDataset::query()->create([
        'uas_gis_project_mission_id' => $projectMission->id,
        ...gisDatasetPayload(['layers' => []]),
        'created_by' => $user->id,
    ]);

    UasGisSpatialLayer::query()->create([
        'uas_gis_dataset_id' => $dataset->id,
        'layer_name' => 'Wetland boundary draft',
        'layer_type' => 'environmental_indicator',
        'geometry_type' => 'polygon',
        'status' => 'review',
    ]);

    $this->actingAs($user)
        ->get("/gis-projects/{$projectMission->project->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/show')
            ->where('project.project_missions.0.datasets.0.dataset_code', 'DATA-2026-001')
            ->where('project.project_missions.0.datasets.0.layers.0.layer_name', 'Wetland boundary draft')
        );
});
