<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasOperatorMembership extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ENDED = 'ended';

    public const ROLE_ACCOUNTABLE_MANAGER = 'accountable_manager';
    public const ROLE_OPERATIONS_MANAGER = 'operations_manager';
    public const ROLE_REMOTE_PILOT = 'remote_pilot';
    public const ROLE_SAFETY_OFFICER = 'safety_officer';
    public const ROLE_COMPLIANCE_OFFICER = 'compliance_officer';
    public const ROLE_MAINTENANCE_OFFICER = 'maintenance_officer';
    public const ROLE_ADMINISTRATOR = 'administrator';

    protected $fillable = [
        'uas_operator_id',
        'user_id',
        'membership_role',
        'status',
        'joined_at',
        'left_at',
        'invited_at',
        'activated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UasOperator::class, 'uas_operator_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function roles(): array
    {
        return [
            self::ROLE_ACCOUNTABLE_MANAGER => 'Accountable Manager',
            self::ROLE_OPERATIONS_MANAGER => 'Operations Manager',
            self::ROLE_REMOTE_PILOT => 'Remote Pilot',
            self::ROLE_SAFETY_OFFICER => 'Safety Officer',
            self::ROLE_COMPLIANCE_OFFICER => 'Compliance Officer',
            self::ROLE_MAINTENANCE_OFFICER => 'Maintenance Officer',
            self::ROLE_ADMINISTRATOR => 'Administrator',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_ENDED => 'Ended',
        ];
    }

    public static function managerRoles(): array
    {
        return [
            self::ROLE_ACCOUNTABLE_MANAGER,
            self::ROLE_OPERATIONS_MANAGER,
            self::ROLE_COMPLIANCE_OFFICER,
            self::ROLE_ADMINISTRATOR,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canManageOperator(): bool
    {
        return $this->isActive() && in_array($this->membership_role, self::managerRoles(), true);
    }
}
