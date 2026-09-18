<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Services\OperationsManualControl;

class ManualRevisionOptions
{
    public function execute(UasOperator $operator): array
    {
        return [
            'approval_statuses' => OperationsManualControl::APPROVAL_STATUSES,
            'superseded_revisions' => $operator->manualRevisions()->orderByDesc('effective_date')->get()->map(fn ($revision): array => [
                'id' => $revision->id,
                'label' => "{$revision->manual_name} {$revision->revision_code}",
            ])->values()->all(),
        ];
    }
}