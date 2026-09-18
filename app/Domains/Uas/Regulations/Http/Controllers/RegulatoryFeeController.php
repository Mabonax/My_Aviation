<?php

namespace App\Domains\Uas\Regulations\Http\Controllers;

use App\Domains\Uas\Regulations\Application\Actions\CreateRegulatoryFee;
use App\Domains\Uas\Regulations\Application\Actions\SupersedeRegulatoryFee;
use App\Domains\Uas\Regulations\Application\Queries\ListRegulatoryFees;
use App\Domains\Uas\Regulations\Application\Queries\RegulatoryFeePresenter;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;
use App\Domains\Uas\Regulations\Http\Requests\StoreRegulatoryFeeRequest;
use App\Domains\Uas\Regulations\Http\Requests\SupersedeRegulatoryFeeRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegulatoryFeeController extends Controller
{
    public function index(ListRegulatoryFees $fees): Response
    {
        Gate::authorize('viewAny', RegulatoryFee::class);

        return Inertia::render('regulations/fees/index', ['fees' => $fees->execute()]);
    }

    public function create(): Response
    {
        Gate::authorize('create', RegulatoryFee::class);

        return Inertia::render('regulations/fees/create');
    }

    public function store(StoreRegulatoryFeeRequest $request, CreateRegulatoryFee $createFee): RedirectResponse
    {
        $fee = $createFee->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-fees.show', $fee)->with('success', 'Regulatory fee created.');
    }

    public function show(RegulatoryFee $regulatoryFee): Response
    {
        Gate::authorize('view', $regulatoryFee);

        return Inertia::render('regulations/fees/show', ['fee' => RegulatoryFeePresenter::toArray($regulatoryFee)]);
    }

    public function supersede(RegulatoryFee $regulatoryFee): Response
    {
        Gate::authorize('update', $regulatoryFee);

        return Inertia::render('regulations/fees/supersede', ['fee' => RegulatoryFeePresenter::toArray($regulatoryFee)]);
    }

    public function storeSupersedingVersion(SupersedeRegulatoryFeeRequest $request, RegulatoryFee $regulatoryFee, SupersedeRegulatoryFee $supersedeFee): RedirectResponse
    {
        $next = $supersedeFee->execute($regulatoryFee, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-fees.show', $next)->with('success', 'Regulatory fee version created.');
    }
}
