<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorAircraft;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorPilot;

class OperatorMembershipReport
{
    public function execute(UasOperator $operator): array
    {
        $operator->loadMissing([
            'memberships.user',
            'pilots',
            'aircraft',
            'missions' => fn ($query) => $query->latest('planned_start_at')->limit(10),
        ]);

        return [
            'summary' => [
                'members_total' => $operator->memberships->count(),
                'members_active' => $operator->memberships->where('status', UasOperatorMembership::STATUS_ACTIVE)->count(),
                'pilots_active' => $operator->pilots->where('pivot.status', UasOperatorPilot::STATUS_ACTIVE)->count(),
                'aircraft_active' => $operator->aircraft->where('pivot.status', UasOperatorAircraft::STATUS_ACTIVE)->count(),
                'missions_total' => $operator->missions()->count(),
            ],
            'memberships' => $operator->memberships
                ->sortBy([['status', 'asc'], ['membership_role', 'asc']])
                ->map(fn (UasOperatorMembership $membership): array => [
                    'id' => $membership->id,
                    'user' => $membership->user ? [
                        'id' => $membership->user->id,
                        'name' => $membership->user->name,
                        'email' => $membership->user->email,
                    ] : null,
                    'membership_role' => $membership->membership_role,
                    'status' => $membership->status,
                    'joined_at' => $membership->joined_at?->toDateString(),
                    'activated_at' => $membership->activated_at?->toDateString(),
                    'left_at' => $membership->left_at?->toDateString(),
                ])
                ->values()
                ->all(),
            'pilots' => $operator->pilots
                ->map(fn ($pilot): array => [
                    'id' => $pilot->id,
                    'label' => $pilot->display_name,
                    'assignment_role' => $pilot->pivot->assignment_role,
                    'status' => $pilot->pivot->status,
                ])
                ->values()
                ->all(),
            'aircraft' => $operator->aircraft
                ->map(fn ($aircraft): array => [
                    'id' => $aircraft->id,
                    'label' => trim("{$aircraft->registration} {$aircraft->model}"),
                    'assignment_role' => $aircraft->pivot->assignment_role,
                    'status' => $aircraft->pivot->status,
                ])
                ->values()
                ->all(),
            'missions' => $operator->missions
                ->map(fn ($mission): array => [
                    'id' => $mission->id,
                    'mission_number' => $mission->mission_number,
                    'purpose' => $mission->purpose,
                    'lifecycle_state' => $mission->lifecycle_state->value,
                ])
                ->values()
                ->all(),
        ];
    }
}
