<?php

namespace App\Domains\Uas\Aircraft\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasAircraftModel extends Model
{
    public const CATALOGUE_DRAFT = 'draft';
    public const CATALOGUE_VERIFIED = 'verified';
    public const CATALOGUE_DEPRECATED = 'deprecated';

    protected $table = 'uas_aircraft_models';

    protected $fillable = [
        'manufacturer_id',
        'model',
        'family',
        'aircraft_type',
        'primary_use',
        'status',
        'weight_kg',
        'mtow_kg',
        'max_payload_kg',
        'max_flight_time_min',
        'max_speed_m_s',
        'max_range_km',
        'service_ceiling_m',
        'max_wind_m_s',
        'ip_rating',
        'operating_temp_c',
        'dimensions',
        'wingspan_mm',
        'gnss',
        'camera_payload_summary',
        'remote_id',
        'source_url',
        'image_source_url',
        'image_license_status',
        'notes',
        'battery_package',
        'component_package',
        'maintenance_package',
        'package_status',
        'verified_at',
        'media_status',
        'source_priority',
        'catalogue_status',
        'local_image_path',
        'media_approved_at',
        'media_approved_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:3',
            'mtow_kg' => 'decimal:3',
            'max_payload_kg' => 'decimal:3',
            'max_flight_time_min' => 'integer',
            'max_speed_m_s' => 'decimal:3',
            'max_range_km' => 'decimal:3',
            'service_ceiling_m' => 'integer',
            'max_wind_m_s' => 'decimal:3',
            'wingspan_mm' => 'integer',
            'battery_package' => 'array',
            'component_package' => 'array',
            'maintenance_package' => 'array',
            'verified_at' => 'date',
            'media_approved_at' => 'datetime',
        ];
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(UasManufacturer::class, 'manufacturer_id');
    }

    public function aircraft(): HasMany
    {
        return $this->hasMany(UasAircraft::class, 'aircraft_model_id');
    }

    public function sourceComponents(): HasMany
    {
        return $this->hasMany(UasAircraftComponent::class, 'source_aircraft_model_id');
    }

    public function mediaApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'media_approved_by');
    }
}
