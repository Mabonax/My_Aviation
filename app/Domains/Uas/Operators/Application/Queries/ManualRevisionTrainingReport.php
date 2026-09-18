<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;

class ManualRevisionTrainingReport
{
    public function execute(UasOperationsManualRevision $manualRevision): array
    {
        $requirements = $manualRevision->trainingRequirements()->orderBy('due_date')->orderBy('title')->get()->map(fn ($requirement): array => [
            'id' => $requirement->id,
            'title' => $requirement->title,
            'requirement_type' => $requirement->requirement_type,
            'training_status' => $requirement->training_status,
            'affected_roles' => $requirement->affected_roles ?? [],
            'due_date' => $requirement->due_date?->toDateString(),
            'competency_standard' => $requirement->competency_standard,
            'trigger_reason' => $requirement->trigger_reason,
            'evidence_references' => $requirement->evidence_references ?? [],
            'notes' => $requirement->notes,
        ])->values()->all();

        return [
            'summary' => [
                'total' => count($requirements),
                'required' => collect($requirements)->where('training_status', 'required')->count(),
                'assigned' => collect($requirements)->where('training_status', 'assigned')->count(),
                'completed' => collect($requirements)->where('training_status', 'completed')->count(),
                'waived' => collect($requirements)->where('training_status', 'waived')->count(),
            ],
            'requirements' => $requirements,
        ];
    }
}
