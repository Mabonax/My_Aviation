<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Documents\Application\Queries\EvidenceSummary;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;

class OperatorPresenter
{
    public static function toArray(UasOperator $operator): array
    {
        return [
            'id' => $operator->id,
            'legal_entity' => $operator->legal_entity,
            'trading_name' => $operator->trading_name,
            'registration_number' => $operator->registration_number,
            'uasoc_number' => $operator->uasoc_number,
            'certificate_issue_date' => $operator->certificate_issue_date?->toDateString(),
            'certificate_expiry_date' => $operator->certificate_expiry_date?->toDateString(),
            'status' => $operator->status,
            'accountable_manager' => $operator->accountable_manager,
            'responsible_person_flight_operations' => $operator->responsible_person_flight_operations,
            'responsible_person_aircraft' => $operator->responsible_person_aircraft,
            'safety_manager' => $operator->safety_manager,
            'security_coordinator' => $operator->security_coordinator,
            'operating_bases' => $operator->operating_bases ?? [],
            'approved_aircraft' => $operator->approved_aircraft ?? [],
            'approved_pilots' => $operator->approved_pilots ?? [],
            'operations_specifications' => $operator->operations_specifications ?? [],
            'evidence_references' => $operator->evidence_references ?? [],
            'evidence' => app(EvidenceSummary::class)->for($operator),
            'regulatory_source' => $operator->regulatory_source,
            'regulatory_source_version' => $operator->regulatory_source_version,
            'regulatory_effective_date' => $operator->regulatory_effective_date?->toDateString(),
            'regulatory_applicability' => $operator->regulatory_applicability,
            'responsible_role' => $operator->responsible_role,
            'created_at' => $operator->created_at?->toISOString(),
        ];
    }
}
