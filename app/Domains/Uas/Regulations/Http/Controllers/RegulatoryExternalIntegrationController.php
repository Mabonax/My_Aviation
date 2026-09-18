<?php

namespace App\Domains\Uas\Regulations\Http\Controllers;

use App\Domains\Uas\Regulations\Application\Actions\CreateRegulatoryExternalIntegration;
use App\Domains\Uas\Regulations\Application\Actions\UpdateRegulatoryExternalIntegrationStatus;
use App\Domains\Uas\Regulations\Application\Queries\ExternalIntegrationOptions;
use App\Domains\Uas\Regulations\Application\Queries\ListRegulatoryExternalIntegrations;
use App\Domains\Uas\Regulations\Application\Queries\RegulatoryExternalIntegrationPresenter;
use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;
use App\Domains\Uas\Regulations\Http\Requests\StoreRegulatoryExternalIntegrationRequest;
use App\Domains\Uas\Regulations\Http\Requests\UpdateRegulatoryExternalIntegrationStatusRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegulatoryExternalIntegrationController extends Controller
{
    public function index(ListRegulatoryExternalIntegrations $integrations): Response
    {
        Gate::authorize('viewAny', RegulatoryExternalIntegration::class);

        return Inertia::render('regulations/external-integrations/index', ['integrations' => $integrations->execute()]);
    }

    public function create(ExternalIntegrationOptions $options): Response
    {
        Gate::authorize('create', RegulatoryExternalIntegration::class);

        return Inertia::render('regulations/external-integrations/create', ['options' => $options->execute()]);
    }

    public function store(StoreRegulatoryExternalIntegrationRequest $request, CreateRegulatoryExternalIntegration $createIntegration): RedirectResponse
    {
        $integration = $createIntegration->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-external-integrations.show', $integration)->with('success', 'External regulatory integration captured.');
    }

    public function show(RegulatoryExternalIntegration $regulatoryExternalIntegration, ExternalIntegrationOptions $options): Response
    {
        Gate::authorize('view', $regulatoryExternalIntegration);

        return Inertia::render('regulations/external-integrations/show', [
            'integration' => RegulatoryExternalIntegrationPresenter::toArray($regulatoryExternalIntegration),
            'options' => $options->execute(),
        ]);
    }

    public function updateStatus(UpdateRegulatoryExternalIntegrationStatusRequest $request, RegulatoryExternalIntegration $regulatoryExternalIntegration, UpdateRegulatoryExternalIntegrationStatus $updateStatus): RedirectResponse
    {
        $updateStatus->execute($regulatoryExternalIntegration, $request->validated('status'), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('regulatory-external-integrations.show', $regulatoryExternalIntegration)->with('success', 'Integration status updated.');
    }
}
