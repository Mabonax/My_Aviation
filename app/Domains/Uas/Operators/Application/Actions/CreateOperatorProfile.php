<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOperatorProfile
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-OPS-001; Operator & Operations Manual domain',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'UAS operator profile, certificate holder identity, post holders, operating bases, approved aircraft, approved pilots and OpsSpecs.',
        'responsible_role' => 'Accountable Manager',
    ];

    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperator
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): UasOperator {
            $operator = UasOperator::query()->create([
                ...$data,
                'status' => $data['status'] ?? 'draft',
                'operating_bases' => $data['operating_bases'] ?? [],
                'approved_aircraft' => $data['approved_aircraft'] ?? [],
                'approved_pilots' => $data['approved_pilots'] ?? [],
                'operations_specifications' => $data['operations_specifications'] ?? [],
                'evidence_references' => $data['evidence_references'] ?? [],
                ...self::TRACEABILITY,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $operator, 'operator.profile.created', 'FR-OPS-001', self::TRACEABILITY['regulatory_source'], null, $operator->getAttributes(), $ipAddress, $userAgent));

            return $operator;
        });
    }
}