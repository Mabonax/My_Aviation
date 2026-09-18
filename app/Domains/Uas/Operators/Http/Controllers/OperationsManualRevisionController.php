<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Actions\CreateManualRevision;
use App\Domains\Uas\Operators\Application\Actions\UpdateManualRevision;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionDistributionReport;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionOptions;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionPresenter;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionTrainingReport;
use App\Domains\Uas\Operators\Application\Queries\OperatorPresenter;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Operators\Http\Requests\StoreManualRevisionRequest;
use App\Domains\Uas\Operators\Http\Requests\UpdateManualRevisionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperationsManualRevisionController extends Controller
{
    public function create(UasOperator $operator, ManualRevisionOptions $options): Response
    {
        Gate::authorize('update', $operator);

        return Inertia::render('operators/manual-revisions/create', [
            'operator' => OperatorPresenter::toArray($operator),
            'options' => $options->execute($operator),
        ]);
    }

    public function store(StoreManualRevisionRequest $request, UasOperator $operator, CreateManualRevision $createRevision): RedirectResponse
    {
        $revision = $createRevision->execute($operator, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operations-manual-revisions.show', $revision)->with('success', 'Operations Manual revision created.');
    }

    public function show(UasOperationsManualRevision $manualRevision, ManualRevisionDistributionReport $distributionReport, ManualRevisionTrainingReport $trainingReport): Response
    {
        Gate::authorize('view', $manualRevision->operator);

        return Inertia::render('operators/manual-revisions/show', [
            'manualRevision' => ManualRevisionPresenter::toArray($manualRevision),
            'distributionReport' => $distributionReport->execute($manualRevision),
            'trainingReport' => $trainingReport->execute($manualRevision),
        ]);
    }

    public function edit(UasOperationsManualRevision $manualRevision, ManualRevisionOptions $options): Response
    {
        Gate::authorize('update', $manualRevision->operator);

        return Inertia::render('operators/manual-revisions/edit', [
            'manualRevision' => ManualRevisionPresenter::toArray($manualRevision),
            'options' => $options->execute($manualRevision->operator),
        ]);
    }

    public function update(UpdateManualRevisionRequest $request, UasOperationsManualRevision $manualRevision, UpdateManualRevision $updateRevision): RedirectResponse
    {
        $revision = $updateRevision->execute($manualRevision, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operations-manual-revisions.show', $revision)->with('success', 'Operations Manual revision updated.');
    }
}


