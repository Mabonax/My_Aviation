<?php

namespace App\Domains\Uas\Defects\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasAircraftDefect extends Model
{
    protected $fillable = [
        'uas_aircraft_id',
        'uas_mission_id',
        'reported_by',
        'defect_number',
        'source',
        'severity',
        'status',
        'serviceability_impact',
        'title',
        'description',
        'immediate_action',
        'reported_at',
        'evidence_references',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'evidence_references' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'uas_aircraft_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(UasMission::class, 'uas_mission_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}