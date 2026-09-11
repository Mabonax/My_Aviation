<?php

namespace App\Domains\Uas\Batteries\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasBattery extends Model
{
    protected $fillable = [
        'battery_uid', 'manufacturer', 'model', 'serial_number', 'compatible_uas_aircraft_id', 'cycle_count', 'maximum_cycles', 'health_status', 'acquisition_date', 'last_used_at', 'damage_incidents', 'retirement_status', 'charge_history', 'evidence_references', 'regulatory_source', 'regulatory_source_version', 'regulatory_effective_date', 'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'last_used_at' => 'date',
            'charge_history' => 'array',
            'evidence_references' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function compatibleAircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'compatible_uas_aircraft_id');
    }

    public function missionUsages(): HasMany
    {
        return $this->hasMany(UasMissionBatteryUsage::class, 'uas_battery_id');
    }
}