<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;

class RegulatoryExternalIntegrationPresenter
{
    public static function toArray(RegulatoryExternalIntegration $integration): array
    {
        return [
            ...self::summary($integration),
            'authoritative_url' => $integration->authoritative_url,
            'evidence_required' => $integration->evidence_required,
            'workflow_notes' => $integration->workflow_notes,
            'verified_at' => $integration->verified_at?->toISOString(),
            'created_at' => $integration->created_at?->toISOString(),
            'updated_at' => $integration->updated_at?->toISOString(),
        ];
    }

    public static function summary(RegulatoryExternalIntegration $integration): array
    {
        return [
            'id' => $integration->id,
            'name' => $integration->name,
            'authority' => $integration->authority,
            'classification' => $integration->classification,
            'regulatory_area' => $integration->regulatory_area,
            'supported_process' => $integration->supported_process,
            'api_assumption_blocked' => $integration->api_assumption_blocked,
            'status' => $integration->status,
        ];
    }
}
