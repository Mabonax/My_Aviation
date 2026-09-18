<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOperatorProfile
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperator $operator, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperator
    {
        return DB::transaction(function () use ($operator, $data, $actor, $ipAddress, $userAgent): UasOperator {
            $previous = $operator->getAttributes();

            $operator->fill([
                ...$data,
                'operating_bases' => $data['operating_bases'] ?? [],
                'approved_aircraft' => $data['approved_aircraft'] ?? [],
                'approved_pilots' => $data['approved_pilots'] ?? [],
                'operations_specifications' => $data['operations_specifications'] ?? [],
                'evidence_references' => $data['evidence_references'] ?? [],
                'updated_by' => $actor->id,
            ])->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $operator, 'operator.profile.updated', 'FR-OPS-001', $operator->regulatory_source, $previous, $operator->getAttributes(), $ipAddress, $userAgent));

            return $operator->refresh();
        });
    }
}