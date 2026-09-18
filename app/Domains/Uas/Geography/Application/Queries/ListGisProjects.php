<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;

class ListGisProjects
{
    public function execute(): array
    {
        return UasGisProject::query()
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (UasGisProject $project): array => GisProjectPresenter::summary($project))
            ->all();
    }
}
