<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Services\ExternalRegulatoryIntegrationClassifier;

class ExternalIntegrationOptions
{
    public function execute(): array
    {
        return [
            'classifications' => ExternalRegulatoryIntegrationClassifier::CLASSIFICATIONS,
            'statuses' => ExternalRegulatoryIntegrationClassifier::STATUSES,
        ];
    }
}
