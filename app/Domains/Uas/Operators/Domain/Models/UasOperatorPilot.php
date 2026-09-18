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
        'assignment_role',
        'status',
        'approved_from',
        'approved_until',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_from' => 'date',
            'approved_until' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
