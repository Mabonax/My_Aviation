<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;

class CertificateCasePresenter
{
    public static function toArray(UasOperatorCertificateCase $case): array
    {
        $case->loadMissing(['operator', 'opener']);

        return [
            'id' => $case->id,
            'operator' => ['id' => $case->operator->id, 'legal_entity' => $case->operator->legal_entity],
            'case_number' => $case->case_number,
            'case_type' => $case->case_type,
            'status' => $case->status,
            'deadline_at' => $case->deadline_at?->toDateString(),
            'evidence_requirements' => $case->evidence_requirements ?? [],
            'outstanding_documents' => $case->outstanding_documents ?? [],
            'fleet_scope' => $case->fleet_scope ?? [],
            'personnel_scope' => $case->personnel_scope ?? [],
            'ops_spec_scope' => $case->ops_spec_scope ?? [],
            'operations_manual_revision' => $case->operations_manual_revision,
            'fees' => $case->fees ?? [],
            'submission_status' => $case->submission_status,
            'authority_correspondence' => $case->authority_correspondence ?? [],
            'outcome' => $case->outcome,
            'submitted_at' => $case->submitted_at?->toISOString(),
            'decided_at' => $case->decided_at?->toISOString(),
            'opened_by' => $case->opener?->name,
            'regulatory_source' => $case->regulatory_source,
            'regulatory_source_version' => $case->regulatory_source_version,
            'regulatory_effective_date' => $case->regulatory_effective_date?->toDateString(),
        ];
    }
}