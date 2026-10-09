<?php

namespace App\Domains\Uas\Maintenance\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Maintenance\Application\Queries\AircraftMaintenanceSummary;
use App\Domains\Uas\Maintenance\Application\Queries\CurrentMaintenanceAuthority;
use App\Domains\Uas\Maintenance\Domain\Models\MaintenanceTask;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceReleaseController extends AircraftMaintenanceController
{
    public function release(Request $request, UasAircraft $aircraft, MaintenanceTask $task, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context);
        abort_unless((int) $task->uas_operator_id === (int) $operator->id && (int) $task->uas_aircraft_id === (int) $aircraft->id, 404);
        $data = $request->validate([
            'evidence_reference' => ['required', 'string', 'max:2000'],
            'release_notes' => ['required', 'string', 'max:2000'],
            'release_confirmed' => ['required', 'accepted'],
        ]);
        $release = DB::transaction(function () use ($request, $aircraft, $task, $operator, $data) {
            $aircraft = UasAircraft::query()->lockForUpdate()->findOrFail($aircraft->id);
            $authority = app(CurrentMaintenanceAuthority::class)->require($operator->id, $aircraft->id, $request->user()->id, true);
            $existing = DB::table('uas_maintenance_releases')->where('maintenance_task_id', $task->id)->first();
            if ($existing) {
                return $existing;
            }
            $task = MaintenanceTask::query()->lockForUpdate()->findOrFail($task->id);
            $restriction = MaintenanceTask::query()->find($aircraft->maintenance_restriction_task_id);
            if (! in_array($aircraft->operational_status, ['flight_restricted', 'unserviceable'], true)
                || ! $restriction || (int) $restriction->uas_operator_id !== (int) $operator->id
                || $aircraft->operational_status !== data_get($restriction->completion_evidence, 'return_to_service_state')) {
                throw ValidationException::withMessages(['release' => 'Only this operator\'s recorded maintenance restriction can be released here. Other aircraft restrictions require their own authorised process.']);
            }
            if (! $task->completed_at || $task->id <= $restriction->id
                || $task->completed_at->lt($restriction->completed_at)
                || data_get($task->completion_evidence, 'return_to_service_state') !== 'serviceable'
                || ! data_get($task->completion_evidence, 'authority_snapshot.id')) {
                throw ValidationException::withMessages(['release' => 'Complete a new repair or inspection task after the restriction, with an authorised serviceable assessment.']);
            }
            $maintenance = app(AircraftMaintenanceSummary::class)->execute($aircraft);
            $defects = UasAircraftDefect::query()->where('uas_aircraft_id', $aircraft->id)
                ->whereNotIn('status', ['closed', 'resolved', 'rectified', 'cancelled'])
                ->whereIn('serviceability_impact', ['grounded', 'flight_restricted', 'maintenance_required'])->exists();
            $inFlight = UasMission::query()->where('uas_aircraft_id', $aircraft->id)->where('lifecycle_state', 'in_progress')->exists();
            if ($maintenance['status'] !== 'green' || $defects || $inFlight) {
                throw ValidationException::withMessages(['release' => 'Resolve maintenance limits, installation gaps, blocking defects and active flights before return to service.']);
            }
            $before = $aircraft->only(['operational_status', 'maintenance_restriction_task_id']);
            $evidence = [...$data, 'authority_snapshot' => $authority->toArray(), 'repair_completion_evidence' => $task->completion_evidence];
            $id = DB::table('uas_maintenance_releases')->insertGetId([
                'uas_operator_id' => $operator->id, 'uas_aircraft_id' => $aircraft->id,
                'maintenance_task_id' => $task->id, 'restriction_task_id' => $restriction->id,
                'released_by' => $request->user()->id, 'released_at' => now(),
                'previous_operational_status' => $aircraft->operational_status, 'evidence' => json_encode($evidence, JSON_THROW_ON_ERROR),
            ]);
            $aircraft->forceFill(['operational_status' => 'active_serviceable', 'maintenance_restriction_task_id' => null])->save();
            app(RecordAuditEntry::class)->execute(new AuditEntryData(
                actor: $request->user(), auditable: $aircraft, action: 'maintenance.returned_to_service',
                requirementId: 'FR-MNT-003', regulatorySource: $data['evidence_reference'],
                previousValues: $before, newValues: [...$aircraft->only('operational_status'), 'release_id' => $id, 'evidence' => $evidence],
                operatorId: $operator->id, operatorContextSource: 'maintenance_workspace',
            ));
            return DB::table('uas_maintenance_releases')->find($id);
        });
        return $request->expectsJson() ? ApiResponse::success(['release' => $release], 'Maintenance return to service recorded.')
            : redirect()->route('aircraft.maintenance.index', $aircraft);
    }
}
