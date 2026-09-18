<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Documents\Application\Actions\StoreEvidenceDocument;
use App\Domains\Uas\Documents\Application\Queries\EvidenceDocumentPresenter;
use App\Domains\Uas\Documents\Application\Queries\ListEvidenceDocuments;
use App\Domains\Uas\Documents\Application\Support\EvidenceTargetResolver;
use App\Domains\Uas\Documents\Application\Support\EvidenceOperatorResolver;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Documents\Http\Requests\StoreEvidenceDocumentRequest;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EvidenceDocumentController extends Controller
{
    public function index(Request $request, ListEvidenceDocuments $documents, CurrentOperatorContext $context): JsonResponse
    {
        Gate::authorize('viewAny', EvidenceDocument::class);
        $operator = $context->requireFromRequest($request);

        return ApiResponse::success([
            'evidence_documents' => $documents->execute($request->user(), $operator->id),
        ]);
    }

    public function store(StoreEvidenceDocumentRequest $request, StoreEvidenceDocument $storeDocument, EvidenceTargetResolver $targets, EvidenceOperatorResolver $operatorResolver, CurrentOperatorContext $context): JsonResponse
    {
        $operator = $context->requireFromRequest($request);
        if ($request->integer('uas_operator_id') && $request->integer('uas_operator_id') !== $operator->id) {
            abort(403, 'Evidence must be stored inside the active operator context.');
        }

        $target = $targets->resolve($request->validated('evidenceable_type'), $request->integer('evidenceable_id') ?: null)
            ?? UasOperator::query()->find($operator->id);

        if ($target && $operatorResolver->operatorIdFor($target, $operator->id) !== $operator->id) {
            abort(403, 'The evidence target is outside the active operator context.');
        }

        Gate::authorize('create', [EvidenceDocument::class, $target]);

        $document = $storeDocument->execute($request->file('file'), $request->validated(), $request->user(), $target, $request->ip(), $request->userAgent());

        return ApiResponse::success([
            'evidence_document' => EvidenceDocumentPresenter::toArray($document),
        ], 'Evidence document uploaded.', 201);
    }
}
