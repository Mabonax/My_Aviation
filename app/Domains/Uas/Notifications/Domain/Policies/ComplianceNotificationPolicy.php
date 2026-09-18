<?php

namespace App\Domains\Uas\Notifications\Domain\Policies;

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Models\User;

class ComplianceNotificationPolicy
{
    public function viewAny(User $user): bool { return $this->hasPermission($user, 'notifications.view'); }
    public function view(User $user, ComplianceNotification $notification): bool { return $this->hasPermission($user, 'notifications.view') || $notification->user_id === $user->id; }
    public function create(User $user): bool { return $this->hasPermission($user, 'notifications.plan'); }
    public function update(User $user, ComplianceNotification $notification): bool { return $this->hasPermission($user, 'notifications.update') || $notification->user_id === $user->id; }
    public function delete(User $user, ComplianceNotification $notification): bool { return false; }

    private function hasPermission(User $user, string $permission): bool
    {
        return UasRole::query()
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->get()
            ->flatMap(fn (UasRole $role): array => $role->permissions ?? [])
            ->contains($permission);
    }
}
