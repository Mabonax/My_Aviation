<?php

namespace App\Domains\Uas\Crew\Domain\Models;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasMissionCrewMember extends Model
{
    protected $fillable = [
        'uas_mission_id',
        'uas_pilot_id',
        'user_id',
        'assigned_by',
        'crew_role',
        'display_name',
        'email',
        'phone',
        'briefing_status',
        'competency_status',
        'acceptance_status',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'regulatory_effective_date' => 'date',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(UasMission::class, 'uas_mission_id');
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(UasPilot::class, 'uas_pilot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}