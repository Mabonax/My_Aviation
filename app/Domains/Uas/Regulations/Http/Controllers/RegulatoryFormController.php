<?php

namespace App\Domains\Uas\Regulations\Http\Controllers;

use App\Domains\Uas\Regulations\Application\Actions\CreateRegulatoryForm;
use App\Domains\Uas\Regulations\Application\Actions\SupersedeRegulatoryForm;
use App\Domains\Uas\Regulations\Application\Queries\ListRegulatoryForms;
use App\Domains\Uas\Regulations\Application\Queries\RegulatoryFormPresenter;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryForm;
use App\Domains\Uas\Regulations\Http\Requests\StoreRegulatoryFormRequest;
use App\Domains\Uas\Regulations\Http\Requests\SupersedeRegulatoryFormRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegulatoryFormController extends Controller
{
    public function index(ListRegulatoryForms $forms): Response
    {
        Gate::authorize('viewAny', RegulatoryForm::class);

        return Inertia::render('regulations/forms/index', ['forms' => $forms->execute()]);
    }

    public function create(): Response
    {
        Gate::authorize('create', RegulatoryForm::class);

        return Inertia::render('regulations/forms/create');
    }

    public function store(StoreRegulatoryFormRequest $request, CreateRegulatoryForm $createForm): RedirectResponse
    {
        $form = $createForm->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-forms.show', $form)->with('success', 'Regulatory form created.');
    }

    public function show(RegulatoryForm $regulatoryForm): Response
    {
        Gate::authorize('view', $regulatoryForm);

        return Inertia::render('regulations/forms/show', ['form' => RegulatoryFormPresenter::toArray($regulatoryForm)]);
    }

    public function supersede(RegulatoryForm $regulatoryForm): Response
    {
        Gate::authorize('update', $regulatoryForm);

        return Inertia::render('regulations/forms/supersede', ['form' => RegulatoryFormPresenter::toArray($regulatoryForm)]);
    }

    public function storeSupersedingVersion(SupersedeRegulatoryFormRequest $request, RegulatoryForm $regulatoryForm, SupersedeRegulatoryForm $supersedeForm): RedirectResponse
    {
        $next = $supersedeForm->execute($regulatoryForm, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-forms.show', $next)->with('success', 'Regulatory form version created.');
    }
}
