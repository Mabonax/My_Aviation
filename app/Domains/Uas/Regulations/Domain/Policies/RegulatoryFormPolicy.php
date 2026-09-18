<?php

namespace App\Domains\Uas\Regulations\Domain\Policies;

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use App\Models\User;

class RegulatoryFormPolicy
{
    public function viewAny(User $user): bool { return $this->hasPermission($user, 'regulations.view'); }
    public function view(User $user, RegulatoryForm $form): bool { return $this->hasPermission($user, 'regulations.view'); }
    public function create(User $user): bool { return $this->hasPermission($user, 'regulations.create'); }
    public function update(User $user, RegulatoryForm $form): bool { return $this->hasPermission($user, 'regulations.update'); }
    public function delete(User $user, RegulatoryForm $form): bool { return false; }

    private function hasPermission(User $user, string $permission): bool
    {
        return UasRole::query()
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->get()
            ->flatMap(fn (UasRole $role): array => $role->permissions ?? [])
            ->contains($permission);
    }
}
