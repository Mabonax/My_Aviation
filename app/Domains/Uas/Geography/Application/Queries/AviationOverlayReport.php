<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Models\AviationOverlaySource;
use App\Domains\Uas\Geography\Domain\Services\AviationOverlayCatalogue;

class AviationOverlayReport
{
    public function __construct(private readonly AviationOverlayCatalogue $catalogue) {}

    public function execute(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'layer_summary' => $this->catalogue->layerSummary(),
            'sources' => AviationOverlaySource::query()
                ->withCount('zones')
                ->orderBy('publisher')
                ->orderBy('name')
                ->get()
                ->map(fn (AviationOverlaySource $source): array => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'publisher' => $source->publisher,
                    'source_url' => $source->source_url,
                    'source_version' => $source->source_version,
                    'effective_date' => $source->effective_date?->toDateString(),
                    'authoritative' => $source->authoritative,
                    'usage_notes' => $source->usage_notes,
                    'zones_count' => $source->zones_count,
                ])
                ->all(),
            'zones' => $this->catalogue->activeZones(),
        ];
    }
}
