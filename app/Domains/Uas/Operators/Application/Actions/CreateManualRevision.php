<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateManualRevision
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Version-controlled Operations Manual revision control with effective date, approval status, authority reference, sections, change summary and superseded revision.',
    ];

    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperator $operator, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperationsManualRevision
    {
        return DB::transaction(function () use ($operator, $data, $actor, $ipAddress, $userAgent): UasOperationsManualRevision {
            $revision = UasOperationsManualRevision::query()->create([
                ...$data,
                'uas_operator_id' => $operator->id,
                'approval_status' => $data['approval_status'] ?? 'draft',
                'sections' => $data['sections'] ?? [],
                'evidence_references' => $data['evidence_references'] ?? [],
                ...self::TRACEABILITY,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if ($revision->superseded_revision_id) {
                UasOperationsManualRevision::query()->whereKey($revision->superseded_revision_id)->update(['approval_status' => 'superseded']);
            }

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $revision, 'operator.manual_revision.created', 'FR-OM-001', self::TRACEABILITY['regulatory_source'], null, $revision->getAttributes(), $ipAddress, $userAgent));

            return $revision;
        });
    }
}