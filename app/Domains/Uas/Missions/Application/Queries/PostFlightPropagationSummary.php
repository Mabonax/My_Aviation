<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class PostFlightPropagationSummary
{
    public function execute(UasMission $mission): array
    {
        $mission->loadMissing(['pilotLogEntry', 'aircraftFlightFolio']);

        $latestChecklist = $mission->checklists()
            ->where('type', 'post_flight')
            ->latest('performed_at')
            ->first();

        $canPropagate = in_array($mission->lifecycle_state, [MissionLifecycleState::Completed, MissionLifecycleState::PostFlightReview], true)
            && $mission->uas_pilot_id !== null
            && $mission->uas_aircraft_id !== null
            && $latestChecklist !== null
            && $latestChecklist->state !== 'blocked';

        return [
            'state' => $mission->post_flight_propagation_state ?? 'pending',
            'label' => $this->label($mission->post_flight_propagation_state ?? 'pending'),
            'can_propagate' => $canPropagate,
            'propagated_at' => $mission->post_flight_propagated_at?->toISOString(),
            'actual_takeoff_at' => $mission->actual_takeoff_at?->toISOString(),
            'actual_landing_at' => $mission->actual_landing_at?->toISOString(),
            'actual_flight_duration_minutes' => $mission->actual_flight_duration_minutes,
            'completed_at' => $mission->completed_at?->toISOString(),
            'pilot_log_entry_id' => $mission->pilotLogEntry?->id,
            'aircraft_flight_folio_id' => $mission->aircraftFlightFolio?->id,
            'latest_checklist_state' => $latestChecklist?->state,
            'post_flight_declaration' => $mission->post_flight_declaration ?? [],
            'results' => $mission->post_flight_propagation_results ?? [],
            'blocking_reasons' => $this->blockingReasons($mission, $latestChecklist),
        ];
    }

    private function label(string $state): string
    {
        return match ($state) {
            'propagated' => 'Propagated',
            'propagated_with_follow_up' => 'Propagated with follow-up',
            default => 'Pending propagation',
        };
    }

    private function blockingReasons(UasMission $mission, $latestChecklist): array
    {
        $reasons = [];

        if (! in_array($mission->lifecycle_state, [MissionLifecycleState::Completed, MissionLifecycleState::PostFlightReview], true)) {
            $reasons[] = 'Mission must be completed first.';
        }

        if ($mission->uas_pilot_id === null) {
            $reasons[] = 'Mission pilot is missing.';
        }

        if ($mission->uas_aircraft_id === null) {
            $reasons[] = 'Mission aircraft is missing.';
        }

        if ($latestChecklist === null) {
            $reasons[] = 'Post-flight checklist has not been recorded.';
        } elseif ($latestChecklist->state === 'blocked') {
            $reasons[] = 'Post-flight checklist is blocked.';
        }

        return $reasons;
    }
}
