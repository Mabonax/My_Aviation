<?php

namespace App\Domains\Uas\Geography\Application\Actions;

use App\Domains\Uas\Geography\Domain\Models\UasGisDataset;
use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateGisDataset
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasGisProjectMission $projectMission, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasGisDataset
    {
        return DB::transaction(function () use ($projectMission, $data, $actor, $ipAddress, $userAgent): UasGisDataset {
            $dataset = UasGisDataset::query()->create([
                'uas_gis_project_mission_id' => $projectMission->id,
                'dataset_code' => $data['dataset_code'],
                'title' => $data['title'],
                'dataset_type' => $data['dataset_type'],
                'capture_source' => $data['capture_source'],
                'storage_uri' => $data['storage_uri'],
                'checksum' => $data['checksum'] ?? null,
                'coordinate_reference_system' => $data['coordinate_reference_system'] ?? null,
                'resolution_cm' => $data['resolution_cm'] ?? null,
                'captured_at' => $data['captured_at'] ?? null,
                'processed_at' => $data['processed_at'] ?? null,
                'processing_status' => $data['processing_status'] ?? 'raw',
                'quality_status' => $data['quality_status'] ?? 'unchecked',
                'provenance_notes' => $data['provenance_notes'],
                'evidence_notes' => $data['evidence_notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($data['layers'] ?? [] as $layer) {
                $dataset->layers()->create([
                    'layer_name' => $layer['layer_name'],
                    'layer_type' => $layer['layer_type'],
                    'geometry_type' => $layer['geometry_type'],
                    'source_uri' => $layer['source_uri'] ?? null,
                    'style_metadata' => $layer['style_metadata'] ?? null,
                    'analysis_notes' => $layer['analysis_notes'] ?? null,
                    'status' => $layer['status'] ?? 'draft',
                ]);
            }

            $dataset->load('layers');

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $dataset, 'gis_dataset.created', 'FR-GIS-003', 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41', null, $dataset->getAttributes(), $ipAddress, $userAgent));

            return $dataset;
        });
    }
}
