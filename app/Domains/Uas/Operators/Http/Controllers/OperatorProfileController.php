<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Operators\Application\Actions\CreateOperatorProfile;
use App\Domains\Uas\Operators\Application\Actions\UpdateOperatorProfile;
use App\Domains\Uas\Operators\Application\Queries\ListOperators;
use App\Domains\Uas\Operators\Application\Queries\OperatorCertificateCaseReport;
use App\Domains\Uas\Operators\Application\Queries\OperatorManualRevisionReport;
use App\Domains\Uas\Operators\Application\Queries\OperatorMembershipOptions;
use App\Domains\Uas\Operators\Application\Queries\OperatorMembershipReport;
use App\Domains\Uas\Operators\Application\Queries\OperatorOptions;
use App\Domains\Uas\Operators\Application\Queries\OperatorPresenter;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Http\Requests\StoreOperatorProfileRequest;
use App\Domains\Uas\Operators\Http\Requests\UpdateOperatorProfileRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperatorProfileController extends Controller
{
    public function index(ListOperators $operators): Response
    {
        Gate::authorize('viewAny', UasOperator::class);

        return Inertia::render('operators/index', ['operators' => $operators->execute(request()->user())]);
    }

    public function create(OperatorOptions $options): Response
    {
        Gate::authorize('create', UasOperator::class);

        return Inertia::render('operators/create', ['options' => $options->execute()]);
    }

    public function store(StoreOperatorProfileRequest $request, CreateOperatorProfile $createOperator): RedirectResponse
    {
        $operator = $createOperator->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operators.show', $operator)->with('success', 'Operator profile created.');
    }

    public function show(UasOperator $operator, OperatorCertificateCaseReport $caseReport, OperatorManualRevisionReport $manualReport, OperatorMembershipReport $membershipReport, OperatorMembershipOptions $membershipOptions): Response
    {
        Gate::authorize('view', $operator);

        return Inertia::render('operators/show', [
            'operator' => OperatorPresenter::toArray($operator),
            'certificateCases' => $caseReport->execute($operator),
            'manualRevisions' => $manualReport->execute($operator),
            'membershipReport' => $membershipReport->execute($operator),
            'membershipOptions' => $membershipOptions->execute(),
        ]);
    }

    public function edit(UasOperator $operator, OperatorOptions $options): Response
    {
        Gate::authorize('update', $operator);

        return Inertia::render('operators/edit', [
            'operator' => OperatorPresenter::toArray($operator),
            'options' => $options->execute(),
        ]);
    }

    public function update(UpdateOperatorProfileRequest $request, UasOperator $operator, UpdateOperatorProfile $updateOperator): RedirectResponse
    {
        $operator = $updateOperator->execute($operator, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('operators.show', $operator)->with('success', 'Operator profile updated.');
    }
}



