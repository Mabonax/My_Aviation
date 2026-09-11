<?php

namespace App\Domains\Uas\Missions\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Tracks\Domain\Models\UasFlightTrack;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Uas\Batteries\Domain\Models\UasMissionBatteryUsage;
use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\Crew\Domain\Models\UasMissionCrewMember;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasMission extends Model
{
    protected $fillable = [
        'mission_number',
        'purpose',
        'client_project',
        'location',
        'location_search_query',
        'latitude',
        'longitude',
        'takeoff_point',
        'landing_point',
        'mission_polygon',
        'flight_route',
        'flight_radius_m',
        'operation_category',
        'uas_aircraft_id',
        'uas_pilot_id',
        'observers_crew',
        'planned_start_at',
        'planned_end_at',
        'maximum_altitude_ft',
        'planned_distance_km',
        'operation_visibility',
        'day_night',
        'weather',
        'airspace_assessment',
        'approvals',
        'risk_assessment',
        'emergency_arrangements',
        'lifecycle_state',
        'release_gate_state',
        'release_gate_results',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
        'responsible_role',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'takeoff_point' => 'array',
            'landing_point' => 'array',
            'mission_polygon' => 'array',
            'flight_route' => 'array',
            'observers_crew' => 'array',
            'planned_start_at' => 'datetime',
            'planned_end_at' => 'datetime',
            'planned_distance_km' => 'decimal:2',
            'approvals' => 'array',
            'risk_assessment' => 'array',
            'lifecycle_state' => MissionLifecycleState::class,
            'release_gate_results' => 'array',
            'regulatory_effective_date' => 'date',
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

    public function batteryUsages(): HasMany
    {
        return $this->hasMany(UasMissionBatteryUsage::class, 'uas_mission_id');
    }

    public function defects(): HasMany
    {
        return $this->hasMany(UasAircraftDefect::class, 'uas_mission_id');
    }

    public function flightTracks(): HasMany
    {
        return $this->hasMany(UasFlightTrack::class, 'uas_mission_id');
    }

    public function crewMembers(): HasMany
    {
        return $this->hasMany(UasMissionCrewMember::class, 'uas_mission_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(UasMissionChecklist::class, 'uas_mission_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
