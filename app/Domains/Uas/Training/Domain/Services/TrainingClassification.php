<?php

namespace App\Domains\Uas\Training\Domain\Services;

class TrainingClassification
{
    public const CLASSIFICATIONS = [
        'regulated_ato' => 'Regulated / ATO Training',
        'operator_internal_competency' => 'Operator Internal Competency',
        'general_education' => 'General Education',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'retired' => 'Retired',
    ];
}
