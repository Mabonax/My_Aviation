<?php

namespace App\Domains\Uas\Regulations\Application\Actions;

use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateRegulatoryExternalIntegrationStatus
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(RegulatoryExternalIntegration $integration, string $status, User $actor, ?string $ipAddress = null, ?string $userAgent = null): RegulatoryExternalIntegration
    {
        return DB::transaction(function () use ($integration, $status, $actor, $ipAddress, $userAgent): RegulatoryExternalIntegration {
            $previous = $integration->getAttributes();

            $integration->forceFill(['status' => $status])->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $integration, 'regulatory_external_integration.status_updated', 'FR-EXT-001', 'UAS Compliance & Operations Platform FRS section 31', $previous, $integration->getAttributes(), $ipAddress, $userAgent));

            return $integration;
        });
    }
}
