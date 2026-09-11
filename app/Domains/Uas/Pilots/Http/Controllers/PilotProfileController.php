<?php

namespace App\Domains\Uas\Pilots\Http\Controllers;

use App\Domains\Uas\Pilots\Application\Actions\CreatePilotProfile;
use App\Domains\Uas\Pilots\Application\Actions\UpdatePilotProfile;
use App\Domains\Uas\Pilots\Application\DTOs\PilotProfileData;
use App\Domains\Uas\Pilots\Application\Queries\ListPilotProfiles;
use App\Domains\Uas\Pilots\Application\Queries\PilotProfileOptions;
use App\Domains\Uas\Pilots\Application\Queries\PilotProfilePresenter;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Domains\Uas\Pilots\Http\Requests\StorePilotProfileRequest;
use App\Domains\Uas\Pilots\Http\Requests\UpdatePilotProfileRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PilotProfileController extends Controller
{
    public function index(ListPilotProfiles $pilots): Response
    {
        Gate::authorize('viewAny', UasPilot::class);

        return Inertia::render('pilots/index', [
            'pilots' => $pilots->execute(),
        ]);
    }

    public function create(PilotProfileOptions $options): Response
    {
        Gate::authorize('create', UasPilot::class);

        return Inertia::render('pilots/create', [
            'options' => $options->execute(),
        ]);
    }

    public function store(StorePilotProfileRequest $request, CreatePilotProfile $createPilot): RedirectResponse
    {
        $pilot = $createPilot->execute(
            PilotProfileData::fromArray($request->validated()),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('pilots.show', $pilot)->with('success', 'Pilot profile created.');
    }

    public function show(UasPilot $pilot): Response
    {
        Gate::authorize('view', $pilot);

        return Inertia::render('pilots/show', [
            'pilot' => PilotProfilePresenter::toArray($pilot),
        ]);
    }

    public function edit(UasPilot $pilot, PilotProfileOptions $options): Response
    {
        Gate::authorize('update', $pilot);

        return Inertia::render('pilots/edit', [
            'pilot' => PilotProfilePresenter::toArray($pilot),
            'options' => $options->execute(),
        ]);
    }

    public function update(UpdatePilotProfileRequest $request, UasPilot $pilot, UpdatePilotProfile $updatePilot): RedirectResponse
    {
        $pilot = $updatePilot->execute(
            $pilot,
            PilotProfileData::fromArray($request->validated()),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('pilots.show', $pilot)->with('success', 'Pilot profile updated.');
    }
}
