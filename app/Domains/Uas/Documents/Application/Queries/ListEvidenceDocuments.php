<?php

namespace App\Domains\Uas\Documents\Application\Queries;

use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Models\User;

class ListEvidenceDocuments
{
    public function execute(User $user): array
    {
        $operatorIds = app(CurrentOperatorContext::class)->accessibleOperatorIds($user);

        return EvidenceDocument::query()
            ->with(['links.evidenceable', 'operator', 'uploader'])
            ->when(! $user->hasAnyUasPermission(['documents.view', 'operators.view']), function ($query) use ($operatorIds) {
                $query->whereIn('uas_operator_id', $operatorIds);
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (EvidenceDocument $document): array => EvidenceDocumentPresenter::toArray($document))
            ->values()
            ->all();
    }
}
