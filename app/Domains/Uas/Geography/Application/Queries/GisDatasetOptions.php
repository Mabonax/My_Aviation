<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Services\GisDatasetCatalogue;

class GisDatasetOptions
{
    public function execute(): array
    {
        return [
            'dataset_types' => GisDatasetCatalogue::DATASET_TYPES,
            'processing_statuses' => GisDatasetCatalogue::PROCESSING_STATUSES,
            'quality_statuses' => GisDatasetCatalogue::QUALITY_STATUSES,
            'layer_types' => GisDatasetCatalogue::LAYER_TYPES,
            'geometry_types' => GisDatasetCatalogue::GEOMETRY_TYPES,
            'layer_statuses' => GisDatasetCatalogue::LAYER_STATUSES,
        ];
    }
}
