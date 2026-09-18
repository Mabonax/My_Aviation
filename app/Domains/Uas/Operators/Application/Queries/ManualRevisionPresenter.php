<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;

class ManualRevisionPresenter
{
    public static function toArray(UasOperationsManualRevision $revision): array
    {
        $revision->loadMissing(['operator', 'supersededRevision']);

        return [
            'id' => $revision->id,
            'operator' => ['id' => $revision->operator->id, 'legal_entity' => $revision->operator->legal_entity],
            'manual_name' => $revision->manual_name,
            'revision_code' => $revision->revision_code,
            'effective_date' => $revision->effective_date?->toDateString(),
            'approval_status' => $revision->approval_status,
            'authority_approval_reference' => $revision->authority_approval_reference,
            'sections' => $revision->sections ?? [],
            'change_summary' => $revision->change_summary,
            'evidence_references' => $revision->evidence_references ?? [],
            'superseded_revision_id' => $revision->superseded_revision_id,
            'superseded_revision' => $revision->supersededRevision ? ['id' => $revision->supersededRevision->id, 'label' => $revision->supersededRevision->manual_name.' '.$revision->supersededRevision->revision_code] : null,
            'regulatory_source' => $revision->regulatory_source,
            'regulatory_source_version' => $revision->regulatory_source_version,
            'regulatory_effective_date' => $revision->regulatory_effective_date?->toDateString(),
        ];
    }
}