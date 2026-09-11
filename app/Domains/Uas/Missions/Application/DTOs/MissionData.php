<?php

namespace App\Domains\Uas\Missions\Application\DTOs;

final readonly class MissionData
{
    public function __construct(
        public string $purpose,
        public ?string $clientProject,
        public string $location,
        public ?string $locationSearchQuery,
        public ?float $latitude,
        public ?float $longitude,
        public ?array $takeoffPoint,
        public ?array $landingPoint,
        public ?array $missionPolygon,
        public ?array $flightRoute,
        public ?int $flightRadiusM,
        public string $operationCategory,
        public ?int $uasAircraftId,
        public ?int $uasPilotId,
        public ?array $observersCrew,
        public ?string $plannedStartAt,
        public ?string $plannedEndAt,
        public ?int $maximumAltitudeFt,
        public ?float $plannedDistanceKm,
        public string $operationVisibility,
        public string $dayNight,
        public ?string $weather,
        public ?string $airspaceAssessment,
        public ?array $approvals,
        public ?array $riskAssessment,
        public ?string $emergencyArrangements,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            purpose: $data['purpose'],
            clientProject: $data['client_project'] ?? null,
            location: $data['location'],
            locationSearchQuery: $data['location_search_query'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            takeoffPoint: $data['takeoff_point'] ?? null,
            landingPoint: $data['landing_point'] ?? null,
            missionPolygon: $data['mission_polygon'] ?? null,
            flightRoute: $data['flight_route'] ?? null,
            flightRadiusM: isset($data['flight_radius_m']) ? (int) $data['flight_radius_m'] : null,
            operationCategory: $data['operation_category'],
            uasAircraftId: isset($data['uas_aircraft_id']) ? (int) $data['uas_aircraft_id'] : null,
            uasPilotId: isset($data['uas_pilot_id']) ? (int) $data['uas_pilot_id'] : null,
            observersCrew: $data['observers_crew'] ?? null,
            plannedStartAt: $data['planned_start_at'] ?? null,
            plannedEndAt: $data['planned_end_at'] ?? null,
            maximumAltitudeFt: isset($data['maximum_altitude_ft']) ? (int) $data['maximum_altitude_ft'] : null,
            plannedDistanceKm: isset($data['planned_distance_km']) ? (float) $data['planned_distance_km'] : null,
            operationVisibility: $data['operation_visibility'],
            dayNight: $data['day_night'],
            weather: $data['weather'] ?? null,
            airspaceAssessment: $data['airspace_assessment'] ?? null,
            approvals: $data['approvals'] ?? null,
            riskAssessment: $data['risk_assessment'] ?? null,
            emergencyArrangements: $data['emergency_arrangements'] ?? null,
        );
    }

    public function toModelAttributes(): array
    {
        return [
            'purpose' => $this->purpose,
            'client_project' => $this->clientProject,
            'location' => $this->location,
            'location_search_query' => $this->locationSearchQuery,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'takeoff_point' => $this->takeoffPoint,
            'landing_point' => $this->landingPoint,
            'mission_polygon' => $this->missionPolygon,
            'flight_route' => $this->flightRoute,
            'flight_radius_m' => $this->flightRadiusM,
            'operation_category' => $this->operationCategory,
            'uas_aircraft_id' => $this->uasAircraftId,
            'uas_pilot_id' => $this->uasPilotId,
            'observers_crew' => $this->observersCrew,
            'planned_start_at' => $this->plannedStartAt,
            'planned_end_at' => $this->plannedEndAt,
            'maximum_altitude_ft' => $this->maximumAltitudeFt,
            'planned_distance_km' => $this->plannedDistanceKm,
            'operation_visibility' => $this->operationVisibility,
            'day_night' => $this->dayNight,
            'weather' => $this->weather,
            'airspace_assessment' => $this->airspaceAssessment,
            'approvals' => $this->approvals,
            'risk_assessment' => $this->riskAssessment,
            'emergency_arrangements' => $this->emergencyArrangements,
        ];
    }
}
