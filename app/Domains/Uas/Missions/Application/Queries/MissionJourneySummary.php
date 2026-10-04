<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionJourneySummary
{
    public function execute(
        UasMission $mission,
        ?array $compliance = null,
        ?array $postFlight = null,
    ): array {
        $compliance ??= app(MissionComplianceSummary::class)->execute($mission);
        $postFlight ??= app(PostFlightPropagationSummary::class)->execute($mission);

        $controls = collect($compliance['controls'] ?? [])->keyBy('key');
        $lifecycle = $mission->lifecycle_state->value;

        $planningComplete = filled($mission->purpose)
            && filled($mission->location)
            && $mission->planned_start_at !== null
            && $mission->planned_end_at !== null;

        $crewStatus = $this->worstStatus([
            data_get($controls, 'pilot_readiness.status'),
            data_get($controls, 'pilot_operator_approval.status'),
        ]);

        $airspaceStatus = $this->worstStatus([
            data_get($controls, 'geometry_airspace.status'),
            data_get($controls, 'aeronautical_information.status'),
            data_get($controls, 'mission_approvals.status'),
        ]);

        $flightStarted = in_array($lifecycle, ['in_progress', 'completed', 'post_flight_review', 'closed'], true)
            || $mission->actual_takeoff_at !== null;
        $flightComplete = in_array($lifecycle, ['completed', 'post_flight_review', 'closed'], true)
            || $mission->actual_landing_at !== null;

        $stages = [
            $this->stage('planning', 'Planning', $planningComplete ? 'green' : 'amber', $planningComplete
                ? 'Mission purpose, location and schedule are captured.'
                : 'Mission planning information still requires completion.', ! $planningComplete, route('missions.show', $mission, false)),
            $this->stage('crew', 'Crew', $crewStatus, $this->controlSummary($controls, ['pilot_readiness', 'pilot_operator_approval'], 'Pilot and operator assignment readiness.'), $crewStatus === 'red', route('missions.crew.create', $mission, false)),
            $this->stage('aircraft', 'Aircraft', data_get($controls, 'aircraft_readiness.status', 'amber'), data_get($controls, 'aircraft_readiness.summary', 'Aircraft assignment and serviceability require review.'), data_get($controls, 'aircraft_readiness.blocking', true), $mission->aircraft ? route('aircraft.show', $mission->aircraft, false) : null),
            $this->stage('airspace', 'Airspace', $airspaceStatus, $this->controlSummary($controls, ['geometry_airspace', 'aeronautical_information', 'mission_approvals'], 'Airspace and briefing readiness.'), $airspaceStatus === 'red', route('missions.briefing.show', $mission, false)),
            $this->stage('risk', 'Risk', data_get($controls, 'risk_assessment.status', 'amber'), data_get($controls, 'risk_assessment.summary', 'Risk assessment requires review.'), data_get($controls, 'risk_assessment.blocking', false), route('missions.show', $mission, false).'#risk'),
            $this->stage('compliance', 'Compliance', $compliance['status'], $compliance['label'], $compliance['status'] === 'red', route('missions.show', $mission, false).'#compliance'),
            $this->stage(
                'release',
                'Release',
                in_array($lifecycle, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed'], true) ? 'green' : ($compliance['status'] === 'red' ? 'red' : 'amber'),
                in_array($lifecycle, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed'], true)
                    ? 'Mission has been released for flight.'
                    : ($compliance['status'] === 'red' ? 'Release is blocked by readiness controls.' : 'Mission is eligible for release when lifecycle approval is complete.'),
                $compliance['status'] === 'red',
                route('missions.show', $mission, false).'#release',
            ),
            $this->stage(
                'flight',
                'Flight',
                $flightComplete ? 'green' : ($flightStarted ? 'amber' : 'pending'),
                $flightComplete ? 'Flight execution is complete.' : ($flightStarted ? 'Flight is in progress.' : 'Flight has not started.'),
                false,
                route('missions.show', $mission, false).'#flight',
            ),
            $this->stage(
                'post_flight',
                'Post-flight',
                ($postFlight['state'] ?? null) === 'propagated' ? 'green' : (($postFlight['state'] ?? null) === 'propagated_with_follow_up' ? 'amber' : (($postFlight['state'] ?? null) === 'blocked' ? 'red' : 'pending')),
                $postFlight['label'] ?? 'Post-flight close-out is pending.',
                ($postFlight['state'] ?? null) === 'blocked',
                route('missions.show', $mission, false).'#post-flight',
            ),
        ];

        $firstAction = collect($stages)
            ->first(fn (array $stage): bool => in_array($stage['status'], ['red', 'amber'], true));

        return [
            'current_stage' => $this->currentStage($lifecycle, $postFlight['state'] ?? null),
            'lifecycle_state' => $lifecycle,
            'readiness' => [
                'status' => $compliance['status'],
                'label' => $compliance['label'],
                'blocking_count' => $compliance['blocking_count'],
                'warning_count' => $compliance['warning_count'],
            ],
            'next_action' => $firstAction ? [
                'stage' => $firstAction['key'],
                'label' => $firstAction['label'],
                'summary' => $firstAction['summary'],
                'action_href' => $firstAction['action_href'],
            ] : null,
            'stages' => $stages,
        ];
    }

    private function stage(string $key, string $label, string $status, string $summary, bool $blocking, ?string $actionHref): array
    {
        return compact('key', 'label', 'status', 'summary', 'blocking') + ['action_href' => $actionHref];
    }

    private function worstStatus(array $statuses): string
    {
        $statuses = array_values(array_filter($statuses));
        if (in_array('red', $statuses, true)) return 'red';
        if (in_array('amber', $statuses, true)) return 'amber';
        if (in_array('green', $statuses, true) && count(array_unique($statuses)) === 1) return 'green';
        return $statuses === [] ? 'amber' : 'amber';
    }

    private function controlSummary($controls, array $keys, string $fallback): string
    {
        $messages = collect($keys)
            ->map(fn (string $key) => data_get($controls, "{$key}.summary"))
            ->filter()
            ->values()
            ->all();

        return $messages === [] ? $fallback : implode(' ', $messages);
    }

    private function currentStage(string $lifecycle, ?string $postFlightState): string
    {
        if (in_array($postFlightState, ['propagated', 'propagated_with_follow_up'], true) || $lifecycle === 'closed') return 'post_flight';
        if (in_array($lifecycle, ['completed', 'post_flight_review'], true)) return 'post_flight';
        if ($lifecycle === 'in_progress') return 'flight';
        if ($lifecycle === 'ready_for_flight') return 'release';
        if (in_array($lifecycle, ['approved', 'awaiting_approval', 'compliance_review'], true)) return 'compliance';
        return 'planning';
    }
}
