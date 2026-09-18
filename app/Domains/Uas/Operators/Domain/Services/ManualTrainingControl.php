<?php

namespace App\Domains\Uas\Operators\Domain\Services;

class ManualTrainingControl
{
    public const TYPES = [
        'operator_internal_competency' => 'Operator Internal Competency',
        'safety_briefing' => 'Safety Briefing',
        'security_awareness' => 'Security Awareness',
        'regulated_training_review' => 'Regulated Training Review',
    ];

    public const STATUSES = [
        'required' => 'Required',
        'assigned' => 'Assigned',
        'completed' => 'Completed',
        'waived' => 'Waived',
    ];
}
