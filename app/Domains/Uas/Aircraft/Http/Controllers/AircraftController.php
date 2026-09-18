<?php

namespace App\Domains\Uas\Aircraft\Http\Controllers;

use App\Domains\Uas\Aircraft\Application\Actions\CreatePhysicalAircraft;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftOnboardingOptions;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftPresenter;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Http\Requests\StorePhysicalAircraftRequest;
use App\Domains\Uas\Operators\Application\Queries\ListAircraft;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AircraftController extends Controller
{
    public function index(ListAircraft $aircraft): Response
    {
        Gate::authorize('viewAny', UasAircraft::class);

        return Inertia::render('aircraft/index', [
            'aircraft' => $aircraft->execute(request()->user()),
        ]);
    }

    public function create(AircraftOnboardingOptions $options): Response
    {
        Gate::authorize('viewAny', UasAircraft::class);

        return Inertia::render('aircraft/create', [
            'options' => $options->execute(request()->user()),
        ]);
    }

    public function store(StorePhysicalAircraftRequest $request, CreatePhysicalAircraft $createAircraft): RedirectResponse
    {
        $aircraft = $createAircraft->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('aircraft.show', $aircraft)->with('success', 'Aircraft onboarding record created.');
    }

    public function show(UasAircraft $aircraft): Response
    {
        Gate::authorize('view', $aircraft);

        return Inertia::render('aircraft/show', [
            'aircraft' => AircraftPresenter::toArray($aircraft),
        ]);
    }
}
