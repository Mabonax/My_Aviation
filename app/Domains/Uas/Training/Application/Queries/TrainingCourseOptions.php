<?php

namespace App\Domains\Uas\Training\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Training\Domain\Services\TrainingClassification;

class TrainingCourseOptions
{
    public function execute(): array
    {
        return [
            'classifications' => TrainingClassification::CLASSIFICATIONS,
            'statuses' => TrainingClassification::STATUSES,
            'regulatory_requirements' => RegulatoryRequirement::query()
                ->where('status', 'active')
                ->orderBy('requirement_id')
                ->get(['id', 'requirement_id', 'title', 'regulation_part', 'clause_reference'])
                ->map(fn (RegulatoryRequirement $requirement): array => [
                    'id' => $requirement->id,
                    'requirement_id' => $requirement->requirement_id,
                    'title' => $requirement->title,
                    'regulation_part' => $requirement->regulation_part,
                    'clause_reference' => $requirement->clause_reference,
                ])
                ->all(),
        ];
    }
}
