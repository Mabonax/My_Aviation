<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Missions\Domain\Models\UasMission;

class ListMissions
{
    public function execute(): array
    {
        return UasMission::query()
            ->with(['pilot', 'aircraft'])
            ->latest('planned_start_at')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (UasMission $mission): array => MissionPresenter::toArray($mission))
            ->all();
    }
}
