<?php

namespace App\Domains\Uas\Maintenance\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Maintenance\Application\Queries\AircraftMaintenanceSummary;
use App\Domains\Uas\Maintenance\Domain\Models\MaintenanceTask;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AircraftMaintenanceController extends Controller
{
    public function index(Request $request, UasAircraft $aircraft, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context);
        $props = [
            'aircraft' => ['id' => $aircraft->id, 'registration' => $aircraft->registration, 'operational_status' => $aircraft->operational_status],
            'components' => $aircraft->components()->get(['id', 'name', 'status', 'installed_at',
                'accumulated_hours', 'accumulated_cycles', 'life_limit_hours', 'life_limit_cycles',
                'serial_number', 'removed_at', 'replaces_component_id', 'removal_evidence', 'installation_evidence'])
                ->map(function ($component) use ($operator) {
                    $record = $component->toArray();
                    foreach (['removal_evidence', 'installation_evidence'] as $key) {
                        if ((int) data_get($record, $key.'.operator_id') !== (int) $operator->id) {
                            unset($record[$key]);
                        }
                    }
                    return $record;
                }),
            'tasks' => MaintenanceTask::query()->where('uas_aircraft_id', $aircraft->id)
                ->where('uas_operator_id', $operator->id)->latest('id')->paginate(20)->withQueryString()
                ->through(fn ($task) => [...$task->toArray(), 'due_state' => app(AircraftMaintenanceSummary::class)
                    ->taskState($task, $aircraft->components->firstWhere('id', $task->uas_aircraft_component_id))]),
            'summary' => app(AircraftMaintenanceSummary::class)->execute($aircraft),
            'can_manage' => $context->canManageOperator($request->user(), $operator) && Gate::allows('update', $aircraft),
            'can_certify' => app(\App\Domains\Uas\Maintenance\Application\Queries\CurrentMaintenanceAuthority::class)
                ->find($operator->id, $aircraft->id, $request->user()->id) !== null,
            'can_return_to_service' => \App\Domains\Uas\Maintenance\Domain\Models\MaintenanceAuthority::query()
                ->where('uas_operator_id', $operator->id)->where('uas_aircraft_id', $aircraft->id)
                ->where('user_id', $request->user()->id)->whereNull('revoked_at')->whereDate('valid_until', '>=', today())
                ->where('can_return_to_service', true)->exists()
                && app(\App\Domains\Uas\Maintenance\Application\Queries\CurrentMaintenanceAuthority::class)
                    ->find($operator->id, $aircraft->id, $request->user()->id) !== null,
            'maintenance_releases' => \Illuminate\Support\Facades\DB::table('uas_maintenance_releases')
                ->where('uas_operator_id', $operator->id)->where('uas_aircraft_id', $aircraft->id)->latest('id')->limit(20)->get(),
            'authorities' => \App\Domains\Uas\Maintenance\Domain\Models\MaintenanceAuthority::query()
                ->where('uas_operator_id', $operator->id)->where('uas_aircraft_id', $aircraft->id)->latest('id')->get(),
            'authority_members' => \App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership::query()
                ->where('uas_operator_id', $operator->id)->where('status', 'active')->with('user:id,name')->get()
                ->map(fn ($membership) => ['id' => $membership->user_id, 'name' => $membership->user->name])->unique('id')->values(),
        ];
        return $request->expectsJson() ? ApiResponse::success($props) : Inertia::render('aircraft/maintenance', $props);
    }

    public function store(Request $request, UasAircraft $aircraft, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context, true);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'requirement_source' => ['required', 'string', 'max:2000'],
            'uas_aircraft_component_id' => ['nullable', 'integer'],
            'due_at' => ['nullable', 'date_format:Y-m-d'],
            'due_hours' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'due_cycles' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'interval_hours' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'interval_cycles' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
        ]);
        $task = DB::transaction(function () use ($data, $aircraft, $operator, $request) {
            UasAircraft::query()->lockForUpdate()->findOrFail($aircraft->id);
            if (($data['due_at'] ?? null) === null && ($data['due_hours'] ?? null) === null && ($data['due_cycles'] ?? null) === null) {
                throw ValidationException::withMessages(['due_at' => 'At least one maintenance threshold is required.']);
            }
            $recurring = ($data['interval_days'] ?? null) !== null
                || ($data['interval_hours'] ?? null) !== null || ($data['interval_cycles'] ?? null) !== null;
            if ($recurring) {
                foreach (['due_at' => 'interval_days', 'due_hours' => 'interval_hours', 'due_cycles' => 'interval_cycles'] as $threshold => $interval) {
                    if ((($data[$threshold] ?? null) !== null) !== (($data[$interval] ?? null) !== null)) {
                        throw ValidationException::withMessages([$interval => 'Every recurring threshold requires its matching interval and initial due value.']);
                    }
                }
            }
            if (($data['uas_aircraft_component_id'] ?? null) !== null) {
                if (! $aircraft->components()->whereKey($data['uas_aircraft_component_id'])->where('status', 'active')->exists()) {
                    throw ValidationException::withMessages(['uas_aircraft_component_id' => 'Select an active component belonging to this aircraft.']);
                }
            } elseif (($data['due_hours'] ?? null) !== null || ($data['due_cycles'] ?? null) !== null) {
                throw ValidationException::withMessages(['uas_aircraft_component_id' => 'Hours and cycle thresholds require a tracked component.']);
            }
            $task = MaintenanceTask::query()->create([...$data,
                'uas_operator_id' => $operator->id, 'uas_aircraft_id' => $aircraft->id, 'created_by' => $request->user()->id]);
            $this->audit($request, $task, 'maintenance.scheduled');
            return $task;
        });
        return $request->expectsJson() ? ApiResponse::success(['task' => $task], 'Maintenance task scheduled.', 201)
            : redirect()->route('aircraft.maintenance.index', $aircraft);
    }

    public function complete(Request $request, UasAircraft $aircraft, MaintenanceTask $task, CurrentOperatorContext $context)
    {
        $operator = $this->authorizeAircraft($request, $aircraft, $context);
        abort_unless((int) $task->uas_aircraft_id === (int) $aircraft->id
            && (int) $task->uas_operator_id === (int) $operator->id, 404);
        abort_unless(app(\App\Domains\Uas\Maintenance\Application\Queries\CurrentMaintenanceAuthority::class)
            ->find($operator->id, $aircraft->id, $request->user()->id), 403);
        $data = $request->validate([
            'work_performed' => ['required', 'string', 'max:2000'],
            'technician' => ['required', 'string', 'max:180'],
            'parts_components' => ['required', 'string', 'max:2000'],
            'evidence_reference' => ['required', 'string', 'max:2000'],
            'certification' => ['required', 'string', 'max:2000'],
            'return_to_service_state' => ['required', 'in:serviceable,flight_restricted,unserviceable'],
            'return_to_service_notes' => ['required', 'string', 'max:2000'],
            'completion_confirmed' => ['required', 'accepted'],
            'end_recurrence' => ['sometimes', 'boolean'],
            'end_recurrence_reason' => ['nullable', 'required_if:end_recurrence,1', 'string', 'max:2000'],
        ]);
        $task = DB::transaction(function () use ($task, $aircraft, $request, $data, $operator) {
            $aircraft = UasAircraft::query()->lockForUpdate()->findOrFail($aircraft->id);
            $authority = app(\App\Domains\Uas\Maintenance\Application\Queries\CurrentMaintenanceAuthority::class)
                ->require($operator->id, $aircraft->id, $request->user()->id);
            $task = MaintenanceTask::query()->lockForUpdate()->findOrFail($task->id);
            if ($task->completed_at === null) {
                if ($request->boolean('end_recurrence')) {
                    $component = $aircraft->components()->find($task->uas_aircraft_component_id);
                    if (! $component || ! in_array($component->status, ['removed', 'awaiting_replacement', 'retired'], true)
                        || ! filled($data['end_recurrence_reason'] ?? null)) {
                        throw ValidationException::withMessages(['end_recurrence' => 'Only an uninstalled component programme can end, with an explicit reason.']);
                    }
                }
                $task->forceFill(['completed_at' => now(), 'completed_by' => $request->user()->id,
                    'completion_evidence' => [...$data, 'certifying_user_id' => $request->user()->id,
                        'authority_snapshot' => $authority->toArray()]])->save();
                $before = $aircraft->only('operational_status');
                $outcome = $data['return_to_service_state'];
                // Completion may impose a restriction, but never clears an existing one.
                $mayRestrict = app(\App\Domains\Uas\Aircraft\Domain\Services\AircraftServiceabilityEvaluator::class)
                    ->mayBeAssignedToReleasedFlight($aircraft);
                if ($outcome !== 'serviceable' && ($mayRestrict
                    || ($aircraft->operational_status === 'flight_restricted' && $outcome === 'unserviceable'))) {
                    $aircraft->forceFill(['operational_status' => $outcome, 'maintenance_restriction_task_id' => $task->id])->save();
                    app(RecordAuditEntry::class)->execute(new AuditEntryData(
                        actor: $request->user(), auditable: $aircraft, action: 'maintenance.serviceability_restricted',
                        requirementId: 'FR-MNT-003', regulatorySource: $task->requirement_source,
                        previousValues: $before, newValues: [...$aircraft->only('operational_status'), 'maintenance_task_id' => $task->id],
                        operatorId: $task->uas_operator_id, operatorContextSource: 'maintenance_workspace',
                    ));
                }
                $next = $request->boolean('end_recurrence') ? null
                    : app(\App\Domains\Uas\Maintenance\Application\Actions\GenerateNextMaintenanceTask::class)
                        ->execute($task, $request->user()->id);
                if ($next) {
                    $this->audit($request, $next, 'maintenance.successor_scheduled');
                }
                $this->audit($request, $task, 'maintenance.completed');
            }
            return $task;
        });
        return $request->expectsJson() ? ApiResponse::success(['task' => $task,
            'next_task' => MaintenanceTask::query()->where('previous_task_id', $task->id)->first()], 'Maintenance completion recorded.')
            : redirect()->route('aircraft.maintenance.index', $aircraft);
    }

    protected function authorizeAircraft(Request $request, UasAircraft $aircraft, CurrentOperatorContext $context, bool $manage = false)
    {
        $operator = $context->requireFromRequest($request);
        abort_unless($operator->aircraft()->whereKey($aircraft->id)->wherePivot('status', 'active')->exists(), 404);
        Gate::authorize($manage ? 'update' : 'view', $aircraft);
        if ($manage) {
            abort_unless($context->canManageOperator($request->user(), $operator), 403);
        }
        return $operator;
    }

    private function audit(Request $request, MaintenanceTask $task, string $action): void
    {
        app(RecordAuditEntry::class)->execute(new AuditEntryData(
            actor: $request->user(), auditable: $task, action: $action,
            requirementId: 'FR-MNT-001/FR-MNT-003', regulatorySource: $task->requirement_source,
            previousValues: null, newValues: $task->toArray(), operatorId: $task->uas_operator_id,
            operatorContextSource: 'maintenance_workspace',
        ));
    }
}
