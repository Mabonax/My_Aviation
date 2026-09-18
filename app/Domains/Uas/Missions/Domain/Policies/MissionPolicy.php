<?php

namespace App\Domains\Uas\Missions\Domain\Policies;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Models\User;

class MissionPolicy
{
    public function __construct(private readonly CurrentOperatorContext $operatorContext) {}

    public function viewAny(User $user): bool
    {
        return $this->operatorContext->hasGlobalOperatorAccess($user) || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, UasMission $mission): bool
    {
        return $mission->uas_operator_id !== null && $this->operatorContext->canAccessOperator($user, $mission->uas_operator_id);
    }

    public function create(User $user): bool
    {
        return $this->operatorContext->hasGlobalOperatorAccess($user) || $user->activeOperatorMemberships()->exists();
    }

    public function update(User $user, UasMission $mission): bool
    {
        return $mission->uas_operator_id !== null
            && $this->operatorContext->canManageOperator($user, $mission->uas_operator_id)
            && ! in_array($mission->lifecycle_state->value, ['closed', 'cancelled'], true);
    }

    public function generateBriefing(User $user, UasMission $mission): bool
    {
        return $this->view($user, $mission)
            && ! in_array($mission->lifecycle_state->value, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed', 'cancelled'], true)
            && ($this->update($user, $mission) || $mission->pilot?->user_id === $user->id);
    }

    public function acknowledgeBriefing(User $user, UasMission $mission): bool
    {
        return $this->view($user, $mission)
            && ! in_array($mission->lifecycle_state->value, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed', 'cancelled'], true)
            && ($this->update($user, $mission) || $mission->pilot?->user_id === $user->id);
    }

    public function delete(User $user, UasMission $mission): bool { return false; }
}
