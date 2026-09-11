<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Services\MissionLifecycle;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;

class MissionOptions
{
    public function __construct(private readonly MissionLifecycle $lifecycle) {}

    public function execute(): array
    {
        return [
            'aircraft' => UasAircraft::query()
                ->orderBy('registration')
                ->get(['id', 'registration', 'model'])
                ->map(fn (UasAircraft $aircraft): array => [
                    'id' => $aircraft->id,
                    'label' => trim("{$aircraft->registration} {$aircraft->model}"),
                ])
                ->all(),
            'pilots' => UasPilot::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (UasPilot $pilot): array => [
                    'id' => $pilot->id,
                    'label' => $pilot->display_name,
                ])
                ->all(),
            'lifecycle_states' => $this->lifecycle->states(),
            'operation_categories' => ['standard' => 'Standard', 'survey' => 'Survey', 'inspection' => 'Inspection', 'emergency' => 'Emergency'],
            'visibility_modes' => ['vlos' => 'VLOS', 'evlos' => 'EVLOS', 'bvlos' => 'BVLOS'],
            'day_night_modes' => ['day' => 'Day', 'night' => 'Night'],
        ];
    }
}
