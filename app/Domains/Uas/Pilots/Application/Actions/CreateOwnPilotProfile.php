<?php

namespace App\Domains\Uas\Pilots\Application\Actions;

use App\Domains\Uas\Pilots\Application\DTOs\PilotProfileData;
use App\Domains\Uas\Pilots\Application\Queries\CurrentPilotProfile;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateOwnPilotProfile
{
    public function __construct(
        private readonly CurrentPilotProfile $currentPilot,
        private readonly CreatePilotProfile $createPilotProfile,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(array $input, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasPilot
    {
        if ($this->currentPilot->resolve($actor) !== null) {
            throw ValidationException::withMessages([
                'pilot_profile' => 'You already have a linked pilot profile.',
            ]);
        }

        $pilot = $this->createPilotProfile->execute(
            PilotProfileData::fromArray([
                ...$input,
                'user_id' => $actor->id,
                'employee_number' => null,
                'profile_status' => 'draft',
                'medical_status' => 'unverified',
                'radiotelephony_qualification' => 'unverified',
            ]),
            $actor,
            $ipAddress,
            $userAgent,
        );

        $this->recordAuditEntry->execute(new AuditEntryData(
            actor: $actor,
            auditable: $pilot,
            action: 'pilot.user.linked',
            requirementId: 'FR-PIL-001',
            regulatorySource: $pilot->regulatory_source,
            previousValues: null,
            newValues: ['user_id' => $actor->id, 'link_source' => 'self_service_creation'],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        ));

        return $pilot;
    }
}
