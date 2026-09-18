<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Services\ManualDistributionControl;

class ManualDistributionOptions
{
    public function execute(): array
    {
        return [
            'channels' => ManualDistributionControl::CHANNELS,
            'statuses' => ManualDistributionControl::STATUSES,
        ];
    }
}
