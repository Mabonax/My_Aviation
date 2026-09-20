<?php

namespace App\Domains\Uas\Geography\Domain\Policies;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;

class GisProjectPolicy
{
    public function __construct(private readonly CurrentOperatorContext $operatorContext) {}

    public function viewAny(User $user): bool
    {
        return $this->operatorContext->hasGlobalOperatorAccess($user) || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, UasGisProject $project): bool
    {
        if ($this->operatorContext->hasGlobalOperatorAccess($user)) {
            return true;
        }

        $accessible = $this->operatorContext->accessibleOperatorIds($user);

        return ($project->uas_operator_id && in_array((int) $project->uas_operator_id, $accessible, true))
            || (! $project->uas_operator_id && $project->projectMissions()->whereHas('mission', fn ($mission) =>
                $mission->whereIn('uas_operator_id', $accessible)
            )->exists());
    }

    public function create(User $user): bool
    {
        return $this->operatorContext->hasGlobalOperatorAccess($user)
            || $this->managedOperatorIds($user) !== [];
    }

    public function update(User $user, UasGisProject $project): bool
    {
        if (in_array($project->lifecycle_state, ['closed', 'cancelled'], true)) {
            return false;
        }
        if ($this->operatorContext->hasGlobalOperatorAccess($user)) {
            return true;
        }
        $managed = $this->managedOperatorIds($user);

        return ($project->uas_operator_id && in_array((int) $project->uas_operator_id, $managed, true))
            || (! $project->uas_operator_id && $project->projectMissions()->whereHas('mission', fn ($mission) => $mission->whereIn('uas_operator_id', $managed))->exists());
    }

    public function delete(User $user, UasGisProject $project): bool { return false; }

    private function managedOperatorIds(User $user): array
    {
        return $user->activeOperatorMemberships()
            ->whereIn('membership_role', UasOperatorMembership::managerRoles())
            ->pluck('uas_operator_id')->all();
    }
}
