<?php

namespace App\Domains\Uas\Batteries\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasBattery extends Model
{
    protected $fillable = [
        'battery_uid', 'manufacturer', 'model', 'serial_number', 'compatible_uas_aircraft_id', 'source_aircraft_model_id', 'package_item_key', 'package_instantiated_at', 'cycle_count', 'maximum_cycles', 'health_status', 'acquisition_date', 'last_used_at', 'damage_incidents', 'retirement_status', 'charge_history', 'evidence_references', 'regulatory_source', 'regulatory_source_version', 'regulatory_effective_date', 'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'last_used_at' => 'date',
            'package_instantiated_at' => 'datetime',
            'charge_history' => 'array',
            'evidence_references' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function compatibleAircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'compatible_uas_aircraft_id');
    }

    public function sourceModel(): BelongsTo
    {
        return $this->belongsTo(UasAircraftModel::class, 'source_aircraft_model_id');
    }

    public function missionUsages(): HasMany
    {
        return $this->hasMany(UasMissionBatteryUsage::class, 'uas_battery_id');
    }
}
