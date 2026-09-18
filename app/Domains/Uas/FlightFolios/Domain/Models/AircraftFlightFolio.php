<?php

namespace App\Domains\Uas\FlightFolios\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AircraftFlightFolio extends Model
{
    protected $fillable = ['uas_aircraft_id', 'uas_pilot_id', 'uas_mission_id', 'flight_date', 'folio_reference', 'flight_hours', 'battery_cycles', 'charging_fuel_oil_records', 'maintenance_certification_entries', 'defects_reported', 'available_offline', 'evidence_references'];

    protected function casts(): array
    {
        return [
            'flight_date' => 'date',
            'flight_hours' => 'decimal:2',
            'charging_fuel_oil_records' => 'array',
            'maintenance_certification_entries' => 'array',
            'available_offline' => 'boolean',
            'evidence_references' => 'array',
        ];
    }

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'uas_aircraft_id');
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
