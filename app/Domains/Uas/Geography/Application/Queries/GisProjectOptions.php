<?php

namespace App\Domains\Uas\Geography\Application\Queries;

use App\Domains\Uas\Geography\Domain\Services\GisProjectLifecycle;

class GisProjectOptions
{
    public function execute(): array
    {
        return [
            'project_types' => GisProjectLifecycle::PROJECT_TYPES,
            'states' => GisProjectLifecycle::STATES,
        ];
    }
}
