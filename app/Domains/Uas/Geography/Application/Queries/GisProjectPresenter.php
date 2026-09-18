<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;

class GisProjectPresenter
{
    public static function toArray(UasGisProject $project): array
    {
        $project->loadMissing(['creator', 'updater', 'projectMissions.mission', 'projectMissions.assigner', 'projectMissions.datasets.layers.features.opportunitiesFindings']);

        return [
            ...self::summary($project),
            'location_search_query' => $project->location_search_query,
            'centroid_latitude' => $project->centroid_latitude,
            'centroid_longitude' => $project->centroid_longitude,
            'area_boundary' => $project->area_boundary,
            'source_reference' => $project->source_reference,
            'source_version' => $project->source_version,
            'data_governance_notes' => $project->data_governance_notes,
            'evidence_required' => $project->evidence_required,
            'created_at' => $project->created_at?->toISOString(),
            'updated_at' => $project->updated_at?->toISOString(),
            'updated_by' => $project->updater?->name,
            'project_missions' => $project->projectMissions->map(fn ($assignment): array => [
                'id' => $assignment->id,
                'mapping_objective' => $assignment->mapping_objective,
                'capture_plan' => $assignment->capture_plan,
                'expected_outputs' => $assignment->expected_outputs ?? [],
                'field_verification_required' => $assignment->field_verification_required,
                'evidence_notes' => $assignment->evidence_notes,
                'status' => $assignment->status,
                'assigned_by' => $assignment->assigner?->name,
                'mission' => $assignment->mission ? [
                    'id' => $assignment->mission->id,
                    'mission_number' => $assignment->mission->mission_number,
                    'purpose' => $assignment->mission->purpose,
                    'location' => $assignment->mission->location,
                    'lifecycle_state' => $assignment->mission->lifecycle_state->value,
                    'release_gate_state' => $assignment->mission->release_gate_state,
                ] : null,
                'datasets' => $assignment->datasets->map(fn ($dataset): array => [
                    'id' => $dataset->id,
                    'dataset_code' => $dataset->dataset_code,
                    'title' => $dataset->title,
                    'dataset_type' => $dataset->dataset_type,
                    'storage_uri' => $dataset->storage_uri,
                    'processing_status' => $dataset->processing_status,
                    'quality_status' => $dataset->quality_status,
                    'captured_at' => $dataset->captured_at?->toISOString(),
                    'processed_at' => $dataset->processed_at?->toISOString(),
                    'layers' => $dataset->layers->map(fn ($layer): array => [
                        'id' => $layer->id,
                        'layer_name' => $layer->layer_name,
                        'layer_type' => $layer->layer_type,
                        'geometry_type' => $layer->geometry_type,
                        'status' => $layer->status,
                        'features' => $layer->features->map(fn ($feature): array => [
                            'id' => $feature->id,
                            'feature_code' => $feature->feature_code,
                            'name' => $feature->name,
                            'feature_type' => $feature->feature_type,
                            'verification_status' => $feature->verification_status,
                            'opportunities_findings' => $feature->opportunitiesFindings->map(fn ($record): array => [
                                'id' => $record->id,
                                'record_type' => $record->record_type,
                                'category' => $record->category,
                                'title' => $record->title,
                                'significance' => $record->significance,
                                'priority' => $record->priority,
                                'status' => $record->status,
                            ])->values()->all(),
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public static function summary(UasGisProject $project): array
    {
        return [
            'id' => $project->id,
            'project_code' => $project->project_code,
            'name' => $project->name,
            'project_type' => $project->project_type,
            'client_or_stakeholder' => $project->client_or_stakeholder,
            'area_name' => $project->area_name,
            'lifecycle_state' => $project->lifecycle_state,
            'responsible_role' => $project->responsible_role,
            'created_by' => $project->creator?->name,
        ];
    }
}
