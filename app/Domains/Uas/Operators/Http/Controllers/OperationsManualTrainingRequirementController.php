<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Actions\CreateManualTrainingRequirement;
use App\Domains\Uas\Operators\Application\Queries\ManualRevisionPresenter;
use App\Domains\Uas\Operators\Application\Queries\ManualTrainingOptions;
use App\Domains\Uas\Operators\Domain\Models\UasOperationsManualRevision;
use App\Domains\Uas\Operators\Http\Requests\StoreManualTrainingRequirementRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperationsManualTrainingRequirementController extends Controller
{
    public function create(UasOperationsManualRevision $manualRevision, ManualTrainingOptions $options): Response
    {
        Gate::authorize('update', $manualRevision->operator);

        return Inertia::render('operators/manual-revisions/training-requirements/create', [
            'manualRevision' => ManualRevisionPresenter::toArray($manualRevision),
            'options' => $options->execute(),
        ]);
    }

    public function store(StoreManualTrainingRequirementRequest $request, UasOperationsManualRevision $manualRevision, CreateManualTrainingRequirement $createTrainingRequirement): RedirectResponse
    {
        $createTrainingRequirement->execute($manualRevision, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operations-manual-revisions.show', $manualRevision)->with('success', 'Manual amendment training requirement recorded.');
    }
}
