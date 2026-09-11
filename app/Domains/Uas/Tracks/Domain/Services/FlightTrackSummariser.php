<?php

namespace App\Domains\Uas\Tracks\Domain\Services;

use Illuminate\Validation\ValidationException;

class FlightTrackSummariser
{
    public function normalise(array $points): array
    {
        if (count($points) < 2) {
            throw ValidationException::withMessages([
                'points' => 'A flight track requires at least two points.',
            ]);
        }

        return collect($points)->map(function (array $point, int $index): array {
            foreach (['latitude', 'longitude'] as $field) {
                if (! isset($point[$field]) || ! is_numeric($point[$field])) {
                    throw ValidationException::withMessages([
                        "points.{$index}.{$field}" => 'Track point coordinates are required.',
                    ]);
                }
            }

            $latitude = (float) $point['latitude'];
            $longitude = (float) $point['longitude'];

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                throw ValidationException::withMessages([
                    "points.{$index}" => 'Track point coordinates are outside valid latitude/longitude bounds.',
                ]);
            }

            return [
                'latitude' => round($latitude, 7),
                'longitude' => round($longitude, 7),
                'altitude_ft' => isset($point['altitude_ft']) && is_numeric($point['altitude_ft']) ? max(0, (int) $point['altitude_ft']) : null,
                'recorded_at' => filled($point['recorded_at'] ?? null) ? (string) $point['recorded_at'] : null,
            ];
        })->values()->all();
    }

    public function summary(array $points): array
    {
        return [
            'point_count' => count($points),
            'total_distance_km' => round($this->distance($points), 3),
            'max_altitude_ft' => collect($points)->pluck('altitude_ft')->filter(fn ($value): bool => $value !== null)->max(),
        ];
    }

    private function distance(array $points): float
    {
        $distance = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            $distance += $this->haversine($points[$i - 1], $points[$i]);
        }

        return $distance;
    }

    private function haversine(array $from, array $to): float
    {
        $earthRadiusKm = 6371.0;
        $lat1 = deg2rad((float) $from['latitude']);
        $lat2 = deg2rad((float) $to['latitude']);
        $deltaLat = deg2rad((float) $to['latitude'] - (float) $from['latitude']);
        $deltaLon = deg2rad((float) $to['longitude'] - (float) $from['longitude']);

        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}