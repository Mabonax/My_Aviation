<?php

namespace App\Domains\Uas\Missions\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Uas\Batteries\Domain\Models\UasMissionBatteryUsage;
use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\Crew\Domain\Models\UasMissionCrewMember;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Documents\Domain\Models\EvidenceLink;
use App\Domains\Uas\FlightFolios\Domain\Models\AircraftFlightFolio;
use App\Domains\Uas\FlightLogs\Domain\Models\PilotLogEntry;
use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Tracks\Domain\Models\UasFlightTrack;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UasMission extends Model
{
    protected $fillable = [
        'aeronautical_context',
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
        'uas_operator_id',
        'uas_aircraft_id',
        'uas_pilot_id',
        'observers_crew',
        'planned_start_at',
        'planned_end_at',
        'actual_takeoff_at',
        'actual_landing_at',
        'actual_flight_duration_minutes',
        'completed_at',
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
        'post_flight_propagation_state',
        'post_flight_propagated_at',
        'post_flight_propagation_results',
        'post_flight_declaration',
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
            'aeronautical_context' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'takeoff_point' => 'array',
            'landing_point' => 'array',
            'mission_polygon' => 'array',
            'flight_route' => 'array',
            'observers_crew' => 'array',
            'planned_start_at' => 'datetime',
            'planned_end_at' => 'datetime',
            'actual_takeoff_at' => 'datetime',
            'actual_landing_at' => 'datetime',
            'actual_flight_duration_minutes' => 'integer',
            'completed_at' => 'datetime',
            'planned_distance_km' => 'decimal:2',
            'approvals' => 'array',
            'risk_assessment' => 'array',
            'lifecycle_state' => MissionLifecycleState::class,
            'release_gate_results' => 'array',
            'post_flight_propagated_at' => 'datetime',
            'post_flight_propagation_results' => 'array',
            'post_flight_declaration' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'uas_aircraft_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UasOperator::class, 'uas_operator_id');
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

    public function gisProjectAssignment(): HasOne
    {
        return $this->hasOne(UasGisProjectMission::class, 'uas_mission_id');
    }

    public function pilotLogEntry(): HasOne
    {
        return $this->hasOne(PilotLogEntry::class, 'uas_mission_id');
    }

    public function aircraftFlightFolio(): HasOne
    {
        return $this->hasOne(AircraftFlightFolio::class, 'uas_mission_id');
    }

    public function evidenceLinks(): MorphMany
    {
        return $this->morphMany(EvidenceLink::class, 'evidenceable');
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
