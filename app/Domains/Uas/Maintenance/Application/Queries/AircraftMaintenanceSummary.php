<?php

namespace App\Domains\Uas\Maintenance\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Maintenance\Domain\Models\MaintenanceTask;

class AircraftMaintenanceSummary
{
    public function taskState(MaintenanceTask $task, $component): array
    {
        $days = $task->due_at ? (int) today()->diffInDays($task->due_at->copy()->startOfDay(), false) : null;
        $hours = $task->due_hours !== null && $component ? round((float) $task->due_hours - (float) $component->accumulated_hours, 2) : null;
        $cycles = $task->due_cycles !== null && $component ? $task->due_cycles - $component->accumulated_cycles : null;
        $remaining = array_filter([$days, $hours, $cycles], fn ($value) => $value !== null);
        $status = $task->completed_at ? 'completed' : (collect($remaining)->contains(fn ($value) => $value < 0)
            ? 'overdue' : (collect($remaining)->contains(fn ($value) => $value === 0 || $value === 0.0) ? 'due' : 'scheduled'));
        if (! $task->completed_at && $task->uas_aircraft_component_id
            && (! $component || in_array($component->status, ['removed', 'retired'], true))) {
            $status = 'review_required';
        }
        return ['status' => $status, 'remaining_days' => $days, 'remaining_hours' => $hours, 'remaining_cycles' => $cycles];
    }

    public function execute(UasAircraft $aircraft): array
    {
        // Read fresh totals; a previously loaded relation must not hide a new flight.
        $components = $aircraft->components()->get()->keyBy('id');
        $reasons = [];
        $unknown = [];
        foreach ($components->whereNotIn('status', ['removed', 'retired']) as $component) {
            if ($component->status !== 'active'
                || ($component->life_limit_hours !== null && (float) $component->accumulated_hours >= (float) $component->life_limit_hours)
                || ($component->life_limit_cycles !== null && $component->accumulated_cycles >= $component->life_limit_cycles)) {
                $reasons[] = $component->name.' is unserviceable or has reached its component life limit.';
            }
            if ($component->installed_at === null) {
                $unknown[] = $component->name.' has no installation time; automatic usage coverage is incomplete.';
            }
        }
        // Aircraft safety is shared: an open obligation is not hidden by changing operator.
        $tasks = MaintenanceTask::query()->where('uas_aircraft_id', $aircraft->id)
            ->whereNull('completed_at')->get();
        foreach ($tasks as $task) {
            $component = $components->get($task->uas_aircraft_component_id);
            $due = in_array($this->taskState($task, $component)['status'], ['due', 'overdue', 'review_required'], true);
            if ($due) {
                $reasons[] = 'An aircraft maintenance obligation is due; authorised completion evidence is required.';
            }
        }
        return [
            'status' => $reasons ? 'red' : ($unknown ? 'amber' : 'green'),
            'blocking_reasons' => $reasons,
            'review_reasons' => $unknown,
            'tracked_component_count' => $components->count(),
            'open_task_count' => $tasks->count(),
        ];
    }
}
