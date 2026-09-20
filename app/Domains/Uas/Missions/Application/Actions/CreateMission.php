<?php

namespace App\Domains\Uas\Missions\Application\Actions;

use App\Domains\Uas\Missions\Application\DTOs\MissionData;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Domain\Services\MissionReleaseGate;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Operators\Application\Services\PilotOperatorApproval;
use Illuminate\Validation\ValidationException;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateMission
{
    private const REGULATORY_TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-MIS-001 and FR-MIS-002',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission planning and operational release workflow for VMT UAS operations.',
        'responsible_role' => 'Operations Manager',
    ];

    public function __construct(
        private readonly MissionReleaseGate $releaseGate,
        private readonly RecordAuditEntry $recordAuditEntry,
        private readonly PilotOperatorApproval $pilotApproval,
    ) {}

    public function execute(MissionData $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasMission
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): UasMission {
            $attrs = $data->toModelAttributes();
            if (! empty($attrs['uas_operator_id']) && ! empty($attrs['uas_pilot_id']) && ! $this->pilotApproval->isApproved((int) $attrs['uas_operator_id'], (int) $attrs['uas_pilot_id'])) {
                throw ValidationException::withMessages(['uas_pilot_id' => 'The selected pilot is not currently approved to operate for this operator.']);
            }
            $mission = UasMission::query()->create([
                'mission_number' => $this->nextMissionNumber(),
                ...$data->toModelAttributes(),
                'lifecycle_state' => MissionLifecycleState::Draft,
                ...self::REGULATORY_TRACEABILITY,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $releaseGate = $this->releaseGate->evaluate($mission);
            $mission->forceFill([
                'release_gate_state' => $releaseGate['state'],
                'release_gate_results' => $releaseGate,
            ])->save();

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $mission,
                action: 'mission.created',
                requirementId: 'FR-MIS-001',
                regulatorySource: self::REGULATORY_TRACEABILITY['regulatory_source'],
                previousValues: null,
                newValues: $mission->getAttributes(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $mission;
        });
    }

    private function nextMissionNumber(): string
    {
        $next = (int) UasMission::query()->max('id') + 1;

        return 'MIS-'.now()->format('Ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
