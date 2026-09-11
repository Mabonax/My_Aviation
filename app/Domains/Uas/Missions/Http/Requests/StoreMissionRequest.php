<?php

namespace App\Domains\Uas\Missions\Http\Requests;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', UasMission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'purpose' => ['required', 'string', 'max:180'],
            'client_project' => ['nullable', 'string', 'max:180'],
            'location' => ['required', 'string', 'max:180'],
            'location_search_query' => ['nullable', 'string', 'max:180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'takeoff_point' => ['nullable', 'array'],
            'takeoff_point.latitude' => ['required_with:takeoff_point', 'numeric', 'between:-90,90'],
            'takeoff_point.longitude' => ['required_with:takeoff_point', 'numeric', 'between:-180,180'],
            'takeoff_point.label' => ['nullable', 'string', 'max:120'],
            'landing_point' => ['nullable', 'array'],
            'landing_point.latitude' => ['required_with:landing_point', 'numeric', 'between:-90,90'],
            'landing_point.longitude' => ['required_with:landing_point', 'numeric', 'between:-180,180'],
            'landing_point.label' => ['nullable', 'string', 'max:120'],
            'mission_polygon' => ['nullable', 'array'],
            'mission_polygon.*.latitude' => ['required_with:mission_polygon', 'numeric', 'between:-90,90'],
            'mission_polygon.*.longitude' => ['required_with:mission_polygon', 'numeric', 'between:-180,180'],
            'flight_route' => ['nullable', 'array'],
            'flight_route.*.latitude' => ['required_with:flight_route', 'numeric', 'between:-90,90'],
            'flight_route.*.longitude' => ['required_with:flight_route', 'numeric', 'between:-180,180'],
            'flight_radius_m' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'operation_category' => ['required', 'string', 'max:80'],
            'uas_aircraft_id' => ['nullable', 'integer', 'exists:uas_aircraft,id'],
            'uas_pilot_id' => ['nullable', 'integer', 'exists:uas_pilots,id'],
            'observers_crew' => ['nullable', 'array'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
            'maximum_altitude_ft' => ['nullable', 'integer', 'min:0', 'max:40000'],
            'planned_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'operation_visibility' => ['required', Rule::in(['vlos', 'evlos', 'bvlos'])],
            'day_night' => ['required', Rule::in(['day', 'night'])],
            'weather' => ['nullable', 'string', 'max:5000'],
            'airspace_assessment' => ['nullable', 'string', 'max:5000'],
            'approvals' => ['nullable', 'array'],
            'risk_assessment' => ['nullable', 'array'],
            'emergency_arrangements' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
