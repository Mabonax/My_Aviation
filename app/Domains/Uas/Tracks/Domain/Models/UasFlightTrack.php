<?php

namespace App\Domains\Uas\Tracks\Domain\Models;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasFlightTrack extends Model
{
    protected $fillable = [
        'uas_mission_id',
        'captured_by',
        'source_type',
        'track_reference',
        'started_at',
        'ended_at',
        'points',
        'point_count',
        'total_distance_km',
        'max_altitude_ft',
        'anomalies',
        'notes',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'points' => 'array',
            'total_distance_km' => 'decimal:3',
            'anomalies' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(UasMission::class, 'uas_mission_id');
    }

    public function capturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}