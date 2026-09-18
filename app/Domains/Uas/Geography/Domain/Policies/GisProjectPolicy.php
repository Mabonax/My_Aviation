<?php

namespace App\Domains\Uas\Geography\Domain\Policies;

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Models\User;

class GisProjectPolicy
{
    public function viewAny(User $user): bool { return $this->hasPermission($user, 'gis.view'); }
    public function view(User $user, UasGisProject $project): bool { return $this->hasPermission($user, 'gis.view'); }
    public function create(User $user): bool { return $this->hasPermission($user, 'gis.create'); }
    public function update(User $user, UasGisProject $project): bool { return $this->hasPermission($user, 'gis.update') && ! in_array($project->lifecycle_state, ['closed', 'cancelled'], true); }
    public function delete(User $user, UasGisProject $project): bool { return false; }

    private function hasPermission(User $user, string $permission): bool
    {
        return UasRole::query()
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->get()
            ->flatMap(fn (UasRole $role): array => $role->permissions ?? [])
            ->contains($permission);
    }
}
