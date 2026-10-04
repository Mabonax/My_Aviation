<?php

namespace App\Domains\Uas\Productisation\Application\Queries;

use App\Domains\Uas\Aircraft\Application\Queries\AircraftReadinessSummary;
use App\Domains\Uas\Missions\Application\Queries\MissionJourneySummary;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;

class OperatorDashboardOverview
{
    public function __construct(
        private readonly AircraftReadinessSummary $aircraftReadiness,
        private readonly MissionJourneySummary $missionJourney,
    ) {}

    public function execute(?UasOperator $operator): array
    {
        if (! $operator) {
            return [
                'pipeline' => [],
                'fleet' => ['total' => 0, 'ready' => 0, 'review' => 0, 'grounded' => 0],
                'missions' => [],
                'certificate' => null,
            ];
        }

        $missions = $operator->missions()
            ->with(['pilot', 'aircraft'])
            ->latest('planned_start_at')
            ->limit(25)
            ->get();

        $pipeline = collect([
            MissionLifecycleState::Draft,
            MissionLifecycleState::Planning,
            MissionLifecycleState::ComplianceReview,
            MissionLifecycleState::AwaitingApproval,
            MissionLifecycleState::ReadyForFlight,
            MissionLifecycleState::InProgress,
            MissionLifecycleState::PostFlightReview,
        ])->map(fn (MissionLifecycleState $state): array => [
            'key' => $state->value,
            'label' => match ($state) {
                MissionLifecycleState::Draft => 'Draft',
                MissionLifecycleState::Planning => 'Planning',
                MissionLifecycleState::ComplianceReview => 'Compliance review',
                MissionLifecycleState::AwaitingApproval => 'Awaiting approval',
                MissionLifecycleState::ReadyForFlight => 'Ready for flight',
                MissionLifecycleState::InProgress => 'In flight',
                MissionLifecycleState::PostFlightReview => 'Post-flight',
                default => ucfirst(str_replace('_', ' ', $state->value)),
            },
            'count' => $missions->where('lifecycle_state', $state)->count(),
        ])->values()->all();

        $aircraft = $operator->aircraft()
            ->wherePivot('status', 'active')
            ->get();

        $fleet = ['total' => $aircraft->count(), 'ready' => 0, 'review' => 0, 'grounded' => 0];

        foreach ($aircraft as $item) {
            $status = $this->aircraftReadiness->execute($item)['status'] ?? 'amber';

            match ($status) {
                'green' => $fleet['ready']++,
                'red' => $fleet['grounded']++,
                default => $fleet['review']++,
            };
        }

        $recent = $missions
            ->take(5)
            ->map(function ($mission): array {
                $journey = $this->missionJourney->execute($mission);
                $next = $journey['next_action'] ?? null;

                return [
                    'id' => $mission->id,
                    'mission_number' => $mission->mission_number,
                    'operation_name' => $mission->purpose ?: $mission->client_project ?: 'Mission',
                    'pilot' => $mission->pilot?->display_name,
                    'aircraft' => $mission->aircraft?->registration ?: $mission->aircraft?->model,
                    'readiness' => $journey['readiness']['status'] ?? 'amber',
                    'readiness_label' => $journey['readiness']['label'] ?? 'Review',
                    'next_action' => $next['label'] ?? 'Review mission',
                    'planned_start_at' => $mission->planned_start_at?->toISOString(),
                    'lifecycle_state' => $mission->lifecycle_state?->value,
                ];
            })
            ->values()
            ->all();

        return [
            'pipeline' => $pipeline,
            'fleet' => $fleet,
            'missions' => $recent,
            'certificate' => [
                'status' => $operator->status,
                'uasoc_number' => $operator->uasoc_number,
                'expiry_date' => $operator->certificate_expiry_date?->toDateString(),
                'days_remaining' => $operator->certificate_expiry_date
                    ? now()->startOfDay()->diffInDays($operator->certificate_expiry_date, false)
                    : null,
            ],
        ];
    }
}
