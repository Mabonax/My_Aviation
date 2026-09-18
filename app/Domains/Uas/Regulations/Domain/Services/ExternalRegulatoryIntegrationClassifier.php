<?php

namespace App\Domains\Uas\Regulations\Domain\Services;

class ExternalRegulatoryIntegrationClassifier
{
    public const CLASSIFICATIONS = [
        'manual' => 'Manual',
        'document_based' => 'Document-based',
        'verified_external' => 'Verified External',
        'api' => 'API',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'retired' => 'Retired',
    ];

    public function requiresApiAssumptionBlock(string $classification, ?bool $requested = null): bool
    {
        if ($classification === 'api') {
            return true;
        }

        return $requested ?? true;
    }
}
