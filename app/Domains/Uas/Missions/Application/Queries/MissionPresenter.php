<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Documents\Application\Queries\EvidenceSummary;

class MissionPresenter
{
    public static function toArray(UasMission $mission, bool $includeComplianceDetails = true): array
    {
        $mission->loadMissing(['evidenceLinks.document', 'operator', 'pilot', 'aircraft']);
        $compliance = $includeComplianceDetails
            ? app(MissionComplianceSummary::class)->execute($mission)
            : app(MissionComplianceSummary::class)->lightweight($mission);
        $postFlightPropagation = $includeComplianceDetails
            ? app(PostFlightPropagationSummary::class)->execute($mission)
            : [
                'state' => $mission->post_flight_propagation_state ?? 'pending',
                'label' => str($mission->post_flight_propagation_state ?? 'pending')->replace('_', ' ')->title()->toString(),
                'propagated_at' => $mission->post_flight_propagated_at?->toISOString(),
            ];

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
            'operator' => $mission->operator ? [
                'id' => $mission->operator->id,
                'legal_entity' => $mission->operator->legal_entity,
            ] : null,
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
            'actual_takeoff_at' => $mission->actual_takeoff_at?->toISOString(),
            'actual_landing_at' => $mission->actual_landing_at?->toISOString(),
            'actual_flight_duration_minutes' => $mission->actual_flight_duration_minutes,
            'completed_at' => $mission->completed_at?->toISOString(),
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
            'compliance' => $compliance,
            'post_flight_propagation' => $postFlightPropagation,
            'evidence' => app(EvidenceSummary::class)->for($mission),
            'regulatory_source' => $mission->regulatory_source,
            'regulatory_source_version' => $mission->regulatory_source_version,
            'regulatory_effective_date' => $mission->regulatory_effective_date?->toDateString(),
            'regulatory_applicability' => $mission->regulatory_applicability,
            'responsible_role' => $mission->responsible_role,
            'created_at' => $mission->created_at?->toISOString(),
        ];
    }
}
