<?php

namespace App\Domains\Uas\Geography\Application\Actions;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignMissionToGisProject
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasGisProject $project, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasGisProjectMission
    {
        return DB::transaction(function () use ($project, $data, $actor, $ipAddress, $userAgent): UasGisProjectMission {
            $assignment = UasGisProjectMission::query()->create([
                'uas_gis_project_id' => $project->id,
                'uas_mission_id' => $data['uas_mission_id'],
                'mapping_objective' => $data['mapping_objective'],
                'capture_plan' => $data['capture_plan'],
                'expected_outputs' => $data['expected_outputs'],
                'field_verification_required' => $data['field_verification_required'],
                'evidence_notes' => $data['evidence_notes'] ?? null,
                'status' => $data['status'] ?? 'planned',
                'assigned_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $assignment, 'gis_project_mission.assigned', 'FR-GIS-002', 'UAS Compliance & Operations Platform FRS section 32 and Phase 5 section 41', null, $assignment->getAttributes(), $ipAddress, $userAgent));

            return $assignment;
        });
    }
}
