<?php

namespace App\Domains\Uas\Missions\Domain\Policies;

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;

class MissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'missions.view');
    }

    public function view(User $user, UasMission $mission): bool
    {
        return $this->hasPermission($user, 'missions.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'missions.create');
    }

    public function update(User $user, UasMission $mission): bool
    {
        return $this->hasPermission($user, 'missions.update')
            && ! in_array($mission->lifecycle_state->value, ['closed', 'cancelled'], true);
    }

    public function delete(User $user, UasMission $mission): bool
    {
        return false;
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return UasRole::query()
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->get()
            ->flatMap(fn (UasRole $role): array => $role->permissions ?? [])
            ->contains($permission);
    }
}
