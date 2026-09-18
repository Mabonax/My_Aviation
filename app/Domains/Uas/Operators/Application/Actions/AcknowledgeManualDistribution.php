<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualDistribution;
use App\Domains\Uas\Operators\Domain\Services\ManualAcknowledgementControl;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcknowledgeManualDistribution
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperationsManualDistribution $distribution, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperationsManualDistribution
    {
        return DB::transaction(function () use ($distribution, $data, $actor, $ipAddress, $userAgent): UasOperationsManualDistribution {
            $previous = $distribution->getAttributes();

            $distribution->forceFill([
                'acknowledgement_status' => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledged_by' => $actor->id,
                'acknowledgement_statement' => ManualAcknowledgementControl::STATEMENT,
                'acknowledgement_notes' => $data['acknowledgement_notes'] ?? null,
            ])->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $distribution, 'operator.manual_acknowledgement.recorded', 'FR-OM-003', 'UAS Compliance & Operations Platform FRS FR-OM-003; Operator & Operations Manual domain', $previous, $distribution->getAttributes(), $ipAddress, $userAgent));

            return $distribution->refresh();
        });
    }
}
