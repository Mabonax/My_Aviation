<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Services\GisProjectMissionAssignment;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class GisProjectMissionOptions
{
    public function execute(?int $operatorId = null): array
    {
        return [
            'outputs' => GisProjectMissionAssignment::OUTPUTS,
            'field_verification' => GisProjectMissionAssignment::FIELD_VERIFICATION,
            'statuses' => GisProjectMissionAssignment::STATUSES,
            'missions' => UasMission::query()
                ->when($operatorId, fn ($query) => $query->where('uas_operator_id', $operatorId))
                ->whereDoesntHave('gisProjectAssignment')
                ->latest('planned_start_at')
                ->limit(100)
                ->get()
                ->map(fn (UasMission $mission): array => [
                    'id' => $mission->id,
                    'mission_number' => $mission->mission_number,
                    'purpose' => $mission->purpose,
                    'location' => $mission->location,
                    'lifecycle_state' => $mission->lifecycle_state->value,
                    'release_gate_state' => $mission->release_gate_state,
                ])
                ->all(),
        ];
    }
}
