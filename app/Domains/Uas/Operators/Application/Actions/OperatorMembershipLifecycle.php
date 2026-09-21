<?php

namespace App\Domains\Uas\Operators\Application\Actions;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperatorMembershipLifecycle
{
    public function __construct(private readonly RecordAuditEntry $audit) {}

    public function invite(UasOperator $operator, User $member, string $role, User $actor, ?string $message = null): UasOperatorMembership
    {
        return $this->createPending($operator, $member, $role, UasOperatorMembership::SOURCE_INVITATION, $actor, $message, 'membership.invited');
    }

    public function request(UasOperator $operator, User $member, string $role, ?string $message = null): UasOperatorMembership
    {
        if (! in_array($role, UasOperatorMembership::selfRequestableRoles(), true)) {
            throw ValidationException::withMessages([
                'membership_role' => 'This operator role can only be assigned by an authorised operator manager.',
            ]);
        }

        return $this->createPending($operator, $member, $role, UasOperatorMembership::SOURCE_JOIN_REQUEST, $member, $message, 'membership.requested');
    }

    public function acceptInvitation(UasOperatorMembership $membership, User $actor): UasOperatorMembership
    {
        $this->assertActor($membership, $actor);
        $this->assertPendingSource($membership, UasOperatorMembership::SOURCE_INVITATION);
        return $this->transition($membership, UasOperatorMembership::STATUS_ACTIVE, $actor, 'membership.invitation_accepted');
    }

    public function declineInvitation(UasOperatorMembership $membership, User $actor): UasOperatorMembership
    {
        $this->assertActor($membership, $actor);
        $this->assertPendingSource($membership, UasOperatorMembership::SOURCE_INVITATION);
        return $this->transition($membership, UasOperatorMembership::STATUS_ENDED, $actor, 'membership.invitation_declined');
    }

    public function approveRequest(UasOperatorMembership $membership, User $actor): UasOperatorMembership
    {
        $this->assertPendingSource($membership, UasOperatorMembership::SOURCE_JOIN_REQUEST);
        return $this->transition($membership, UasOperatorMembership::STATUS_ACTIVE, $actor, 'membership.request_approved');
    }

    public function rejectRequest(UasOperatorMembership $membership, User $actor): UasOperatorMembership
    {
        $this->assertPendingSource($membership, UasOperatorMembership::SOURCE_JOIN_REQUEST);
        return $this->transition($membership, UasOperatorMembership::STATUS_ENDED, $actor, 'membership.request_rejected');
    }

    public function suspend(UasOperatorMembership $membership, User $actor): UasOperatorMembership
    {
        if ($membership->status !== UasOperatorMembership::STATUS_ACTIVE) $this->invalid();
        return $this->transition($membership, UasOperatorMembership::STATUS_SUSPENDED, $actor, 'membership.suspended');
    }

    public function reinstate(UasOperatorMembership $membership, User $actor): UasOperatorMembership
    {
        if ($membership->status !== UasOperatorMembership::STATUS_SUSPENDED) $this->invalid();
        return $this->transition($membership, UasOperatorMembership::STATUS_ACTIVE, $actor, 'membership.reinstated');
    }

    public function end(UasOperatorMembership $membership, User $actor): UasOperatorMembership
    {
        if (! in_array($membership->status, [UasOperatorMembership::STATUS_ACTIVE, UasOperatorMembership::STATUS_SUSPENDED], true)) $this->invalid();
        return $this->transition($membership, UasOperatorMembership::STATUS_ENDED, $actor, 'membership.ended');
    }

    private function createPending(UasOperator $operator, User $member, string $role, string $source, User $actor, ?string $message, string $event): UasOperatorMembership
    {
        if (! array_key_exists($role, UasOperatorMembership::roles())) {
            throw ValidationException::withMessages(['membership_role' => 'Select a valid operator membership role.']);
        }
        $exists = UasOperatorMembership::query()->where('uas_operator_id',$operator->id)->where('user_id',$member->id)->whereIn('status',[UasOperatorMembership::STATUS_PENDING,UasOperatorMembership::STATUS_ACTIVE,UasOperatorMembership::STATUS_SUSPENDED])->exists();
        if ($exists) throw ValidationException::withMessages(['membership' => 'An open or active membership already exists for this operator.']);

        return DB::transaction(function () use ($operator,$member,$role,$source,$actor,$message,$event) {
            try {
                $membership=UasOperatorMembership::query()->create([
                'uas_operator_id'=>$operator->id,'user_id'=>$member->id,'membership_role'=>$role,
                'status'=>UasOperatorMembership::STATUS_PENDING,'open_membership_key'=>$operator->id.':'.$member->id,'source'=>$source,'message'=>$message,
                'invited_at'=>$source===UasOperatorMembership::SOURCE_INVITATION ? now() : null,'created_by'=>$actor->id,
                ]);
            } catch (QueryException $exception) {
                if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                    throw ValidationException::withMessages(['membership' => 'An open or active membership already exists for this operator.']);
                }
                throw $exception;
            }
            $this->record($membership,$actor,$event,null,$membership->getAttributes());
            return $membership;
        });
    }

    private function transition(UasOperatorMembership $membership, string $status, User $actor, string $event): UasOperatorMembership
    {
        return DB::transaction(function () use ($membership,$status,$actor,$event) {
            $membership=UasOperatorMembership::query()->lockForUpdate()->findOrFail($membership->id);
            $previous=$membership->getAttributes();
            $updates=['status'=>$status,'responded_at'=>now(),'responded_by'=>$actor->id];
            $updates['open_membership_key'] = in_array($status, [UasOperatorMembership::STATUS_PENDING, UasOperatorMembership::STATUS_ACTIVE, UasOperatorMembership::STATUS_SUSPENDED], true)
                ? $membership->uas_operator_id.':'.$membership->user_id
                : null;
            if ($status===UasOperatorMembership::STATUS_ACTIVE) {
                $updates['joined_at']=$membership->joined_at ?? now(); $updates['activated_at']=now(); $updates['left_at']=null;
            }
            if ($status===UasOperatorMembership::STATUS_ENDED) $updates['left_at']=now();
            $membership->forceFill($updates)->save();
            $this->record($membership,$actor,$event,$previous,$membership->getAttributes());
            return $membership->refresh();
        });
    }

    private function assertActor(UasOperatorMembership $membership, User $actor): void
    {
        if ((int)$membership->user_id !== (int)$actor->id) abort(403);
    }
    private function assertPendingSource(UasOperatorMembership $membership, string $source): void
    {
        if ($membership->status!==UasOperatorMembership::STATUS_PENDING || $membership->source!==$source) $this->invalid();
    }
    private function invalid(): never
    {
        throw ValidationException::withMessages(['membership'=>'This membership transition is not allowed.']);
    }
    private function record(UasOperatorMembership $membership, User $actor, string $event, ?array $previous, array $new): void
    {
        $this->audit->execute(new AuditEntryData($actor,$membership,$event,'TR-005',$membership->operator->regulatory_source,$previous,$new));
    }
}
