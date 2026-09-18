<?php

namespace App\Domains\Uas\Pilots\Http\Controllers;

use App\Domains\Uas\Pilots\Application\Actions\CreateOwnPilotProfile;
use App\Domains\Uas\Pilots\Application\Actions\UpdateOwnPilotProfile;
use App\Domains\Uas\Pilots\Application\Queries\CurrentPilotProfile;
use App\Domains\Uas\Pilots\Application\Queries\MyPilotWorkspace;
use App\Domains\Uas\Pilots\Application\Queries\PilotProfileOptions;
use App\Domains\Uas\Pilots\Application\Queries\PilotProfilePresenter;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Pilots\Http\Requests\StoreOwnPilotProfileRequest;
use App\Domains\Uas\Pilots\Http\Requests\UpdateOwnPilotProfileRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MyPilotProfileController extends Controller
{
    public function show(CurrentPilotProfile $currentPilot, MyPilotWorkspace $workspace): Response
    {
        $pilot = $currentPilot->resolve(request()->user());

        if ($pilot === null) {
            Gate::authorize('createOwn', UasPilot::class);

            return Inertia::render('my/pilot/show', [
                'workspace' => null,
            ]);
        }

        Gate::authorize('viewOwn', $pilot);

        return Inertia::render('my/pilot/show', [
            'workspace' => $workspace->execute($pilot),
        ]);
    }

    public function create(CurrentPilotProfile $currentPilot, PilotProfileOptions $options): Response|RedirectResponse
    {
        Gate::authorize('createOwn', UasPilot::class);

        if ($currentPilot->resolve(request()->user()) !== null) {
            return redirect()->route('my.pilot.show');
        }

        return Inertia::render('my/pilot/create', [
            'options' => $options->execute(),
        ]);
    }

    public function store(StoreOwnPilotProfileRequest $request, CreateOwnPilotProfile $createPilot): RedirectResponse
    {
        $createPilot->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('my.pilot.show')->with('success', 'Pilot profile created.');
    }

    public function edit(CurrentPilotProfile $currentPilot, PilotProfileOptions $options): Response
    {
        $pilot = $currentPilot->resolve(request()->user());
        abort_unless($pilot instanceof UasPilot, 404);

        Gate::authorize('updateOwn', $pilot);

        return Inertia::render('my/pilot/edit', [
            'pilot' => PilotProfilePresenter::toArray($pilot),
            'options' => $options->execute(),
        ]);
    }

    public function update(UpdateOwnPilotProfileRequest $request, CurrentPilotProfile $currentPilot, UpdateOwnPilotProfile $updatePilot): RedirectResponse
    {
        $pilot = $currentPilot->resolve($request->user());
        abort_unless($pilot instanceof UasPilot, 404);

        $updatePilot->execute($pilot, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('my.pilot.show')->with('success', 'Pilot profile updated.');
    }
}
