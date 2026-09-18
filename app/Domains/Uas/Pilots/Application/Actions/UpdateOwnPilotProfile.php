<?php

namespace App\Domains\Uas\Pilots\Application\Actions;

use App\Domains\Uas\Pilots\Application\DTOs\PilotProfileData;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;

class UpdateOwnPilotProfile
{
    public function __construct(private readonly UpdatePilotProfile $updatePilotProfile) {}

    public function execute(UasPilot $pilot, array $input, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasPilot
    {
        return $this->updatePilotProfile->execute(
            $pilot,
            PilotProfileData::fromArray([
                ...$input,
                'user_id' => $actor->id,
                'employee_number' => $pilot->employee_number,
                'profile_status' => $pilot->profile_status->value,
            ]),
            $actor,
            $ipAddress,
            $userAgent,
        );
    }
}
