<?php

namespace App\Domains\Uas\Missions\Domain\Services;

class MissionGeometry
{
    public function point(?float $latitude, ?float $longitude, ?string $label = null): ?array
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => round($latitude, 7),
            'longitude' => round($longitude, 7),
            'label' => $label,
        ];
    }

    public function parsePoints(?string $lines): array
    {
        if (blank($lines)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', trim((string) $lines)))
            ->map(fn (string $line): ?array => $this->parseLine($line))
            ->filter()
            ->values()
            ->all();
    }

    public function summary(array $polygon = [], array $route = [], ?array $takeoff = null, ?array $landing = null, ?int $radius = null): array
    {
        return [
            'has_takeoff_point' => filled($takeoff),
            'has_landing_point' => filled($landing),
            'polygon_points' => count($polygon),
            'route_points' => count($route),
            'flight_radius_m' => $radius,
            'ready_for_map_review' => filled($takeoff) && filled($landing) && (count($polygon) >= 3 || count($route) >= 2 || $radius !== null),
        ];
    }

    private function parseLine(string $line): ?array
    {
        $parts = array_map('trim', explode(',', $line));

        if (count($parts) < 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        $latitude = (float) $parts[0];
        $longitude = (float) $parts[1];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return $this->point($latitude, $longitude, $parts[2] ?? null);
    }
}
