<?php

namespace App\Domains\Uas\Defects\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Defects\Application\Actions\ReportAircraftDefect;
use App\Domains\Uas\Defects\Application\Queries\ListAircraftDefects;
use App\Domains\Uas\Defects\Domain\Services\DefectServiceabilityImpact;
use App\Domains\Uas\Defects\Http\Requests\StoreAircraftDefectRequest;
use App\Domains\Uas\Missions\Application\Queries\MissionPresenter;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AircraftDefectController extends Controller
{
    public function index(ListAircraftDefects $defects): Response
    {
        Gate::authorize('viewAny', UasMission::class);

        return Inertia::render('defects/index', ['defects' => $defects->execute()]);
    }

    public function create(?UasMission $mission = null): Response
    {
        if ($mission) {
            Gate::authorize('update', $mission);
        } else {
            Gate::authorize('create', UasMission::class);
        }

        return Inertia::render('defects/create', [
            'mission' => $mission ? MissionPresenter::toArray($mission) : null,
            'aircraft' => UasAircraft::query()->orderBy('registration')->get()->map(fn (UasAircraft $aircraft): array => [
                'id' => $aircraft->id,
                'label' => "{$aircraft->registration} {$aircraft->model}",
                'operational_status' => $aircraft->operational_status,
            ])->values()->all(),
            'sources' => DefectServiceabilityImpact::SOURCES,
            'severities' => DefectServiceabilityImpact::SEVERITIES,
        ]);
    }

    public function store(StoreAircraftDefectRequest $request, ReportAircraftDefect $reportDefect, ?UasMission $mission = null): RedirectResponse
    {
        $defect = $reportDefect->execute($request->validated(), $request->user(), $mission, $request->ip(), $request->userAgent());

        if ($mission) {
            return redirect()->route('missions.show', $mission)->with('success', 'Defect reported.');
        }

        return redirect()->route('defects.index')->with('success', "Defect {$defect->defect_number} reported.");
    }
}