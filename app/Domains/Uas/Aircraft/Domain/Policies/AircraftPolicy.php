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
        return $user->hasAnyUasPermission(['operators.view', 'missions.view'])
            || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, UasAircraft $aircraft): bool
    {
        if ($user->hasAnyUasPermission(['operators.view', 'missions.view'])) {
            return true;
        }

        $operatorIds = $this->operatorContext->accessibleOperatorIds($user);

        return $aircraft->operators()
            ->whereIn('uas_operators.id', $operatorIds)
            ->where('uas_operator_aircraft.status', 'active')
            ->exists();
    }
}
