<?php

namespace App\Domains\Uas\Aircraft\Domain\Models;

use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Documents\Domain\Models\EvidenceLink;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UasAircraft extends Model
{
    protected $table = 'uas_aircraft';

    protected $fillable = ['aircraft_model_id', 'registration', 'manufacturer', 'model', 'serial_number', 'internal_asset_number', 'aircraft_category', 'owner', 'operator', 'supplier', 'firmware_version', 'flight_controller_serial', 'remote_id_serial', 'acquisition_date', 'operational_status', 'onboarding_status', 'package_instantiation_state', 'package_instantiated_at', 'package_instantiation_results', 'base_location', 'manual_references', 'evidence_references'];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'package_instantiated_at' => 'datetime',
            'package_instantiation_results' => 'array',
            'manual_references' => 'array',
            'evidence_references' => 'array',
        ];
    }

    public function catalogueModel(): BelongsTo
    {
        return $this->belongsTo(UasAircraftModel::class, 'aircraft_model_id');
    }

    public function batteries(): HasMany
    {
        return $this->hasMany(UasBattery::class, 'compatible_uas_aircraft_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(UasAircraftComponent::class, 'uas_aircraft_id');
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

    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(UasOperator::class, 'uas_operator_aircraft', 'uas_aircraft_id', 'uas_operator_id')
            ->withPivot(['assignment_role', 'status', 'approved_from', 'approved_until', 'notes'])
            ->withTimestamps();
    }

    public function evidenceLinks(): MorphMany
    {
        return $this->morphMany(EvidenceLink::class, 'evidenceable');
    }
}
