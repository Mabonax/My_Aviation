<?php

namespace App\Domains\Uas\Aircraft\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasAircraftComponent extends Model
{
    protected $fillable = [
        'uas_aircraft_id',
        'source_aircraft_model_id',
        'package_item_key',
        'component_uid',
        'component_type',
        'name',
        'manufacturer',
        'model',
        'serial_number',
        'installed_at',
        'life_limit_hours',
        'life_limit_cycles',
        'accumulated_hours',
        'accumulated_cycles',
        'status',
        'maintenance_baseline',
        'evidence_references',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
            'life_limit_hours' => 'decimal:2',
            'accumulated_hours' => 'decimal:2',
            'maintenance_baseline' => 'array',
            'evidence_references' => 'array',
        ];
    }

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'uas_aircraft_id');
    }

    public function sourceModel(): BelongsTo
    {
        return $this->belongsTo(UasAircraftModel::class, 'source_aircraft_model_id');
    }
}

