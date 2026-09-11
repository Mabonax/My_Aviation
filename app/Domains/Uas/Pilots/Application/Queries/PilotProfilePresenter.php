<?php

namespace App\Domains\Uas\Pilots\Application\Queries;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;

class PilotProfilePresenter
{
    public static function toArray(UasPilot $pilot): array
    {
        return [
            'id' => $pilot->id,
            'display_name' => $pilot->display_name,
            'user_id' => $pilot->user_id,
            'employee_number' => $pilot->employee_number,
            'first_name' => $pilot->first_name,
            'last_name' => $pilot->last_name,
            'preferred_name' => $pilot->preferred_name,
            'email' => $pilot->email,
            'phone' => $pilot->phone,
            'nationality' => $pilot->nationality,
            'date_of_birth' => $pilot->date_of_birth?->toDateString(),
            'sacaa_certificate_number' => $pilot->sacaa_certificate_number,
            'rpc_category' => $pilot->rpc_category->value,
            'ratings' => $pilot->ratings ?? [],
            'medical_status' => $pilot->medical_status->value,
            'radiotelephony_qualification' => $pilot->radiotelephony_qualification->value,
            'language_proficiency' => $pilot->language_proficiency,
            'training_history' => $pilot->training_history ?? [],
            'examiner_records' => $pilot->examiner_records ?? [],
            'operator_affiliations' => $pilot->operator_affiliations ?? [],
            'supporting_document_references' => $pilot->supporting_document_references ?? [],
            'profile_status' => $pilot->profile_status->value,
            'regulatory_source' => $pilot->regulatory_source,
            'regulatory_source_version' => $pilot->regulatory_source_version,
            'regulatory_effective_date' => $pilot->regulatory_effective_date?->toDateString(),
            'regulatory_applicability' => $pilot->regulatory_applicability,
            'responsible_role' => $pilot->responsible_role,
            'notes' => $pilot->notes,
            'created_at' => $pilot->created_at?->toISOString(),
            'updated_at' => $pilot->updated_at?->toISOString(),
        ];
    }
}
