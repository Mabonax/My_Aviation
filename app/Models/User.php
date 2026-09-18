<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function pilotProfile()
    {
        return $this->hasOne(UasPilot::class);
    }

    public function uasRoles()
    {
        return $this->belongsToMany(UasRole::class, 'uas_role_user')->withTimestamps();
    }

    public function operatorMemberships()
    {
        return $this->hasMany(UasOperatorMembership::class);
    }

    public function activeOperatorMemberships()
    {
        return $this->operatorMemberships()->where('status', UasOperatorMembership::STATUS_ACTIVE);
    }

    public function operators()
    {
        return $this->belongsToMany(UasOperator::class, 'uas_operator_memberships', 'user_id', 'uas_operator_id')
            ->withPivot(['membership_role', 'status', 'joined_at', 'left_at', 'invited_at', 'activated_at'])
            ->withTimestamps();
    }

    public function hasUasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->uasRoles()
            ->get()
            ->flatMap(fn (UasRole $role): array => $role->permissions ?? [])
            ->contains($permission);
    }

    public function hasAnyUasPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasUasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'workos_id',
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
