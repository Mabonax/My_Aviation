<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;

class ListRegulatoryExternalIntegrations
{
    public function execute(): array
    {
        return RegulatoryExternalIntegration::query()
            ->orderBy('authority')
            ->orderBy('regulatory_area')
            ->orderBy('supported_process')
            ->get()
            ->map(fn (RegulatoryExternalIntegration $integration): array => RegulatoryExternalIntegrationPresenter::summary($integration))
            ->all();
    }
}
