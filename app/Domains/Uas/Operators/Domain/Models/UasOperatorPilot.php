<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasOperatorPilot extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ENDED = 'ended';

    protected $table = 'uas_operator_pilots';

    protected $fillable = [
        'uas_operator_id',
        'uas_pilot_id',
        'uas_operator_membership_id',
        'assignment_role',
        'status',
        'approved_from',
        'approved_until',
        'approved_at',
        'approved_by',
        'suspended_at',
        'ended_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_from' => 'date',
            'approved_until' => 'date',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UasOperator::class, 'uas_operator_id');
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(UasPilot::class, 'uas_pilot_id');
    }

    public function membership(): BelongsTo { return $this->belongsTo(UasOperatorMembership::class, 'uas_operator_membership_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
