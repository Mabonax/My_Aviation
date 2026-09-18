<?php

namespace App\Domains\Uas\Training\Domain\Policies;

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;
use App\Models\User;

class TrainingCoursePolicy
{
    public function viewAny(User $user): bool { return $this->hasPermission($user, 'training.view'); }
    public function view(User $user, UasTrainingCourse $course): bool { return $this->hasPermission($user, 'training.view'); }
    public function create(User $user): bool { return $this->hasPermission($user, 'training.create'); }
    public function update(User $user, UasTrainingCourse $course): bool { return $this->hasPermission($user, 'training.update'); }
    public function delete(User $user, UasTrainingCourse $course): bool { return false; }

    private function hasPermission(User $user, string $permission): bool
    {
        return UasRole::query()
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->get()
            ->flatMap(fn (UasRole $role): array => $role->permissions ?? [])
            ->contains($permission);
    }
}
