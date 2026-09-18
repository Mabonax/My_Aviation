<?php

namespace App\Domains\Uas\Operators\Domain\Services;

class ManualDistributionControl
{
    public const CHANNELS = [
        'manual_register' => 'Manual Register',
        'email' => 'Email',
        'briefing' => 'Briefing',
        'document_portal' => 'Document Portal',
        'printed_copy' => 'Printed Copy',
    ];

    public const STATUSES = [
        'required' => 'Required',
        'distributed' => 'Distributed',
        'waived' => 'Waived',
    ];
}
