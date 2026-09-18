<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Models\User;

class ListMissions
{
    public function execute(?User $user = null, ?int $operatorId = null): array
    {
        $query = UasMission::query();

        if ($operatorId !== null) {
            $query->where('uas_operator_id', $operatorId);
        } elseif ($user !== null) {
            $operatorContext = app(CurrentOperatorContext::class);
            if (! $operatorContext->hasGlobalOperatorAccess($user)) {
                $query->whereIn('uas_operator_id', $operatorContext->accessibleOperatorIds($user));
            }
        }

        return $query->with(['operator', 'pilot', 'aircraft'])
            ->latest('planned_start_at')->latest()->limit(50)->get()
            ->map(fn (UasMission $mission): array => MissionPresenter::toArray($mission, includeComplianceDetails: false))->all();
    }
}
