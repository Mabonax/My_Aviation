<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;

class ListGisProjects
{
    public function __construct(private readonly CurrentOperatorContext $operatorContext) {}

    public function execute(): array
    {
        $user = request()->user();
        $query = UasGisProject::query();

        if ($user && ! $this->operatorContext->hasGlobalOperatorAccess($user)) {
            $operator = $this->operatorContext->requireFromRequest(request());
            $query->where(function ($scope) use ($operator) {
                $scope->where('uas_operator_id', $operator->id)
                    ->orWhere(function ($legacy) use ($operator) {
                        $legacy->whereNull('uas_operator_id')
                            ->whereHas('projectMissions.mission', fn ($mission) => $mission->where('uas_operator_id', $operator->id));
                    });
            });
        }

        return $query
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (UasGisProject $project): array => GisProjectPresenter::summary($project))
            ->all();
    }
}
