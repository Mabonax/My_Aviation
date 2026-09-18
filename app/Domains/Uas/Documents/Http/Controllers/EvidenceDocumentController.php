<?php

namespace App\Domains\Uas\Documents\Http\Controllers;

use App\Domains\Uas\Documents\Application\Actions\StoreEvidenceDocument;
use App\Domains\Uas\Documents\Application\Queries\EvidenceDocumentOptions;
use App\Domains\Uas\Documents\Application\Queries\ListEvidenceDocuments;
use App\Domains\Uas\Documents\Application\Support\EvidenceTargetResolver;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Documents\Http\Requests\StoreEvidenceDocumentRequest;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EvidenceDocumentController extends Controller
{
    public function index(ListEvidenceDocuments $documents, EvidenceDocumentOptions $options): Response
    {
        Gate::authorize('viewAny', EvidenceDocument::class);

        return Inertia::render('documents/index', [
            'documents' => $documents->execute(request()->user()),
            'options' => $options->execute(request()->user()),
        ]);
    }

    public function store(StoreEvidenceDocumentRequest $request, StoreEvidenceDocument $storeDocument, EvidenceTargetResolver $targets): RedirectResponse
    {
        $target = $targets->resolve($request->validated('evidenceable_type'), $request->integer('evidenceable_id') ?: null)
            ?? ($request->integer('uas_operator_id') ? UasOperator::query()->find($request->integer('uas_operator_id')) : null);

        Gate::authorize('create', [EvidenceDocument::class, $target]);

        $storeDocument->execute($request->file('file'), $request->validated(), $request->user(), $target, $request->ip(), $request->userAgent());

        return back()->with('success', 'Evidence document uploaded.');
    }
}
