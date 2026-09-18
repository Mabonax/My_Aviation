<?php

namespace App\Domains\Uas\Missions\Application\Actions;

use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\FlightFolios\Domain\Models\AircraftFlightFolio;
use App\Domains\Uas\FlightLogs\Domain\Models\PilotLogEntry;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropagatePostFlightRecords
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasMission $mission, User $actor, array $closureData = [], ?string $ipAddress = null, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($mission, $actor, $closureData, $ipAddress, $userAgent): array {
            $mission->refresh()->loadMissing(['aircraft', 'pilot', 'batteryUsages.battery', 'flightTracks', 'defects']);

            if ($mission->post_flight_propagated_at && filled($mission->post_flight_propagation_results)) {
                return $mission->post_flight_propagation_results;
            }

            $previous = $mission->only(['lifecycle_state', 'actual_takeoff_at', 'actual_landing_at', 'actual_flight_duration_minutes', 'completed_at', 'post_flight_declaration', 'post_flight_propagation_state', 'post_flight_propagated_at', 'post_flight_propagation_results']);
            $this->applyClosureActuals($mission, $closureData);

            $this->guardMissionCanPropagate($mission);

            $checklist = $this->latestPostFlightChecklist($mission);
            $batteryUsages = $mission->batteryUsages;
            $defects = $mission->defects;
            $tracks = $mission->flightTracks;
            $flightHours = $this->flightHours($mission);
            $completedAt = now();
            $evidence = [
                'mission_id' => $mission->id,
                'mission_number' => $mission->mission_number,
                'operator_id' => $mission->uas_operator_id,
                'aircraft_id' => $mission->uas_aircraft_id,
                'pilot_id' => $mission->uas_pilot_id,
                'actual_takeoff_at' => $mission->actual_takeoff_at?->toISOString(),
                'actual_landing_at' => $mission->actual_landing_at?->toISOString(),
                'actual_flight_duration_minutes' => $mission->actual_flight_duration_minutes,
                'completed_at' => $completedAt->toISOString(),
                'post_flight_declaration' => $mission->post_flight_declaration ?? [],
                'post_flight_checklist_id' => $checklist->id,
                'post_flight_checklist_state' => $checklist->state,
                'post_flight_checklist_version' => $checklist->checklist_version,
                'battery_usage_ids' => $batteryUsages->pluck('id')->values()->all(),
                'flight_track_ids' => $tracks->pluck('id')->values()->all(),
                'defect_ids' => $defects->pluck('id')->values()->all(),
                'propagated_by' => $actor->id,
                'propagated_at' => now()->toISOString(),
                'regulatory_source' => $mission->regulatory_source,
                'regulatory_source_version' => $mission->regulatory_source_version,
            ];

            $pilotLog = PilotLogEntry::query()->updateOrCreate(
                ['uas_mission_id' => $mission->id],
                [
                    'uas_pilot_id' => $mission->uas_pilot_id,
                    'flight_date' => ($mission->actual_takeoff_at ?? $mission->planned_start_at ?? now())->toDateString(),
                    'aircraft_registration' => $mission->aircraft?->registration,
                    'operation_type' => $mission->operation_category,
                    'flight_hours' => $flightHours,
                    'launch_location' => $this->pointLabel($mission->takeoff_point) ?? $mission->location,
                    'landing_location' => $this->pointLabel($mission->landing_point) ?? $mission->location,
                    'remarks' => $this->remarks($mission, $checklist),
                    'evidence_references' => $evidence,
                ],
            );

            $folio = AircraftFlightFolio::query()->updateOrCreate(
                ['uas_mission_id' => $mission->id],
                [
                    'uas_aircraft_id' => $mission->uas_aircraft_id,
                    'uas_pilot_id' => $mission->uas_pilot_id,
                    'flight_date' => ($mission->actual_takeoff_at ?? $mission->planned_start_at ?? now())->toDateString(),
                    'folio_reference' => 'PF-'.$mission->mission_number,
                    'flight_hours' => $flightHours,
                    'battery_cycles' => $batteryUsages->sum('cycles_added'),
                    'charging_fuel_oil_records' => $batteryUsages->map(fn ($usage): array => [
                        'battery_uid' => $usage->battery?->battery_uid,
                        'serial_number' => $usage->battery?->serial_number,
                        'cycles_added' => $usage->cycles_added,
                        'state_of_charge_start' => $usage->state_of_charge_start,
                        'state_of_charge_end' => $usage->state_of_charge_end,
                        'used_at' => $usage->used_at?->toISOString(),
                        'notes' => $usage->notes,
                    ])->values()->all(),
                    'maintenance_certification_entries' => [[
                        'source' => 'post_flight_propagation',
                        'checklist_state' => $checklist->state,
                        'checklist_version' => $checklist->checklist_version,
                        'exceptions' => $checklist->exceptions,
                        'open_defects' => $defects->where('status', 'open')->count(),
                        'grounding_defects' => $defects->where('serviceability_impact', 'grounded')->count(),
                        'follow_up_required' => filled($checklist->exceptions) || $defects->where('status', 'open')->isNotEmpty(),
                        'recorded_at' => now()->toISOString(),
                    ]],
                    'defects_reported' => $this->defectSummary($defects),
                    'available_offline' => true,
                    'evidence_references' => $evidence,
                ],
            );

            $results = [
                'state' => filled($checklist->exceptions) || $defects->where('status', 'open')->isNotEmpty() ? 'propagated_with_follow_up' : 'propagated',
                'pilot_log_entry_id' => $pilotLog->id,
                'aircraft_flight_folio_id' => $folio->id,
                'battery_cycles_summarised' => $batteryUsages->sum('cycles_added'),
                'battery_usage_count' => $batteryUsages->count(),
                'flight_track_count' => $tracks->count(),
                'defect_count' => $defects->count(),
                'open_defect_count' => $defects->where('status', 'open')->count(),
                'post_flight_checklist_id' => $checklist->id,
                'post_flight_checklist_state' => $checklist->state,
                'actual_takeoff_at' => $mission->actual_takeoff_at?->toISOString(),
                'actual_landing_at' => $mission->actual_landing_at?->toISOString(),
                'actual_flight_duration_minutes' => $mission->actual_flight_duration_minutes,
                'completed_at' => $completedAt->toISOString(),
                'post_flight_declaration' => $mission->post_flight_declaration ?? [],
                'evidence' => $evidence,
            ];

            $mission->forceFill([
                'lifecycle_state' => MissionLifecycleState::PostFlightReview,
                'post_flight_propagation_state' => $results['state'],
                'post_flight_propagated_at' => $completedAt,
                'completed_at' => $completedAt,
                'post_flight_propagation_results' => $results,
                'updated_by' => $actor->id,
            ])->save();

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $mission,
                action: 'mission.post_flight_propagated',
                requirementId: 'FR-MIS-002',
                regulatorySource: $mission->regulatory_source,
                previousValues: $previous,
                newValues: $results,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $results;
        });
    }

    private function guardMissionCanPropagate(UasMission $mission): void
    {
        if (! in_array($mission->lifecycle_state, [MissionLifecycleState::Completed, MissionLifecycleState::PostFlightReview], true)) {
            throw ValidationException::withMessages(['mission' => 'Only completed missions can be propagated into post-flight records.']);
        }

        if ($mission->uas_pilot_id === null) {
            throw ValidationException::withMessages(['mission' => 'A pilot is required before post-flight records can be propagated.']);
        }

        if ($mission->uas_aircraft_id === null) {
            throw ValidationException::withMessages(['mission' => 'An aircraft is required before post-flight records can be propagated.']);
        }

        if (! $mission->actual_takeoff_at || ! $mission->actual_landing_at || $mission->actual_flight_duration_minutes === null) {
            throw ValidationException::withMessages(['actual_takeoff_at' => 'Actual takeoff and landing times are required before post-flight records can be propagated.']);
        }
    }

    private function applyClosureActuals(UasMission $mission, array $closureData): void
    {
        $takeoff = isset($closureData['actual_takeoff_at'])
            ? Carbon::parse($closureData['actual_takeoff_at'])
            : $mission->actual_takeoff_at;
        $landing = isset($closureData['actual_landing_at'])
            ? Carbon::parse($closureData['actual_landing_at'])
            : $mission->actual_landing_at;

        if (! $takeoff && ! $landing && $mission->planned_start_at && $mission->planned_end_at) {
            $takeoff = $mission->planned_start_at;
            $landing = $mission->planned_end_at;
        }

        if ($takeoff && $landing) {
            $mission->forceFill([
                'actual_takeoff_at' => $takeoff,
                'actual_landing_at' => $landing,
                'actual_flight_duration_minutes' => max(0, $takeoff->diffInMinutes($landing)),
                'post_flight_declaration' => [
                    'pilot_confirmed' => (bool) ($closureData['pilot_confirmed'] ?? data_get($mission->post_flight_declaration, 'pilot_confirmed', false)),
                    'aircraft_confirmed' => (bool) ($closureData['aircraft_confirmed'] ?? data_get($mission->post_flight_declaration, 'aircraft_confirmed', false)),
                    'defects_declared' => (bool) ($closureData['defects_declared'] ?? data_get($mission->post_flight_declaration, 'defects_declared', false)),
                    'occurrence_declared' => (bool) ($closureData['occurrence_declared'] ?? data_get($mission->post_flight_declaration, 'occurrence_declared', false)),
                    'closure_notes' => $closureData['closure_notes'] ?? data_get($mission->post_flight_declaration, 'closure_notes'),
                ],
            ]);
        }
    }

    private function latestPostFlightChecklist(UasMission $mission): UasMissionChecklist
    {
        $checklist = $mission->checklists()
            ->where('type', 'post_flight')
            ->latest('performed_at')
            ->first();

        if (! $checklist) {
            throw ValidationException::withMessages(['post_flight_checklist' => 'A post-flight checklist must be completed before propagation.']);
        }

        if ($checklist->state === 'blocked') {
            throw ValidationException::withMessages(['post_flight_checklist' => 'Blocked post-flight checklist results must be resolved before propagation.']);
        }

        return $checklist;
    }

    private function flightHours(UasMission $mission): float
    {
        if ($mission->actual_flight_duration_minutes !== null) {
            return round($mission->actual_flight_duration_minutes / 60, 2);
        }

        if (! $mission->planned_start_at || ! $mission->planned_end_at) {
            return 0.0;
        }

        return round(max(0, $mission->planned_start_at->diffInMinutes($mission->planned_end_at)) / 60, 2);
    }

    private function pointLabel(?array $point): ?string
    {
        if ($point === null) {
            return null;
        }

        if (filled($point['label'] ?? null)) {
            return $point['label'];
        }

        if (isset($point['latitude'], $point['longitude'])) {
            return $point['latitude'].', '.$point['longitude'];
        }

        return null;
    }

    private function remarks(UasMission $mission, UasMissionChecklist $checklist): string
    {
        return trim("{$mission->mission_number}: {$mission->purpose}. Post-flight {$checklist->state}.".($checklist->exceptions ? " Exceptions: {$checklist->exceptions}" : ''));
    }

    private function defectSummary($defects): ?string
    {
        if ($defects->isEmpty()) {
            return null;
        }

        return $defects
            ->map(fn ($defect): string => "{$defect->defect_number}: {$defect->title} ({$defect->status}, {$defect->serviceability_impact})")
            ->implode("\n");
    }
}
