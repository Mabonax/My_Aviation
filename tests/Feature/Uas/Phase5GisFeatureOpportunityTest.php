<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Geography\Domain\Models\UasGisDataset;
use App\Domains\Uas\Geography\Domain\Models\UasGisFeature;
use App\Domains\Uas\Geography\Domain\Models\UasGisOpportunityFinding;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Geography\Domain\Models\UasGisSpatialLayer;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function gisFeatureUser(array $permissions = ['gis.view', 'gis.create', 'gis.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'gis_feature_manager_'.str()->random(8),
        'label' => 'GIS Feature Manager',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function gisFeatureLayer(User $user): UasGisSpatialLayer
{
    $project = UasGisProject::query()->create([
        'project_code' => 'GIS-FEAT-001',
        'name' => 'Feature intelligence programme',
        'project_type' => 'economic_opportunity_mapping',
        'area_name' => 'Industrial corridor',
        'lifecycle_state' => 'analyse',
        'source_reference' => 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41',
        'source_version' => 'FRS v1.0, dated 2026-09-09',
        'evidence_required' => 'Project brief, mission authorisation and mapping output evidence',
        'responsible_role' => 'GIS Lead',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $mission = UasMission::query()->create([
        'mission_number' => 'MIS-FEAT-001',
        'purpose' => 'Industrial corridor feature capture',
        'location' => 'Industrial corridor',
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

    $assignment = UasGisProjectMission::query()->create([
        'uas_gis_project_id' => $project->id,
        'uas_mission_id' => $mission->id,
        'mapping_objective' => 'Identify opportunity areas and inspection findings',
        'capture_plan' => 'Capture corridor imagery and annotate derived features.',
        'expected_outputs' => ['orthomosaic', 'spatial_layer'],
        'field_verification_required' => 'recommended',
        'evidence_notes' => 'Field review recommended for opportunity classification.',
        'status' => 'mapped',
        'assigned_by' => $user->id,
    ]);

    $dataset = UasGisDataset::query()->create([
        'uas_gis_project_mission_id' => $assignment->id,
        'dataset_code' => 'DATA-FEAT-001',
        'title' => 'Industrial corridor orthomosaic',
        'dataset_type' => 'orthomosaic',
        'capture_source' => 'UAS mission capture',
        'storage_uri' => 'vault://gis/corridor/orthomosaic.tif',
        'processing_status' => 'processed',
        'quality_status' => 'passed',
        'provenance_notes' => 'Dataset derived from assigned mission imagery.',
        'created_by' => $user->id,
    ]);

    return UasGisSpatialLayer::query()->create([
        'uas_gis_dataset_id' => $dataset->id,
        'layer_name' => 'Opportunity zones',
        'layer_type' => 'opportunity_area',
        'geometry_type' => 'polygon',
        'status' => 'approved',
    ]);
}

function gisFeaturePayload(array $overrides = []): array
{
    return [
        'feature_code' => 'FEAT-2026-001',
        'name' => 'Underutilised serviced parcel',
        'feature_type' => 'opportunity_zone',
        'geometry_reference' => 'vault://gis/corridor/features/serviced-parcel.geojson#feature=1',
        'confidence_score' => 86.5,
        'verification_status' => 'field_check_required',
        'interpretation_notes' => 'Orthomosaic shows access road, nearby services and vacant developable area.',
        'evidence_notes' => 'Layer annotation screenshot retained with analyst notes.',
        'opportunities_findings' => [
            [
                'record_type' => 'opportunity',
                'category' => 'economic',
                'title' => 'Potential light industrial infill site',
                'description' => 'Mapped area appears suitable for further feasibility screening.',
                'significance' => 'high',
                'recommended_action' => 'Schedule stakeholder and field verification review.',
                'priority' => 'action_required',
                'status' => 'review',
                'evidence_reference' => 'vault://gis/corridor/evidence/feature-1.png',
                'due_date' => '2026-10-15',
                'responsible_role' => 'Economic Development Analyst',
            ],
        ],
        ...$overrides,
    ];
}

it('requires GIS update permission to capture layer features', function () {
    $this->withoutVite();

    $viewer = gisFeatureUser(['gis.view']);
    $layer = gisFeatureLayer($viewer);

    $this->actingAs($viewer)->get("/gis-layers/{$layer->id}/features/create")->assertForbidden();
    $this->actingAs($viewer)->post("/gis-layers/{$layer->id}/features", gisFeaturePayload())->assertForbidden();
});

it('captures a GIS feature with opportunity finding records and FR-GIS-004 audit evidence', function () {
    $user = gisFeatureUser();
    $layer = gisFeatureLayer($user);

    $this->actingAs($user)
        ->post("/gis-layers/{$layer->id}/features", gisFeaturePayload())
        ->assertRedirect(route('gis-projects.show', $layer->dataset->projectMission->project));

    $feature = UasGisFeature::query()->with('opportunitiesFindings')->where('feature_code', 'FEAT-2026-001')->firstOrFail();

    expect($feature->spatialLayer->is($layer))->toBeTrue()
        ->and($feature->feature_type)->toBe('opportunity_zone')
        ->and($feature->verification_status)->toBe('field_check_required')
        ->and($feature->opportunitiesFindings)->toHaveCount(1)
        ->and($feature->opportunitiesFindings->first()->record_type)->toBe('opportunity')
        ->and($feature->opportunitiesFindings->first()->priority)->toBe('action_required');

    $audit = UasAuditEntry::query()->where('action', 'gis_feature.created')->firstOrFail();

    expect($audit->auditable_type)->toBe(UasGisFeature::class)
        ->and($audit->auditable_id)->toBe($feature->id)
        ->and($audit->requirement_id)->toBe('FR-GIS-004');
});

it('validates feature classification geometry confidence and opportunity metadata', function () {
    $user = gisFeatureUser();
    $layer = gisFeatureLayer($user);
    UasGisFeature::query()->create([
        'uas_gis_spatial_layer_id' => $layer->id,
        'feature_code' => 'FEAT-2026-001',
        'name' => 'Existing feature',
        'feature_type' => 'opportunity_zone',
        'geometry_reference' => 'vault://gis/existing.geojson#feature=1',
        'verification_status' => 'verified',
        'interpretation_notes' => 'Existing feature.',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post("/gis-layers/{$layer->id}/features", gisFeaturePayload([
            'feature_code' => 'FEAT-2026-001',
            'name' => '',
            'feature_type' => 'unsupported',
            'geometry_reference' => '',
            'confidence_score' => 101,
            'verification_status' => 'hidden',
            'interpretation_notes' => '',
            'opportunities_findings' => [
                [
                    'record_type' => 'memo',
                    'category' => 'unknown',
                    'title' => '',
                    'description' => '',
                    'significance' => 'massive',
                    'priority' => 'whenever',
                    'status' => 'secret',
                ],
            ],
        ]))
        ->assertInvalid(['feature_code', 'name', 'feature_type', 'geometry_reference', 'confidence_score', 'verification_status', 'interpretation_notes', 'opportunities_findings.0.record_type', 'opportunities_findings.0.category', 'opportunities_findings.0.title', 'opportunities_findings.0.description', 'opportunities_findings.0.significance', 'opportunities_findings.0.priority', 'opportunities_findings.0.status']);
});

it('exposes feature creation options and project intelligence summaries through Inertia', function () {
    $this->withoutVite();

    $user = gisFeatureUser();
    $layer = gisFeatureLayer($user);

    $this->actingAs($user)
        ->get("/gis-layers/{$layer->id}/features/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/features/create')
            ->where('spatialLayer.layer_name', 'Opportunity zones')
            ->where('options.feature_types.opportunity_zone', 'Opportunity zone')
            ->where('options.record_types.finding', 'Finding')
        );

    $feature = UasGisFeature::query()->create([
        'uas_gis_spatial_layer_id' => $layer->id,
        'feature_code' => 'FEAT-2026-002',
        'name' => 'Drainage constraint',
        'feature_type' => 'risk_indicator',
        'geometry_reference' => 'vault://gis/corridor/features/drainage.geojson#feature=2',
        'verification_status' => 'verified',
        'interpretation_notes' => 'Potential drainage constraint visible in imagery.',
        'created_by' => $user->id,
    ]);

    UasGisOpportunityFinding::query()->create([
        'uas_gis_feature_id' => $feature->id,
        'record_type' => 'finding',
        'category' => 'infrastructure',
        'title' => 'Drainage review required',
        'description' => 'Drainage pattern should be reviewed before site recommendation.',
        'significance' => 'medium',
        'priority' => 'monitor',
        'status' => 'review',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get("/gis-projects/{$layer->dataset->projectMission->project->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('geography/projects/show')
            ->where('project.project_missions.0.datasets.0.layers.0.features.0.feature_code', 'FEAT-2026-002')
            ->where('project.project_missions.0.datasets.0.layers.0.features.0.opportunities_findings.0.title', 'Drainage review required')
        );
});
