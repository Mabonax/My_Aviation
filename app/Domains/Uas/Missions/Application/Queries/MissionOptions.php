<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Services\MissionLifecycle;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;

class MissionOptions
{
    public function __construct(private readonly MissionLifecycle $lifecycle) {}

    public function execute(?User $user = null): array
    {
        $operatorIds = $user ? app(CurrentOperatorContext::class)->accessibleOperatorIds($user) : [];
        $global = $user === null || $user->hasUasPermission('missions.view');

        return [
            'operators' => UasOperator::query()
                ->when(! $global, fn ($query) => $query->whereIn('id', $operatorIds))
                ->orderBy('legal_entity')
                ->get(['id', 'legal_entity', 'uasoc_number'])
                ->map(fn (UasOperator $operator): array => [
                    'id' => $operator->id,
                    'label' => trim("{$operator->legal_entity} {$operator->uasoc_number}"),
                ])
                ->all(),
            'aircraft' => UasAircraft::query()
                ->when(! $global, fn ($query) => $query->whereHas('operators', fn ($operators) => $operators
                    ->whereIn('uas_operators.id', $operatorIds)
                    ->where('uas_operator_aircraft.status', 'active')))
                ->orderBy('registration')
                ->get(['id', 'registration', 'model'])
                ->map(fn (UasAircraft $aircraft): array => [
                    'id' => $aircraft->id,
                    'label' => trim("{$aircraft->registration} {$aircraft->model}"),
                ])
                ->all(),
            'pilots' => UasPilot::query()
                ->when(! $global, fn ($query) => $query->whereHas('operators', fn ($operators) => $operators
                    ->whereIn('uas_operators.id', $operatorIds)
                    ->where('uas_operator_pilots.status', 'active')
                    ->whereHas('activeMemberships', fn ($memberships) => $memberships->whereColumn('uas_operator_memberships.user_id', 'uas_pilots.user_id'))
                    ->where(fn ($q) => $q->whereNull('uas_operator_pilots.approved_from')->orWhereDate('uas_operator_pilots.approved_from', '<=', today()))
                    ->where(fn ($q) => $q->whereNull('uas_operator_pilots.approved_until')->orWhereDate('uas_operator_pilots.approved_until', '>=', today()))))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (UasPilot $pilot): array => [
                    'id' => $pilot->id,
                    'label' => $pilot->display_name,
                ])
                ->all(),
            'lifecycle_states' => $this->lifecycle->states(),
            'operation_categories' => ['standard' => 'Standard', 'survey' => 'Survey', 'inspection' => 'Inspection', 'emergency' => 'Emergency'],
            'visibility_modes' => ['vlos' => 'VLOS', 'evlos' => 'EVLOS', 'bvlos' => 'BVLOS'],
            'day_night_modes' => ['day' => 'Day', 'night' => 'Night'],
        ];
    }
}
