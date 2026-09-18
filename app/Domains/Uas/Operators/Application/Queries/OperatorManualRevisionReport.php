<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;

class OperatorManualRevisionReport
{
    public function execute(UasOperator $operator): array
    {
        $revisions = $operator->manualRevisions()->with('supersededRevision')->orderByDesc('effective_date')->orderByDesc('id')->get()->map(fn ($revision): array => [
            'id' => $revision->id,
            'manual_name' => $revision->manual_name,
            'revision_code' => $revision->revision_code,
            'effective_date' => $revision->effective_date?->toDateString(),
            'approval_status' => $revision->approval_status,
            'authority_approval_reference' => $revision->authority_approval_reference,
            'sections_count' => count($revision->sections ?? []),
            'superseded_revision' => $revision->supersededRevision ? $revision->supersededRevision->manual_name.' '.$revision->supersededRevision->revision_code : null,
        ])->values()->all();

        return [
            'summary' => [
                'total' => count($revisions),
                'approved' => collect($revisions)->where('approval_status', 'approved')->count(),
                'draft' => collect($revisions)->where('approval_status', 'draft')->count(),
                'superseded' => collect($revisions)->where('approval_status', 'superseded')->count(),
            ],
            'revisions' => $revisions,
        ];
    }
}