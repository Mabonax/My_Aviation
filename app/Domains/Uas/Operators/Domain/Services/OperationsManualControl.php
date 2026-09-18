<?php

namespace App\Domains\Uas\Operators\Domain\Services;

class OperationsManualControl
{
    public const APPROVAL_STATUSES = [
        'draft' => 'Draft',
        'internal_review' => 'Internal Review',
        'submitted_to_authority' => 'Submitted to Authority',
        'approved' => 'Approved',
        'superseded' => 'Superseded',
        'rejected' => 'Rejected',
    ];
}