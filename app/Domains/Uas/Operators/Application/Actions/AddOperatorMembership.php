<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddOperatorMembership
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasOperator $operator, User $member, string $role, string $status, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasOperatorMembership
    {
        return DB::transaction(function () use ($operator, $member, $role, $status, $actor, $ipAddress, $userAgent): UasOperatorMembership {
            if (! array_key_exists($role, UasOperatorMembership::roles())) {
                throw ValidationException::withMessages(['membership_role' => 'Select a valid operator membership role.']);
            }

            if (! array_key_exists($status, UasOperatorMembership::statuses())) {
                throw ValidationException::withMessages(['status' => 'Select a valid membership status.']);
            }

            if ($status === UasOperatorMembership::STATUS_ACTIVE && UasOperatorMembership::query()
                ->where('uas_operator_id', $operator->id)
                ->where('user_id', $member->id)
                ->where('status', UasOperatorMembership::STATUS_ACTIVE)
                ->exists()) {
                throw ValidationException::withMessages(['user_id' => 'This user already has an active membership for this operator.']);
            }

            $membership = UasOperatorMembership::query()->create([
                'uas_operator_id' => $operator->id,
                'user_id' => $member->id,
                'membership_role' => $role,
                'status' => $status,
                'joined_at' => in_array($status, [UasOperatorMembership::STATUS_ACTIVE, UasOperatorMembership::STATUS_PENDING], true) ? now() : null,
                'activated_at' => $status === UasOperatorMembership::STATUS_ACTIVE ? now() : null,
                'invited_at' => $status === UasOperatorMembership::STATUS_PENDING ? now() : null,
                'left_at' => $status === UasOperatorMembership::STATUS_ENDED ? now() : null,
                'created_by' => $actor->id,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $membership,
                action: 'membership.created',
                requirementId: 'FR-OPS-001',
                regulatorySource: $operator->regulatory_source,
                previousValues: null,
                newValues: [
                    'uas_operator_id' => $operator->id,
                    'user_id' => $member->id,
                    'membership_role' => $role,
                    'status' => $status,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            if ($status === UasOperatorMembership::STATUS_ACTIVE) {
                $this->recordAuditEntry->execute(new AuditEntryData($actor, $membership, 'membership.activated', 'FR-OPS-001', $operator->regulatory_source, null, ['status' => 'active'], $ipAddress, $userAgent));
            }

            return $membership;
        });
    }
}
