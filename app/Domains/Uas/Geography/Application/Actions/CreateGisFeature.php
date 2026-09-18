<?php

namespace App\Domains\Uas\Geography\Application\Actions;

use App\Domains\Uas\Geography\Domain\Models\UasGisFeature;
use App\Domains\Uas\Geography\Domain\Models\UasGisSpatialLayer;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateGisFeature
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasGisSpatialLayer $spatialLayer, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasGisFeature
    {
        return DB::transaction(function () use ($spatialLayer, $data, $actor, $ipAddress, $userAgent): UasGisFeature {
            $feature = UasGisFeature::query()->create([
                'uas_gis_spatial_layer_id' => $spatialLayer->id,
                'feature_code' => $data['feature_code'],
                'name' => $data['name'],
                'feature_type' => $data['feature_type'],
                'geometry_reference' => $data['geometry_reference'],
                'confidence_score' => $data['confidence_score'] ?? null,
                'verification_status' => $data['verification_status'] ?? 'unverified',
                'interpretation_notes' => $data['interpretation_notes'],
                'evidence_notes' => $data['evidence_notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($data['opportunities_findings'] ?? [] as $record) {
                $feature->opportunitiesFindings()->create([
                    'record_type' => $record['record_type'],
                    'category' => $record['category'],
                    'title' => $record['title'],
                    'description' => $record['description'],
                    'significance' => $record['significance'] ?? 'medium',
                    'recommended_action' => $record['recommended_action'] ?? null,
                    'priority' => $record['priority'] ?? 'routine',
                    'status' => $record['status'] ?? 'draft',
                    'evidence_reference' => $record['evidence_reference'] ?? null,
                    'due_date' => $record['due_date'] ?? null,
                    'responsible_role' => $record['responsible_role'] ?? null,
                    'created_by' => $actor->id,
                ]);
            }

            $feature->load('opportunitiesFindings');

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $feature, 'gis_feature.created', 'FR-GIS-004', 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41', null, $feature->getAttributes(), $ipAddress, $userAgent));

            return $feature;
        });
    }
}
