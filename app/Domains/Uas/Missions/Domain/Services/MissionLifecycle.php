<?php

namespace App\Domains\Uas\Missions\Domain\Services;

use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;

class MissionLifecycle
{
    private const TRANSITIONS = [
        'draft' => ['planning', 'cancelled'],
        'planning' => ['compliance_review', 'cancelled'],
        'compliance_review' => ['awaiting_approval', 'planning', 'cancelled'],
        'awaiting_approval' => ['approved', 'planning', 'cancelled'],
        'approved' => ['ready_for_flight', 'planning', 'cancelled'],
        'ready_for_flight' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed'],
        'completed' => ['post_flight_review'],
        'post_flight_review' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function canTransition(MissionLifecycleState|string $from, MissionLifecycleState|string $to): bool
    {
        $fromValue = $from instanceof MissionLifecycleState ? $from->value : $from;
        $toValue = $to instanceof MissionLifecycleState ? $to->value : $to;

        return in_array($toValue, self::TRANSITIONS[$fromValue] ?? [], true);
    }

    public function states(): array
    {
        return collect(MissionLifecycleState::cases())
            ->mapWithKeys(fn (MissionLifecycleState $state): array => [$state->value => str($state->value)->replace('_', ' ')->title()->toString()])
            ->all();
    }
}
