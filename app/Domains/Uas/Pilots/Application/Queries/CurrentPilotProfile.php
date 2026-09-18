<?php

namespace App\Domains\Uas\Pilots\Application\Queries;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;

class CurrentPilotProfile
{
    public function resolve(User $user): ?UasPilot
    {
        return UasPilot::query()
            ->where('user_id', $user->id)
            ->first();
    }
}
