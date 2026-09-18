<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Services;

use App\Domains\Uas\Missions\Domain\Models\UasMission;

class BriefingFingerprint
{
    public function mission(UasMission $mission): string
    {
        return hash('sha256', json_encode($mission->only(['uas_operator_id', 'uas_pilot_id', 'uas_aircraft_id', 'latitude', 'longitude', 'takeoff_point', 'landing_point', 'mission_polygon', 'flight_route', 'flight_radius_m', 'planned_start_at', 'planned_end_at', 'maximum_altitude_ft', 'aeronautical_context', 'operation_category', 'operation_visibility', 'day_night']), JSON_THROW_ON_ERROR));
    }

    public function policy(): string
    {
        return hash('sha256', json_encode([
            'version' => config('aeronautical.assessment_version'), 'horizontal_buffer_m' => config('aeronautical.horizontal_buffer_m'),
            'vertical_buffer_ft' => config('aeronautical.vertical_buffer_ft'), 'briefing_valid_minutes' => config('aeronautical.briefing_valid_minutes'),
            'required_providers' => config('aeronautical.required_providers'),
            'providers' => collect(config('aeronautical.providers'))->map(fn ($p) => array_intersect_key($p, array_flip(['adapter', 'enabled', 'max_age_minutes', 'operational', 'approved', 'approval_evidence'])))->all(),
        ], JSON_THROW_ON_ERROR));
    }
}
