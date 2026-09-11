<?php

namespace App\Domains\Uas\Defects\Domain\Services;

class DefectServiceabilityImpact
{
    public const SOURCES = [
        'pre_flight' => 'Pre-flight',
        'in_flight' => 'In-flight',
        'post_flight' => 'Post-flight',
        'maintenance' => 'Maintenance',
        'inspection' => 'Inspection',
    ];

    public const SEVERITIES = [
        'observation' => 'Observation',
        'minor' => 'Minor',
        'maintenance_required' => 'Maintenance Required',
        'flight_restricted' => 'Flight Restricted',
        'ground_aircraft' => 'Ground Aircraft',
    ];

    public function impactForSeverity(string $severity): string
    {
        return match ($severity) {
            'ground_aircraft' => 'grounded',
            'flight_restricted' => 'flight_restricted',
            'maintenance_required' => 'maintenance_required',
            default => 'none',
        };
    }

    public function aircraftStatusForImpact(string $impact): ?string
    {
        return match ($impact) {
            'grounded' => 'grounded',
            'flight_restricted' => 'flight_restricted',
            'maintenance_required' => 'unserviceable',
            default => null,
        };
    }
}