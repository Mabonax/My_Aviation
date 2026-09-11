<?php

namespace App\Domains\Uas\Tracks\Application\Queries;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Tracks\Domain\Models\UasFlightTrack;

class MissionTrackReport
{
    public function execute(UasMission $mission): array
    {
        $tracks = $mission->flightTracks()
            ->with('capturer')
            ->latest('started_at')
            ->latest('id')
            ->get()
            ->map(fn (UasFlightTrack $track): array => [
                'id' => $track->id,
                'source_type' => $track->source_type,
                'track_reference' => $track->track_reference,
                'started_at' => $track->started_at?->toISOString(),
                'ended_at' => $track->ended_at?->toISOString(),
                'point_count' => $track->point_count,
                'total_distance_km' => $track->total_distance_km,
                'max_altitude_ft' => $track->max_altitude_ft,
                'anomalies' => $track->anomalies ?? [],
                'notes' => $track->notes,
                'captured_by' => $track->capturer?->name,
                'regulatory_source' => $track->regulatory_source,
                'regulatory_source_version' => $track->regulatory_source_version,
                'regulatory_effective_date' => $track->regulatory_effective_date?->toDateString(),
            ])->values()->all();

        return [
            'tracks' => $tracks,
            'summary' => [
                'total' => count($tracks),
                'total_points' => collect($tracks)->sum('point_count'),
                'total_distance_km' => round((float) collect($tracks)->sum(fn (array $track): float => (float) $track['total_distance_km']), 3),
                'max_altitude_ft' => collect($tracks)->pluck('max_altitude_ft')->filter(fn ($value): bool => $value !== null)->max(),
                'anomaly_count' => collect($tracks)->sum(fn (array $track): int => count($track['anomalies'] ?? [])),
            ],
        ];
    }
}