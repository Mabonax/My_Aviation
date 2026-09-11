<?php

namespace App\Domains\Uas\Pilots\Domain\Policies;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;

class PilotProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UasPilot $pilot): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UasPilot $pilot): bool
    {
        return true;
    }

    public function delete(User $user, UasPilot $pilot): bool
    {
        return false;
    }
}
