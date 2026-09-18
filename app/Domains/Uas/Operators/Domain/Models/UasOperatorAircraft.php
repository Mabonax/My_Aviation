<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasOperatorAircraft extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ENDED = 'ended';

    protected $table = 'uas_operator_aircraft';

    protected $fillable = [
        'uas_operator_id',
        'uas_aircraft_id',
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

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'uas_aircraft_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
