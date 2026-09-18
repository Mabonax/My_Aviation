<?php

namespace App\Domains\Uas\Aircraft\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;

class AircraftCatalogueFilters
{
    public function execute(): array
    {
        return [
            'manufacturers' => UasAircraftModel::query()
                ->join('uas_manufacturers', 'uas_manufacturers.id', '=', 'uas_aircraft_models.manufacturer_id')
                ->select('uas_manufacturers.id', 'uas_manufacturers.name')
                ->distinct()
                ->orderBy('uas_manufacturers.name')
                ->get()
                ->map(fn ($manufacturer): array => ['id' => $manufacturer->id, 'label' => $manufacturer->name])
                ->values()
                ->all(),
            'aircraft_types' => UasAircraftModel::query()
                ->whereNotNull('aircraft_type')
                ->distinct()
                ->orderBy('aircraft_type')
                ->pluck('aircraft_type')
                ->values()
                ->all(),
            'statuses' => [
                'current' => 'Current',
                'deprecated' => 'Deprecated',
            ],
            'catalogue_statuses' => [
                'draft' => 'Draft',
                'verified' => 'Verified',
                'deprecated' => 'Deprecated',
            ],
        ];
    }
}
