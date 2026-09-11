<?php

namespace App\Domains\Uas\Geography\Domain\Services;

use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionSpatialRuleEvaluator
{
    private const RULES = [
        'prohibited_airspace' => ['result' => 'authorisation_required', 'basis' => 'regulatory', 'label' => 'Prohibited airspace review'],
        'restricted_airspace' => ['result' => 'authorisation_required', 'basis' => 'regulatory', 'label' => 'Restricted airspace review'],
        'controlled_airspace' => ['result' => 'authorisation_required', 'basis' => 'regulatory', 'label' => 'Controlled airspace review'],
        'aerodrome' => ['result' => 'authorisation_required', 'basis' => 'regulatory', 'label' => 'Aerodrome proximity review'],
        'strategic_area' => ['result' => 'review_required', 'basis' => 'internal_policy', 'label' => 'Strategic-area review'],
        'approved_operating_zone' => ['result' => 'clear', 'basis' => 'operator_evidence', 'label' => 'Approved operating zone match'],
    ];

    public function __construct(private readonly AviationOverlayCatalogue $catalogue) {}

    public function evaluate(UasMission $mission): array
    {
        $geometry = $this->missionGeometry($mission);

        if ($geometry['point_count'] === 0) {
            return [
                'state' => 'insufficient_geometry',
                'summary' => 'Mission geometry is not sufficient for spatial rule evaluation.',
                'matches' => [],
                'evaluated_at' => now()->toISOString(),
                'source_boundary' => 'Configured aviation overlay zones only; authoritative dataset completeness is not implied.',
            ];
        }

        $matches = collect($this->catalogue->activeZones())
            ->filter(fn (array $zone): bool => $this->intersects($geometry, $zone['geometry'] ?? []))
            ->map(fn (array $zone): array => $this->matchPayload($zone))
            ->values()
            ->all();

        return [
            'state' => $this->overallState($matches),
            'summary' => $this->summary($matches),
            'matches' => $matches,
            'evaluated_at' => now()->toISOString(),
            'source_boundary' => 'Configured aviation overlay zones only; authoritative dataset completeness is not implied.',
        ];
    }

    private function missionGeometry(UasMission $mission): array
    {
        $points = collect([
            $this->normalisePoint($mission->takeoff_point),
            $this->normalisePoint($mission->landing_point),
            $this->normalisePoint(['latitude' => $mission->latitude, 'longitude' => $mission->longitude]),
            ...array_map(fn (array $point): ?array => $this->normalisePoint($point), $mission->mission_polygon ?? []),
            ...array_map(fn (array $point): ?array => $this->normalisePoint($point), $mission->flight_route ?? []),
        ])->filter()->values()->all();

        return [
            'points' => $points,
            'polygon' => count($mission->mission_polygon ?? []) >= 3
                ? collect($mission->mission_polygon)->map(fn (array $point): ?array => $this->normalisePoint($point))->filter()->values()->all()
                : [],
            'bbox' => $this->boundingBox($points, $mission->flight_radius_m),
            'point_count' => count($points),
        ];
    }

    private function matchPayload(array $zone): array
    {
        $rule = self::RULES[$zone['zone_type']] ?? ['result' => 'review_required', 'basis' => 'configured_rule', 'label' => 'Configured spatial review'];

        return [
            'zone_type' => $zone['zone_type'],
            'zone_name' => $zone['name'],
            'identifier' => $zone['identifier'],
            'result' => $rule['result'],
            'basis' => $rule['basis'],
            'label' => $rule['label'],
            'message' => $this->message($zone, $rule['result']),
            'operational_notes' => $zone['operational_notes'],
            'source' => $zone['source'],
        ];
    }

    private function intersects(array $missionGeometry, array $zoneGeometry): bool
    {
        $zonePoints = $this->zonePoints($zoneGeometry);

        if ($zonePoints === []) {
            return false;
        }

        foreach ($missionGeometry['points'] as $point) {
            if ($this->pointInPolygon($point, $zonePoints)) {
                return true;
            }
        }

        foreach ($missionGeometry['polygon'] as $missionPoint) {
            foreach ($zonePoints as $zonePoint) {
                if ($this->pointInPolygon($zonePoint, $missionGeometry['polygon']) || $this->pointInPolygon($missionPoint, $zonePoints)) {
                    return true;
                }
            }
        }

        return $missionGeometry['bbox'] !== null && $this->boxesOverlap($missionGeometry['bbox'], $this->boundingBox($zonePoints));
    }

    private function zonePoints(array $geometry): array
    {
        if (($geometry['type'] ?? null) !== 'polygon') {
            return [];
        }

        return collect($geometry['points'] ?? [])
            ->map(fn (array $point): ?array => $this->normalisePoint($point))
            ->filter()
            ->values()
            ->all();
    }

    private function normalisePoint(?array $point): ?array
    {
        if ($point === null || ! isset($point['latitude'], $point['longitude']) || ! is_numeric($point['latitude']) || ! is_numeric($point['longitude'])) {
            return null;
        }

        return ['latitude' => (float) $point['latitude'], 'longitude' => (float) $point['longitude']];
    }

    private function boundingBox(array $points, ?int $radiusM = null): ?array
    {
        if ($points === []) {
            return null;
        }

        $latitudes = array_column($points, 'latitude');
        $longitudes = array_column($points, 'longitude');
        $padding = $radiusM ? $radiusM / 111_320 : 0.0;

        return [
            'min_latitude' => min($latitudes) - $padding,
            'max_latitude' => max($latitudes) + $padding,
            'min_longitude' => min($longitudes) - $padding,
            'max_longitude' => max($longitudes) + $padding,
        ];
    }

    private function boxesOverlap(array $a, ?array $b): bool
    {
        if ($b === null) {
            return false;
        }

        return $a['min_latitude'] <= $b['max_latitude']
            && $a['max_latitude'] >= $b['min_latitude']
            && $a['min_longitude'] <= $b['max_longitude']
            && $a['max_longitude'] >= $b['min_longitude'];
    }

    private function pointInPolygon(array $point, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = $polygon[$i]['latitude'];
            $xi = $polygon[$i]['longitude'];
            $yj = $polygon[$j]['latitude'];
            $xj = $polygon[$j]['longitude'];

            $intersects = (($yi > $point['latitude']) !== ($yj > $point['latitude']))
                && ($point['longitude'] < ($xj - $xi) * ($point['latitude'] - $yi) / (($yj - $yi) ?: 0.0000001) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function overallState(array $matches): string
    {
        if (collect($matches)->contains(fn (array $match): bool => $match['result'] === 'authorisation_required')) {
            return 'authorisation_required';
        }

        if (collect($matches)->contains(fn (array $match): bool => $match['result'] === 'review_required')) {
            return 'review_required';
        }

        return 'clear';
    }

    private function summary(array $matches): string
    {
        if ($matches === []) {
            return 'No configured aviation overlay intersections were found.';
        }

        return count($matches).' configured aviation overlay match'.(count($matches) === 1 ? '' : 'es').' found.';
    }

    private function message(array $zone, string $result): string
    {
        return match ($result) {
            'authorisation_required' => "Mission geometry intersects {$zone['name']}; review authorisation evidence before release.",
            'review_required' => "Mission geometry intersects {$zone['name']}; operations review is required.",
            default => "Mission geometry intersects {$zone['name']}; record supporting operating-zone evidence.",
        };
    }
}