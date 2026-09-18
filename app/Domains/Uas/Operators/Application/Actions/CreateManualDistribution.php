<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualDistribution;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateManualDistribution
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OM-002; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Operations Manual revision distribution record for personnel required to receive a controlled manual revision.',
    ];

    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperationsManualRevision $manualRevision, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperationsManualDistribution
    {
        return DB::transaction(function () use ($manualRevision, $data, $actor, $ipAddress, $userAgent): UasOperationsManualDistribution {
            $distribution = UasOperationsManualDistribution::query()->create([
                ...$data,
                'manual_revision_id' => $manualRevision->id,
                'distribution_channel' => $data['distribution_channel'] ?? 'manual_register',
                'distribution_status' => $data['distribution_status'] ?? 'required',
                'evidence_references' => $data['evidence_references'] ?? [],
                ...self::TRACEABILITY,
                'created_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $distribution, 'operator.manual_distribution.created', 'FR-OM-002', self::TRACEABILITY['regulatory_source'], null, $distribution->getAttributes(), $ipAddress, $userAgent));

            return $distribution;
        });
    }
}
