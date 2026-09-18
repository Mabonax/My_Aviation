<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Actions\CreateManualDistribution;
use App\Domains\Uas\Operators\Application\Queries\ManualDistributionOptions;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionPresenter;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Operators\Http\Requests\StoreManualDistributionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperationsManualDistributionController extends Controller
{
    public function create(UasOperationsManualRevision $manualRevision, ManualDistributionOptions $options): Response
    {
        Gate::authorize('update', $manualRevision->operator);

        return Inertia::render('operators/manual-revisions/distributions/create', [
            'manualRevision' => ManualRevisionPresenter::toArray($manualRevision),
            'options' => $options->execute(),
        ]);
    }

    public function store(StoreManualDistributionRequest $request, UasOperationsManualRevision $manualRevision, CreateManualDistribution $createDistribution): RedirectResponse
    {
        $createDistribution->execute($manualRevision, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operations-manual-revisions.show', $manualRevision)->with('success', 'Manual distribution recipient recorded.');
    }
}

