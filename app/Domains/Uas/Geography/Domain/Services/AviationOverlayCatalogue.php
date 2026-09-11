<?php

namespace App\Domains\Uas\Geography\Domain\Services;

use App\Domains\Uas\Geography\Domain\Models\AviationOverlayZone;

class AviationOverlayCatalogue
{
    public function layerSummary(): array
    {
        return AviationOverlayZone::query()
            ->selectRaw('zone_type, count(*) as total')
            ->where('status', 'active')
            ->groupBy('zone_type')
            ->orderBy('zone_type')
            ->pluck('total', 'zone_type')
            ->all();
    }

    public function activeZones(): array
    {
        return AviationOverlayZone::query()
            ->with('source')
            ->where('status', 'active')
            ->orderBy('zone_type')
            ->orderBy('name')
            ->get()
            ->map(fn (AviationOverlayZone $zone): array => [
                'id' => $zone->id,
                'zone_type' => $zone->zone_type,
                'name' => $zone->name,
                'identifier' => $zone->identifier,
                'geometry' => $zone->geometry,
                'operational_notes' => $zone->operational_notes,
                'source' => [
                    'name' => $zone->source->name,
                    'publisher' => $zone->source->publisher,
                    'source_url' => $zone->source->source_url,
                    'source_version' => $zone->source->source_version,
                    'authoritative' => $zone->source->authoritative,
                ],
            ])
            ->all();
    }
}
