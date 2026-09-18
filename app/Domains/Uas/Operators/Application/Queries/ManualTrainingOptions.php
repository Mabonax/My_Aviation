<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Services\ManualTrainingControl;

class ManualTrainingOptions
{
    public function execute(): array
    {
        return [
            'types' => ManualTrainingControl::TYPES,
            'statuses' => ManualTrainingControl::STATUSES,
        ];
    }
}
