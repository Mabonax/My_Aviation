<?php

namespace App\Domains\Uas\Batteries\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Batteries\Application\Actions\CreateBattery;
use App\Domains\Uas\Batteries\Application\Queries\ListBatteries;
use App\Domains\Uas\Batteries\Http\Requests\StoreBatteryRequest;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BatteryController extends Controller
{
    public function index(ListBatteries $batteries): Response
    {
        Gate::authorize('viewAny', UasMission::class);

        return Inertia::render('batteries/index', ['batteries' => $batteries->execute()]);
    }

    public function create(): Response
    {
        Gate::authorize('create', UasMission::class);

        return Inertia::render('batteries/create', [
            'aircraft' => UasAircraft::query()->orderBy('registration')->get()->map(fn (UasAircraft $aircraft): array => [
                'id' => $aircraft->id,
                'label' => "{$aircraft->registration} {$aircraft->model}",
            ])->values()->all(),
        ]);
    }

    public function store(StoreBatteryRequest $request, CreateBattery $createBattery): RedirectResponse
    {
        $createBattery->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('batteries.index')->with('success', 'Battery created.');
    }
}