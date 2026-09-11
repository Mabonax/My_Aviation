<?php

namespace App\Domains\Uas\Pilots\Application\Queries;

use App\Domains\Uas\Pilots\Domain\Enums\PilotMedicalStatus;
use App\Domains\Uas\Pilots\Domain\Enums\PilotProfileStatus;
use App\Domains\Uas\Pilots\Domain\Enums\RadiotelephonyQualification;
use App\Domains\Uas\Pilots\Domain\Enums\RpcCategory;

class PilotProfileOptions
{
    /**
     * @return array<string, array<string, string>>
     */
    public function execute(): array
    {
        return [
            'rpcCategories' => $this->labels(RpcCategory::cases()),
            'medicalStatuses' => $this->labels(PilotMedicalStatus::cases()),
            'radiotelephonyQualifications' => $this->labels(RadiotelephonyQualification::cases()),
            'profileStatuses' => $this->labels(PilotProfileStatus::cases()),
        ];
    }

    /**
     * @param  array<int, object>  $cases
     * @return array<string, string>
     */
    private function labels(array $cases): array
    {
        $labels = [];

        foreach ($cases as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
