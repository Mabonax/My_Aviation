<?php

namespace App\Domains\Uas\Missions\Domain\Services;

use App\Domains\Uas\Missions\Application\Queries\MissionComplianceSummary;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionReleaseGate
{
    public function __construct(private readonly MissionComplianceSummary $compliance) {}

    public function evaluate(UasMission $mission): array
    {
        $summary = $this->compliance->execute($mission);

        return [
            'state' => $summary['status'],
            'status' => $summary['status'],
            'label' => $summary['label'],
            'blocking_count' => $summary['blocking_count'],
            'warning_count' => $summary['warning_count'],
            'controls' => $summary['controls'],
            'aeronautical_information' => $summary['aeronautical_information'],
            'checks' => collect($summary['controls'])
                ->map(fn (array $control): array => [
                    'label' => $control['label'],
                    'result' => $control['status'],
                    'basis' => $control['basis'],
                    'message' => $control['summary'],
                ])
                ->values()
                ->all(),
            'evaluated_at' => $summary['evaluated_at'],
        ];
    }
}
