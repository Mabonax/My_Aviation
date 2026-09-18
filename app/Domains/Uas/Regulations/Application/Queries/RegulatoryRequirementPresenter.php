<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;

class RegulatoryRequirementPresenter
{
    public static function toArray(RegulatoryRequirement $requirement): array
    {
        $requirement->loadMissing(['previousRequirement', 'supersedingRequirements', 'trainingComplianceLinks.course']);

        return [
            'id' => $requirement->id,
            'previous_requirement_id' => $requirement->previous_requirement_id,
            'previous_requirement' => $requirement->previousRequirement ? self::summary($requirement->previousRequirement) : null,
            'superseding_requirements' => $requirement->supersedingRequirements->map(fn (RegulatoryRequirement $version): array => self::summary($version))->values()->all(),
            'requirement_id' => $requirement->requirement_id,
            'regulation_part' => $requirement->regulation_part,
            'clause_reference' => $requirement->clause_reference,
            'title' => $requirement->title,
            'requirement_text' => $requirement->requirement_text,
            'responsible_party' => $requirement->responsible_party,
            'applicability' => $requirement->applicability,
            'system_control' => $requirement->system_control,
            'evidence_required' => $requirement->evidence_required,
            'frequency' => $requirement->frequency,
            'validity_period' => $requirement->validity_period,
            'retention_period' => $requirement->retention_period,
            'effective_date' => $requirement->effective_date?->toDateString(),
            'superseded_date' => $requirement->superseded_date?->toDateString(),
            'official_source' => $requirement->official_source,
            'source_version' => $requirement->source_version,
            'status' => $requirement->status,
            'training_links' => $requirement->trainingComplianceLinks->map(fn ($link): array => [
                'id' => $link->id,
                'course' => $link->course?->code.' / '.$link->course?->title,
                'requirement_reference' => $link->requirement_reference,
                'link_status' => $link->link_status,
            ])->values()->all(),
        ];
    }

    private static function summary(RegulatoryRequirement $requirement): array
    {
        return [
            'id' => $requirement->id,
            'requirement_id' => $requirement->requirement_id,
            'title' => $requirement->title,
            'source_version' => $requirement->source_version,
            'status' => $requirement->status,
            'effective_date' => $requirement->effective_date?->toDateString(),
        ];
    }
}
