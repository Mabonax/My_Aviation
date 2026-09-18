<?php

namespace App\Domains\Uas\Documents\Application\Queries;

use App\Domains\Uas\Documents\Domain\Models\EvidenceLink;
use Illuminate\Database\Eloquent\Model;

class EvidenceSummary
{
    public function for(Model $model): array
    {
        if (! method_exists($model, 'evidenceLinks')) {
            return ['count' => 0, 'documents' => []];
        }

        $model->loadMissing('evidenceLinks.document');
        $links = $model->evidenceLinks
            ->filter(fn (EvidenceLink $link): bool => $link->document !== null)
            ->values();

        return [
            'count' => $links->count(),
            'documents' => $links
                ->map(fn (EvidenceLink $link): array => array_merge(
                    EvidenceDocumentPresenter::summaryFor($link->document),
                    [
                        'evidence_role' => $link->evidence_role,
                        'requirement_id' => $link->requirement_id,
                        'notes' => $link->notes,
                        'attached_at' => $link->attached_at?->toISOString(),
                    ],
                ))
                ->values()
                ->all(),
        ];
    }
}
