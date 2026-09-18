<?php

namespace App\Domains\Uas\FlightLogs\Domain\Models;

use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilotLogEntry extends Model
{
    protected $fillable = ['uas_pilot_id', 'uas_mission_id', 'flight_date', 'aircraft_registration', 'operation_type', 'flight_hours', 'launch_location', 'landing_location', 'remarks', 'evidence_references'];

    protected function casts(): array
    {
        return ['flight_date' => 'date', 'flight_hours' => 'decimal:2', 'evidence_references' => 'array'];
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(UasPilot::class, 'uas_pilot_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(UasMission::class, 'uas_mission_id');
    }
}
