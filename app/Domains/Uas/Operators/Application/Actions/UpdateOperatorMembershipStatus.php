<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOperatorMembershipStatus
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperatorMembership $membership, string $status, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperatorMembership
    {
        return DB::transaction(function () use ($membership, $status, $actor, $ipAddress, $userAgent): UasOperatorMembership {
            if (! array_key_exists($status, UasOperatorMembership::statuses())) {
                throw ValidationException::withMessages(['status' => 'Select a valid membership status.']);
            }

            if ($membership->status === UasOperatorMembership::STATUS_ENDED && $status === UasOperatorMembership::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['status' => 'Ended memberships cannot be reactivated in this workflow.']);
            }

            $previous = $membership->getAttributes();
            $updates = ['status' => $status];

            if ($status === UasOperatorMembership::STATUS_ACTIVE) {
                $updates['activated_at'] = now();
                $updates['left_at'] = null;
            }

            if ($status === UasOperatorMembership::STATUS_ENDED) {
                $updates['left_at'] = now();
            }

            $membership->forceFill($updates)->save();
            $membership = $membership->refresh();

            $action = match ($status) {
                UasOperatorMembership::STATUS_ACTIVE => 'membership.activated',
                UasOperatorMembership::STATUS_SUSPENDED => 'membership.suspended',
                UasOperatorMembership::STATUS_ENDED => 'membership.ended',
                default => 'membership.updated',
            };

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $membership,
                action: $action,
                requirementId: 'FR-OPS-001',
                regulatorySource: $membership->operator->regulatory_source,
                previousValues: $previous,
                newValues: $membership->getAttributes(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $membership;
        });
    }
}
