<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Policies;

use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Models\User;

class AeronauticalInformationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyUasPermission(['aeronautical-information.view', 'missions.view']) || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, AeronauticalInformationItem $item): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user): bool
    {
        return $user->hasUasPermission('aeronautical-information.manage');
    }

    public function sync(User $user): bool
    {
        return $user->hasUasPermission('aeronautical-information.sync');
    }
}
