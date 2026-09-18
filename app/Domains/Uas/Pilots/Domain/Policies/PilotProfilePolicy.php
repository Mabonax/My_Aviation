<?php

namespace App\Domains\Uas\Pilots\Domain\Policies;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;

class PilotProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manageAnyPilots($user);
    }

    public function view(User $user, UasPilot $pilot): bool
    {
        return $this->managePilot($user);
    }

    public function create(User $user): bool
    {
        return $user->hasUasPermission(PilotPermissions::CREATE);
    }

    public function update(User $user, UasPilot $pilot): bool
    {
        return $this->managePilot($user);
    }

    public function delete(User $user, UasPilot $pilot): bool
    {
        return false;
    }

    public function createOwn(User $user): bool
    {
        return $user->hasUasPermission(PilotPermissions::SELF_SERVICE);
    }

    public function viewOwn(User $user, UasPilot $pilot): bool
    {
        return $this->isOwnPilot($user, $pilot);
    }

    public function updateOwn(User $user, UasPilot $pilot): bool
    {
        return $this->isOwnPilot($user, $pilot)
            && $user->hasUasPermission(PilotPermissions::SELF_SERVICE);
    }

    public function linkUser(User $user, UasPilot $pilot): bool
    {
        return $user->hasUasPermission(PilotPermissions::UPDATE);
    }

    public function manageAnyPilots(User $user): bool
    {
        return $user->hasUasPermission(PilotPermissions::MANAGE_ANY);
    }

    public function managePilot(User $user): bool
    {
        return $user->hasAnyUasPermission([PilotPermissions::MANAGE_ANY, PilotPermissions::UPDATE]);
    }

    private function isOwnPilot(User $user, UasPilot $pilot): bool
    {
        return $pilot->user_id !== null && $pilot->user_id === $user->id;
    }
}
