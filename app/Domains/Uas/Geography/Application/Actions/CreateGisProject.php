<?php

namespace App\Domains\Uas\Geography\Application\Actions;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateGisProject
{
    public function __construct(
        private readonly RecordAuditEntry $recordAuditEntry,
        private readonly CurrentOperatorContext $operatorContext,
    ) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasGisProject
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): UasGisProject {
            $operator = $this->operatorContext->requireFromRequest(request());
            $project = UasGisProject::query()->create([
                'uas_operator_id' => $operator->id,
                'project_code' => $data['project_code'],
                'name' => $data['name'],
                'project_type' => $data['project_type'],
                'client_or_stakeholder' => $data['client_or_stakeholder'] ?? null,
                'area_name' => $data['area_name'],
                'location_search_query' => $data['location_search_query'] ?? null,
                'centroid_latitude' => $data['centroid_latitude'] ?? null,
                'centroid_longitude' => $data['centroid_longitude'] ?? null,
                'area_boundary' => $data['area_boundary'] ?? null,
                'lifecycle_state' => 'plan',
                'source_reference' => $data['source_reference'],
                'source_version' => $data['source_version'] ?? null,
                'data_governance_notes' => $data['data_governance_notes'] ?? null,
                'evidence_required' => $data['evidence_required'],
                'responsible_role' => $data['responsible_role'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $project, 'gis_project.created', 'FR-GIS-001', 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41', null, $project->getAttributes(), $ipAddress, $userAgent));

            return $project;
        });
    }
}
