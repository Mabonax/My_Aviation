<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;

class ListRegulatoryRequirements
{
    public function execute(): array
    {
        return RegulatoryRequirement::query()
            ->withCount(['trainingComplianceLinks', 'supersedingRequirements'])
            ->orderBy('regulation_part')
            ->orderBy('requirement_id')
            ->get()
            ->map(fn (RegulatoryRequirement $requirement): array => [
                'id' => $requirement->id,
                'requirement_id' => $requirement->requirement_id,
                'regulation_part' => $requirement->regulation_part,
                'clause_reference' => $requirement->clause_reference,
                'title' => $requirement->title,
                'status' => $requirement->status,
                'source_version' => $requirement->source_version,
                'effective_date' => $requirement->effective_date?->toDateString(),
                'superseded_date' => $requirement->superseded_date?->toDateString(),
                'training_links_count' => $requirement->training_compliance_links_count,
                'superseding_versions_count' => $requirement->superseding_requirements_count,
            ])
            ->all();
    }
}
