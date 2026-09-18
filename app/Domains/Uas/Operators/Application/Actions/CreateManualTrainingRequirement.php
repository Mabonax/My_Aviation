<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualTrainingRequirement;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateManualTrainingRequirement
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-004; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Operations Manual amendment training trigger for mandatory training and competency requirements.',
    ];

    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperationsManualRevision $manualRevision, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperationsManualTrainingRequirement
    {
        return DB::transaction(function () use ($manualRevision, $data, $actor, $ipAddress, $userAgent): UasOperationsManualTrainingRequirement {
            $requirement = UasOperationsManualTrainingRequirement::query()->create([
                ...$data,
                'manual_revision_id' => $manualRevision->id,
                'requirement_type' => $data['requirement_type'] ?? 'operator_internal_competency',
                'training_status' => $data['training_status'] ?? 'required',
                'affected_roles' => $data['affected_roles'] ?? [],
                'evidence_references' => $data['evidence_references'] ?? [],
                ...self::TRACEABILITY,
                'created_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $requirement, 'operator.manual_training_requirement.created', 'FR-OM-004', self::TRACEABILITY['regulatory_source'], null, $requirement->getAttributes(), $ipAddress, $userAgent));

            return $requirement;
        });
    }
}
