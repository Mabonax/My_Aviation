<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Queries;

use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;

class AeronauticalItemPresenter
{
    public static function toArray(AeronauticalInformationItem $item): array
    {
        $item->loadMissing('source');

        return [
            'id' => $item->id, 'uuid' => $item->uuid, 'type' => $item->information_type, 'title' => $item->title,
            'summary' => $item->summary, 'provider' => $item->provider, 'source_identifier' => $item->source_identifier,
            'source_revision' => $item->source_revision, 'source_classification' => $item->source_classification,
            'usable_for_release' => $item->usable_for_release, 'status' => $item->superseded_at ? 'superseded' : $item->status,
            'issued_at' => $item->issued_at?->toISOString(), 'effective_from' => $item->effective_from?->toISOString(),
            'effective_until' => $item->effective_until?->toISOString(), 'permanent' => $item->permanent,
            'received_at' => $item->received_at?->toISOString(), 'checksum' => $item->checksum,
            'superseded_at' => $item->superseded_at?->toISOString(), 'supersedes_id' => $item->supersedes_id,
            'source' => $item->source->toArray(), 'interpretation' => $item->interpretation,
            'geometry' => ['type' => $item->geometry_type, 'latitude' => $item->latitude, 'longitude' => $item->longitude, 'radius_nm' => $item->radius_nm, 'geojson' => $item->geometry_json],
        ];
    }
}
