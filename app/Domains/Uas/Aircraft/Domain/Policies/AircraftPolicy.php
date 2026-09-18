<?php

namespace App\Domains\Uas\Aircraft\Domain\Policies;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Models\User;

class AircraftPolicy
{
    public function __construct(private readonly CurrentOperatorContext $operatorContext) {}

    public function viewAny(User $user): bool
    {
        return $this->operatorContext->hasGlobalOperatorAccess($user)
            || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, UasAircraft $aircraft): bool
    {
        if ($this->operatorContext->hasGlobalOperatorAccess($user)) {
            return true;
        }

        return $aircraft->operators()
            ->whereIn('uas_operators.id', $this->operatorContext->accessibleOperatorIds($user))
            ->where('uas_operator_aircraft.status', 'active')
            ->exists();
    }

    public function update(User $user, UasAircraft $aircraft): bool
    {
        if ($this->operatorContext->hasGlobalOperatorAccess($user)) {
            return true;
        }

        return $aircraft->operators()
            ->whereIn('uas_operators.id', $this->managedOperatorIds($user))
            ->where('uas_operator_aircraft.status', 'active')
            ->exists();
    }

    private function managedOperatorIds(User $user): array
    {
        return $user->activeOperatorMemberships()
            ->whereIn('membership_role', \App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership::managerRoles())
            ->pluck('uas_operator_id')->all();
    }
}
