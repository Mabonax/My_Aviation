<?php

namespace App\Domains\Uas\Regulations\Http\Controllers;

use App\Domains\Uas\Regulations\Application\Actions\CreateRegulatoryRequirement;
use App\Domains\Uas\Regulations\Application\Actions\SupersedeRegulatoryRequirement;
use App\Domains\Uas\Regulations\Application\Queries\ListRegulatoryRequirements;
use App\Domains\Uas\Regulations\Application\Queries\RegulatoryRequirementPresenter;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Regulations\Http\Requests\StoreRegulatoryRequirementRequest;
use App\Domains\Uas\Regulations\Http\Requests\SupersedeRegulatoryRequirementRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegulatoryRequirementController extends Controller
{
    public function index(ListRegulatoryRequirements $requirements): Response
    {
        Gate::authorize('viewAny', RegulatoryRequirement::class);

        return Inertia::render('regulations/requirements/index', ['requirements' => $requirements->execute()]);
    }

    public function create(): Response
    {
        Gate::authorize('create', RegulatoryRequirement::class);

        return Inertia::render('regulations/requirements/create');
    }

    public function store(StoreRegulatoryRequirementRequest $request, CreateRegulatoryRequirement $createRequirement): RedirectResponse
    {
        $requirement = $createRequirement->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-requirements.show', $requirement)->with('success', 'Regulatory requirement created.');
    }

    public function show(RegulatoryRequirement $regulatoryRequirement): Response
    {
        Gate::authorize('view', $regulatoryRequirement);

        return Inertia::render('regulations/requirements/show', ['requirement' => RegulatoryRequirementPresenter::toArray($regulatoryRequirement)]);
    }

    public function supersede(RegulatoryRequirement $regulatoryRequirement): Response
    {
        Gate::authorize('update', $regulatoryRequirement);

        return Inertia::render('regulations/requirements/supersede', ['requirement' => RegulatoryRequirementPresenter::toArray($regulatoryRequirement)]);
    }

    public function storeSupersedingVersion(SupersedeRegulatoryRequirementRequest $request, RegulatoryRequirement $regulatoryRequirement, SupersedeRegulatoryRequirement $supersedeRequirement): RedirectResponse
    {
        $next = $supersedeRequirement->execute($regulatoryRequirement, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-requirements.show', $next)->with('success', 'Regulatory requirement version created.');
    }
}
