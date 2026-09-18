<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;
use App\Domains\Uas\Regulations\Domain\Services\ExternalRegulatoryIntegrationClassifier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRegulatoryExternalIntegration
{
    public function __construct(
        private readonly ExternalRegulatoryIntegrationClassifier $classifier,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryExternalIntegration
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): RegulatoryExternalIntegration {
            $integration = RegulatoryExternalIntegration::query()->create([
                'name' => $data['name'],
                'authority' => $data['authority'],
                'classification' => $data['classification'],
                'regulatory_area' => $data['regulatory_area'],
                'supported_process' => $data['supported_process'],
                'authoritative_url' => $data['authoritative_url'] ?? null,
                'evidence_required' => $data['evidence_required'],
                'workflow_notes' => $data['workflow_notes'],
                'api_assumption_blocked' => $this->classifier->requiresApiAssumptionBlock($data['classification'], $data['api_assumption_blocked'] ?? null),
                'status' => $data['status'] ?? 'active',
                'verified_at' => $data['verified_at'] ?? null,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $integration, 'regulatory_external_integration.created', 'FR-EXT-001', 'UAS Compliance & Operations Platform FRS section 31', null, $integration->getAttributes(), $ipAddress, $userAgent));

            return $integration;
        });
    }
}
