<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Services\GisFeatureCatalogue;

class GisFeatureOptions
{
    public function execute(): array
    {
        return [
            'feature_types' => GisFeatureCatalogue::FEATURE_TYPES,
            'verification_statuses' => GisFeatureCatalogue::VERIFICATION_STATUSES,
            'record_types' => GisFeatureCatalogue::RECORD_TYPES,
            'categories' => GisFeatureCatalogue::CATEGORIES,
            'significance' => GisFeatureCatalogue::SIGNIFICANCE,
            'priorities' => GisFeatureCatalogue::PRIORITIES,
            'statuses' => GisFeatureCatalogue::STATUSES,
        ];
    }
}
