<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionPresenter
{
    public static function toArray(UasMission $mission): array
    {
        $mission->loadMissing(['pilot', 'aircraft']);

        return [
            'id' => $mission->id,
            'mission_number' => $mission->mission_number,
            'purpose' => $mission->purpose,
            'client_project' => $mission->client_project,
            'location' => $mission->location,
            'latitude' => $mission->latitude,
            'longitude' => $mission->longitude,
            'mission_polygon' => $mission->mission_polygon ?? [],
            'operation_category' => $mission->operation_category,
            'aircraft' => $mission->aircraft ? [
                'id' => $mission->aircraft->id,
                'registration' => $mission->aircraft->registration,
                'model' => $mission->aircraft->model,
            ] : null,
            'pilot' => $mission->pilot ? [
                'id' => $mission->pilot->id,
                'display_name' => $mission->pilot->display_name,
            ] : null,
            'observers_crew' => $mission->observers_crew ?? [],
            'planned_start_at' => $mission->planned_start_at?->toISOString(),
            'planned_end_at' => $mission->planned_end_at?->toISOString(),
            'maximum_altitude_ft' => $mission->maximum_altitude_ft,
            'planned_distance_km' => $mission->planned_distance_km,
            'operation_visibility' => $mission->operation_visibility,
            'day_night' => $mission->day_night,
            'weather' => $mission->weather,
            'airspace_assessment' => $mission->airspace_assessment,
            'approvals' => $mission->approvals ?? [],
            'risk_assessment' => $mission->risk_assessment ?? [],
            'emergency_arrangements' => $mission->emergency_arrangements,
            'lifecycle_state' => $mission->lifecycle_state->value,
            'release_gate_state' => $mission->release_gate_state,
            'release_gate_results' => $mission->release_gate_results ?? [],
            'regulatory_source' => $mission->regulatory_source,
            'regulatory_source_version' => $mission->regulatory_source_version,
            'regulatory_effective_date' => $mission->regulatory_effective_date?->toDateString(),
            'regulatory_applicability' => $mission->regulatory_applicability,
            'responsible_role' => $mission->responsible_role,
            'created_at' => $mission->created_at?->toISOString(),
        ];
    }
}
