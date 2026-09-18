<?php

namespace App\Domains\Uas\Pilots\Application\Actions;

use App\Domains\Uas\Pilots\Application\Queries\CurrentPilotProfile;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LinkPilotProfileToUser
{
    public function __construct(
        private readonly CurrentPilotProfile $currentPilot,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(UasPilot $pilot, User $targetUser, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasPilot
    {
        return DB::transaction(function () use ($pilot, $targetUser, $actor, $ipAddress, $userAgent): UasPilot {
            if ($pilot->user_id !== null && $pilot->user_id !== $targetUser->id) {
                throw ValidationException::withMessages([
                    'user_id' => 'This pilot profile is already linked to another user.',
                ]);
            }

            $existingPilot = $this->currentPilot->resolve($targetUser);
            if ($existingPilot !== null && $existingPilot->id !== $pilot->id) {
                throw ValidationException::withMessages([
                    'user_id' => 'This user already has a linked pilot profile.',
                ]);
            }

            $previousValues = ['user_id' => $pilot->user_id];

            $pilot->forceFill([
                'user_id' => $targetUser->id,
                'updated_by' => $actor->id,
            ])->save();

            $pilot = $pilot->refresh();

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $pilot,
                action: 'pilot.user.linked',
                requirementId: 'FR-PIL-001',
                regulatorySource: $pilot->regulatory_source,
                previousValues: $previousValues,
                newValues: ['user_id' => $targetUser->id, 'link_source' => 'admin_managed_link'],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $pilot;
        });
    }
}
