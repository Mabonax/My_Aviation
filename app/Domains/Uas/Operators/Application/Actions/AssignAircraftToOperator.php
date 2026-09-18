<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;

class AssignAircraftToOperator
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperator $operator, UasAircraft $aircraft, User $actor, string $role = 'operated_aircraft', string $status = 'active', ?string $notes = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $operator->aircraft()->syncWithoutDetaching([
            $aircraft->id => [
                'assignment_role' => $role,
                'status' => $status,
                'approved_from' => now()->toDateString(),
                'notes' => $notes,
                'created_by' => $actor->id,
            ],
        ]);

        $this->recordAuditEntry->execute(new AuditEntryData($actor, $operator, 'aircraft.assigned_to_operator', 'FR-OPS-001', $operator->regulatory_source, null, [
            'uas_operator_id' => $operator->id,
            'uas_aircraft_id' => $aircraft->id,
            'assignment_role' => $role,
            'status' => $status,
        ], $ipAddress, $userAgent));
    }
}
