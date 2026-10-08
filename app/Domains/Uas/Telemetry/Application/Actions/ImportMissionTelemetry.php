<?php

namespace App\Domains\Uas\Telemetry\Application\Actions;

use App\Domains\Uas\Missions\Application\Actions\PropagatePostFlightRecords;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Telemetry\Domain\Models\TelemetryImport;
use App\Domains\Uas\Telemetry\Domain\Services\CanonicalCsvTelemetryParser;
use App\Domains\Uas\Tracks\Application\Actions\RecordFlightTrack;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ImportMissionTelemetry
{
    public function __construct(
        private readonly CanonicalCsvTelemetryParser $parser,
        private readonly RecordFlightTrack $tracks,
        private readonly PropagatePostFlightRecords $propagate,
        private readonly RecordAuditEntry $audit,
    ) {}

    public function stage(UasMission $mission, User $actor, string $csv): TelemetryImport
    {
        Gate::forUser($actor)->authorize('update', $mission);
        $flight = $this->parser->parse($csv);
        $hash = hash('sha256', json_encode($flight, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($mission, $actor, $csv, $flight, $hash) {
            // Serialise duplicate detection across missions of the same operator.
            UasOperator::query()->lockForUpdate()->findOrFail($mission->uas_operator_id);
            $mission = UasMission::query()->lockForUpdate()->findOrFail($mission->id);
            Gate::forUser($actor)->authorize('update', $mission);
            $this->guardOpenClosure($mission);
            if ($mission->aircraft?->serial_number !== $flight['aircraft_serial']) {
                $this->fail('file', 'The log aircraft serial does not match the mission aircraft.');
            }

            $existing = TelemetryImport::query()->where('uas_operator_id', $mission->uas_operator_id)
                ->where('flight_sha256', $hash)->first();
            if ($existing) {
                if ((int) $existing->uas_mission_id !== (int) $mission->id) {
                    $this->fail('file', 'This flight has already been imported for another mission.');
                }
                return $existing;
            }
            $import = TelemetryImport::query()->create([
                'uas_operator_id' => $mission->uas_operator_id,
                'uas_mission_id' => $mission->id,
                'uas_aircraft_id' => $mission->uas_aircraft_id,
                'uas_pilot_id' => $mission->uas_pilot_id,
                'imported_by' => $actor->id,
                'format' => $flight['format'],
                'content_sha256' => hash('sha256', $csv),
                'flight_sha256' => $hash,
                'raw_csv' => $csv,
                'normalised_flight' => $flight,
                'state' => 'pending_review',
            ]);
            $this->record($import, $actor, 'telemetry.staged', [
                'mission_id' => $mission->id, 'content_sha256' => $import->content_sha256,
                'flight_sha256' => $hash, 'point_count' => $flight['point_count'],
            ]);
            return $import;
        });
    }

    public function accept(UasMission $mission, TelemetryImport $import, User $actor, array $declarations): TelemetryImport
    {
        return DB::transaction(function () use ($mission, $import, $actor, $declarations) {
            $mission = UasMission::query()->lockForUpdate()->findOrFail($mission->id);
            Gate::forUser($actor)->authorize('update', $mission);
            $import = TelemetryImport::query()->lockForUpdate()->findOrFail($import->id);
            abort_unless((int) $import->uas_mission_id === (int) $mission->id
                && (int) $import->uas_operator_id === (int) $mission->uas_operator_id, 404);
            if ($import->state === 'accepted') {
                return $import;
            }
            $this->guardOpenClosure($mission);
            if ((int) $import->uas_aircraft_id !== (int) $mission->uas_aircraft_id
                || (int) $import->uas_pilot_id !== (int) $mission->uas_pilot_id
                || $mission->aircraft?->serial_number !== $import->normalised_flight['aircraft_serial']) {
                $this->fail('mission', 'Mission aircraft or pilot changed after import; stage a new flight for review.');
            }
            foreach (['telemetry_confirmed', 'pilot_confirmed', 'aircraft_confirmed'] as $key) {
                if (($declarations[$key] ?? null) !== true) {
                    $this->fail($key, 'Explicit confirmation is required.');
                }
            }
            foreach (['defects_declared', 'occurrence_declared'] as $key) {
                if (! array_key_exists($key, $declarations) || ! is_bool($declarations[$key])) {
                    $this->fail($key, 'An explicit boolean declaration is required.');
                }
            }
            $flight = $import->normalised_flight;
            foreach (['actual_takeoff_at', 'actual_landing_at'] as $key) {
                if ($mission->$key !== null && ! $mission->$key->equalTo(Carbon::parse($flight[$key]))) {
                    $this->fail('mission', 'Imported times conflict with existing actual flight records.');
                }
            }

            $track = $this->tracks->execute($mission, [
                'source_type' => 'telemetry_import',
                'track_reference' => 'TEL-'.$import->id,
                'started_at' => $flight['actual_takeoff_at'],
                'ended_at' => $flight['actual_landing_at'],
                'points' => $flight['points'],
                'notes' => 'Reviewed YAW CSV v1 import; SHA-256 '.$import->content_sha256,
            ], $actor);
            // Existing checklist and closure guards still apply. A failure rolls back the track.
            $results = $this->propagate->execute($mission, $actor, [
                'actual_takeoff_at' => $flight['actual_takeoff_at'],
                'actual_landing_at' => $flight['actual_landing_at'],
                'pilot_confirmed' => true,
                'aircraft_confirmed' => true,
                'defects_declared' => $declarations['defects_declared'],
                'occurrence_declared' => $declarations['occurrence_declared'],
                'closure_notes' => $declarations['closure_notes'] ?? null,
            ]);
            $import->forceFill([
                'state' => 'accepted',
                'accepted_by' => $actor->id,
                'accepted_at' => now(),
                'uas_flight_track_id' => $track->id,
                'propagation_results' => $results,
            ])->save();
            $this->record($import, $actor, 'telemetry.accepted', [
                'mission_id' => $mission->id, 'flight_track_id' => $track->id,
                'content_sha256' => $import->content_sha256, 'declarations' => $declarations,
                'propagation_results' => $results,
            ]);
            return $import;
        });
    }

    private function guardOpenClosure(UasMission $mission): void
    {
        if (! in_array($mission->lifecycle_state, [MissionLifecycleState::Completed, MissionLifecycleState::PostFlightReview], true)
            || $mission->post_flight_propagated_at !== null || ! $mission->uas_aircraft_id || ! $mission->uas_pilot_id) {
            $this->fail('mission', 'A completed, unpropagated mission with a pilot and aircraft is required.');
        }
        if (! $mission->operator->aircraft()->whereKey($mission->uas_aircraft_id)->wherePivot('status', 'active')->exists()) {
            $this->fail('mission', 'The mission aircraft must be actively assigned to its operator.');
        }
    }

    private function record(TelemetryImport $import, User $actor, string $action, array $values): void
    {
        $this->audit->execute(new AuditEntryData(
            actor: $actor, auditable: $import, action: $action, requirementId: 'FR-TEL-001',
            regulatorySource: 'YAW CSV v1 operational evidence; not a regulatory approval',
            previousValues: null, newValues: $values,
            operatorId: $import->uas_operator_id, operatorContextSource: 'telemetry_import',
        ));
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
