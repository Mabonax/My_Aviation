<?php

namespace App\Domains\Uas\Aircraft\Domain\Models;

use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasAircraft extends Model
{
    protected $table = 'uas_aircraft';

    protected $fillable = ['registration', 'manufacturer', 'model', 'serial_number', 'aircraft_category', 'owner', 'operator', 'acquisition_date', 'operational_status', 'base_location', 'manual_references', 'evidence_references'];

    protected function casts(): array
    {
        return ['acquisition_date' => 'date', 'manual_references' => 'array', 'evidence_references' => 'array'];
    }

    public function batteries(): HasMany
    {
        return $this->hasMany(UasBattery::class, 'compatible_uas_aircraft_id');
    }

    public function defects(): HasMany
    {
        return $this->hasMany(UasAircraftDefect::class, 'uas_aircraft_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(AircraftRegistration::class, 'uas_aircraft_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AircraftApproval::class, 'uas_aircraft_id');
    }
}
