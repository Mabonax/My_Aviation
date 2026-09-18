<?php

namespace App\Domains\Uas\Notifications\Http\Controllers;

use App\Domains\Uas\Notifications\Application\Actions\PlanComplianceNotification;
use App\Domains\Uas\Notifications\Application\Actions\UpdateComplianceNotificationStatus;
use App\Domains\Uas\Notifications\Application\Queries\ComplianceNotificationOptions;
use App\Domains\Uas\Notifications\Application\Queries\ComplianceNotificationPresenter;
use App\Domains\Uas\Notifications\Application\Queries\ListComplianceNotifications;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Notifications\Http\Requests\StoreComplianceNotificationRequest;
use App\Domains\Uas\Notifications\Http\Requests\UpdateComplianceNotificationStatusRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceNotificationController extends Controller
{
    public function index(ListComplianceNotifications $notifications): Response
    {
        Gate::authorize('viewAny', ComplianceNotification::class);

        return Inertia::render('notifications/index', ['notifications' => $notifications->execute()]);
    }

    public function create(ComplianceNotificationOptions $options): Response
    {
        Gate::authorize('create', ComplianceNotification::class);

        return Inertia::render('notifications/create', ['options' => $options->execute()]);
    }

    public function store(StoreComplianceNotificationRequest $request, PlanComplianceNotification $planner): RedirectResponse
    {
        $notification = $planner->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('compliance-notifications.show', $notification)->with('success', 'Compliance notification planned.');
    }

    public function show(ComplianceNotification $complianceNotification): Response
    {
        Gate::authorize('view', $complianceNotification);

        return Inertia::render('notifications/show', ['notification' => ComplianceNotificationPresenter::toArray($complianceNotification)]);
    }

    public function updateStatus(UpdateComplianceNotificationStatusRequest $request, ComplianceNotification $complianceNotification, UpdateComplianceNotificationStatus $updateStatus): RedirectResponse
    {
        $notification = $updateStatus->execute($complianceNotification, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('compliance-notifications.show', $notification)->with('success', 'Compliance notification updated.');
    }
}
