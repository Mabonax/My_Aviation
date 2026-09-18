<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;

class OperatorOptions
{
    public const STATUSES = [
        'draft' => 'Draft',
        'application' => 'Application',
        'active' => 'Active',
        'renewal_due' => 'Renewal Due',
        'suspended' => 'Suspended',
        'expired' => 'Expired',
    ];

    public function execute(): array
    {
        return [
            'statuses' => self::STATUSES,
            'aircraft' => UasAircraft::query()->orderBy('registration')->get()->map(fn (UasAircraft $aircraft): array => [
                'id' => $aircraft->id,
                'label' => "{$aircraft->registration} {$aircraft->model}",
            ])->values()->all(),
            'pilots' => UasPilot::query()->orderBy('last_name')->orderBy('first_name')->get()->map(fn (UasPilot $pilot): array => [
                'id' => $pilot->id,
                'label' => $pilot->display_name,
            ])->values()->all(),
        ];
    }
}