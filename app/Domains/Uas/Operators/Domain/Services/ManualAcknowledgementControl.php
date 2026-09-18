<?php

namespace App\Domains\Uas\Operators\Domain\Services;

class ManualAcknowledgementControl
{
    public const STATUSES = [
        'pending' => 'Pending',
        'acknowledged' => 'Acknowledged',
    ];

    public const STATEMENT = 'I acknowledge receipt and readership of this Operations Manual revision.';
}
