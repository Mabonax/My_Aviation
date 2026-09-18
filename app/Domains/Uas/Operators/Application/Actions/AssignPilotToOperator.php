<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;

class AssignPilotToOperator
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperator $operator, UasPilot $pilot, User $actor, string $role = 'remote_pilot', string $status = 'active', ?string $notes = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $operator->pilots()->syncWithoutDetaching([
            $pilot->id => [
                'assignment_role' => $role,
                'status' => $status,
                'approved_from' => now()->toDateString(),
                'notes' => $notes,
                'created_by' => $actor->id,
            ],
        ]);

        $this->recordAuditEntry->execute(new AuditEntryData($actor, $operator, 'pilot.assigned_to_operator', 'FR-OPS-001', $operator->regulatory_source, null, [
            'uas_operator_id' => $operator->id,
            'uas_pilot_id' => $pilot->id,
            'assignment_role' => $role,
            'status' => $status,
        ], $ipAddress, $userAgent));
    }
}
