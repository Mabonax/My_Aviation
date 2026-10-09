<?php

namespace App\Domains\Uas\Maintenance\Application\Actions;

use App\Domains\Uas\Maintenance\Domain\Models\MaintenanceTask;
use Illuminate\Validation\ValidationException;

class GenerateNextMaintenanceTask
{
    /** Runs inside completion's transaction with aircraft and parent task locked. */
    public function execute(MaintenanceTask $task, int $actorId): ?MaintenanceTask
    {
        if ($task->interval_days === null && $task->interval_hours === null && $task->interval_cycles === null) {
            return null;
        }
        if ($task->completed_at === null) {
            throw ValidationException::withMessages(['task' => 'Complete the maintenance task before generating its successor.']);
        }
        $existing = MaintenanceTask::query()->where('previous_task_id', $task->id)->first();
        if ($existing) {
            return $existing;
        }
        $dueAt = $task->interval_days !== null ? $task->due_at?->copy()->addDays($task->interval_days) : null;
        // Integer hundredths avoid accumulating floating point threshold drift.
        $dueHours = $task->interval_hours !== null
            ? (round((float) $task->due_hours * 100) + round((float) $task->interval_hours * 100)) / 100 : null;
        $dueCycles = $task->interval_cycles !== null ? $task->due_cycles + $task->interval_cycles : null;
        if (($dueAt && $dueAt->year > 9999) || ($dueHours !== null && $dueHours > 99999999.99)
            || ($dueCycles !== null && $dueCycles > 4294967295)) {
            throw ValidationException::withMessages(['task' => 'The next interval exceeds storage limits; review the maintenance programme before completion.']);
        }
        return MaintenanceTask::query()->create([
            'uas_operator_id' => $task->uas_operator_id,
            'uas_aircraft_id' => $task->uas_aircraft_id,
            'uas_aircraft_component_id' => $task->uas_aircraft_component_id,
            'title' => $task->title, 'requirement_source' => $task->requirement_source,
            'due_at' => $dueAt?->toDateString(), 'due_hours' => $dueHours, 'due_cycles' => $dueCycles,
            'interval_days' => $task->interval_days, 'interval_hours' => $task->interval_hours,
            'interval_cycles' => $task->interval_cycles, 'previous_task_id' => $task->id,
            'created_by' => $actorId,
        ]);
    }
}
