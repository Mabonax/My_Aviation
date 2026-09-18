<?php

namespace App\Domains\Uas\Geography\Domain\Services;

use App\Domains\Uas\Missions\Domain\Models\UasMission;

class RegionalGeometryOverlap
{
    public function __construct(private readonly MissionSpatialRuleEvaluator $geometry) {}

    /** Unknown/unsupported geometry returns null, never a false clearance. */
    public function evaluate(UasMission $mission, ?array $shape, ?float $latitude, ?float $longitude, float $radiusM, float $bufferM): ?bool
    {
        $missionGeometry = $this->geometry->missionGeometry($mission);
        $points = $missionGeometry['points'];
        $noticePolygon = [];
        $noticeRoute = [];
        $noticePoints = [];
        if ($shape) {
            if (! is_string($shape['type'] ?? null)
                || (isset($shape['coordinates']) && ! is_array($shape['coordinates']))
                || (isset($shape['points']) && ! is_array($shape['points']))) {
                return null;
            }
            $type = strtolower($shape['type']);
            if ($type === 'polygon') {
                if (isset($shape['coordinates'])) {
                    if (count($shape['coordinates']) !== 1 || ! is_array($shape['coordinates'][0] ?? null)) {
                        return null;
                    }
                    $noticePolygon = $this->coordinates($shape['coordinates'][0]);
                } else {
                    $noticePolygon = $this->pointList($shape['points'] ?? []);
                }
                if (count($noticePolygon) < 3) {
                    return null;
                }
                $noticePoints = $noticePolygon;
            } elseif ($type === 'linestring') {
                $noticeRoute = $this->coordinates($shape['coordinates'] ?? []);
                if (count($noticeRoute) < 2) {
                    return null;
                }
                $noticePoints = $noticeRoute;
            } elseif ($type === 'point' && isset($shape['coordinates'][0], $shape['coordinates'][1])) {
                $noticePoints = $this->coordinates([$shape['coordinates']]);
            } else {
                return null;
            }
        } elseif ($latitude !== null && $longitude !== null) {
            $noticePoints = [['latitude' => $latitude, 'longitude' => $longitude]];
        }
        if ($points === [] || $noticePoints === []) {
            return null;
        }
        $all = [...$points, ...$noticePoints];
        foreach ($all as $point) {
            if (abs($point['latitude']) > 75 || abs($point['longitude']) > 180) {
                return null;
            }
        }
        // Local tangent projection: unsupported long-haul/polar/dateline geometry is unknown.
        if (max(array_column($all, 'longitude')) - min(array_column($all, 'longitude')) > 180) {
            return null;
        }
        $missionExtent = max(array_map(fn ($p) => $this->distance($points[0], $p), $points));
        $noticeExtent = max(array_map(fn ($p) => $this->distance($noticePoints[0], $p), $noticePoints));
        if ($missionExtent <= 200000 && $noticeExtent <= 200000
            && $this->distance($points[0], $noticePoints[0]) > $missionExtent + $noticeExtent + max(0, $radiusM) + max(0, (float) $mission->flight_radius_m) + max(0, $bufferM) + 100) {
            return false;
        }
        foreach ($all as $point) {
            if ($this->distance($all[0], $point) > 500000) {
                return null;
            }
        }
        foreach ($points as $point) {
            if ($noticePolygon !== [] && $this->geometry->pointInPolygon($point, $noticePolygon)) {
                return true;
            }
        }
        foreach ($noticePoints as $point) {
            if ($missionGeometry['polygon'] !== [] && $this->geometry->pointInPolygon($point, $missionGeometry['polygon'])) {
                return true;
            }
        }
        $missionRoute = $this->pointList($mission->flight_route ?? []);
        $missionSegments = [...$this->segments($missionGeometry['polygon'], true), ...$this->segments($missionRoute)];
        $noticeSegments = [...$this->segments($noticePolygon, true), ...$this->segments($noticeRoute)];
        $padding = max(0, $radiusM) + max(0, (float) $mission->flight_radius_m) + max(0, $bufferM);
        $minimum = INF;
        foreach ($points as $a) {
            foreach ($noticePoints as $b) {
                $minimum = min($minimum, $this->distance($a, $b));
            }
            foreach ($noticeSegments as [$b, $c]) {
                $minimum = min($minimum, $this->segmentDistance($a, $b, $c));
            }
        }
        foreach ($noticePoints as $a) {
            foreach ($missionSegments as [$b, $c]) {
                $minimum = min($minimum, $this->segmentDistance($a, $b, $c));
            }
        }
        foreach ($missionSegments as [$a, $b]) {
            foreach ($noticeSegments as [$c, $d]) {
                if ($this->crosses($a, $b, $c, $d)) {
                    return true;
                }
            }
        }

        return $minimum <= $padding * 1.01 + 1;
    }

    public function points(UasMission $mission): array
    {
        return $this->geometry->missionGeometry($mission)['points'];
    }

    private function coordinates(array $coordinates): array
    {
        $points = [];
        foreach ($coordinates as $coordinate) {
            if (! is_array($coordinate) || ! isset($coordinate[0], $coordinate[1]) || ! is_numeric($coordinate[0]) || ! is_numeric($coordinate[1])) {
                return [];
            }
            $points[] = ['latitude' => (float) $coordinate[1], 'longitude' => (float) $coordinate[0]];
        }

        return $points;
    }

    private function pointList(array $points): array
    {
        return array_values(array_filter(array_map(fn ($p) => is_array($p) ? $this->geometry->normalisePoint($p) : null, $points)));
    }

    private function segments(array $points, bool $closed = false): array
    {
        if ($closed && count($points) >= 3) {
            $points[] = $points[0];
        }
        $segments = [];
        for ($i = 1; $i < count($points); $i++) {
            $segments[] = [$points[$i - 1], $points[$i]];
        }

        return $segments;
    }

    public function distance(array $a, array $b): float
    {
        $lat = deg2rad($b['latitude'] - $a['latitude']);
        $lon = deg2rad($b['longitude'] - $a['longitude']);
        $h = sin($lat / 2) ** 2 + cos(deg2rad($a['latitude'])) * cos(deg2rad($b['latitude'])) * sin($lon / 2) ** 2;

        return 6371008.8 * 2 * asin(sqrt(min(1, max(0, $h))));
    }

    private function segmentDistance(array $point, array $a, array $b): float
    {
        $scale = cos(deg2rad($point['latitude']));
        $ax = deg2rad($a['longitude'] - $point['longitude']) * 6371008.8 * $scale;
        $ay = deg2rad($a['latitude'] - $point['latitude']) * 6371008.8;
        $bx = deg2rad($b['longitude'] - $point['longitude']) * 6371008.8 * $scale;
        $by = deg2rad($b['latitude'] - $point['latitude']) * 6371008.8;
        $length = ($bx - $ax) ** 2 + ($by - $ay) ** 2;
        $t = $length > 0 ? max(0, min(1, (-$ax * ($bx - $ax) - $ay * ($by - $ay)) / $length)) : 0;

        return hypot($ax + $t * ($bx - $ax), $ay + $t * ($by - $ay));
    }

    private function crosses(array $a, array $b, array $c, array $d): bool
    {
        $cross = fn ($p, $q, $r) => ($q['longitude'] - $p['longitude']) * ($r['latitude'] - $p['latitude']) - ($q['latitude'] - $p['latitude']) * ($r['longitude'] - $p['longitude']);

        return $cross($a, $b, $c) * $cross($a, $b, $d) < 0 && $cross($c, $d, $a) * $cross($c, $d, $b) < 0;
    }
}
