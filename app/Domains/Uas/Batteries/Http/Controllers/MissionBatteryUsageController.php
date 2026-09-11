<?php

namespace App\Domains\Uas\Batteries\Http\Controllers;

use App\Domains\Uas\Batteries\Application\Actions\RecordMissionBatteryUsage;
use App\Domains\Uas\Batteries\Application\Queries\MissionBatteryReport;
use App\Domains\Uas\Batteries\Http\Requests\StoreMissionBatteryUsageRequest;
use App\Domains\Uas\Missions\Application\Queries\MissionPresenter;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MissionBatteryUsageController extends Controller
{
    public function create(UasMission $mission, MissionBatteryReport $batteryReport): Response
    {
        Gate::authorize('update', $mission);

        return Inertia::render('missions/batteries/create', [
            'mission' => MissionPresenter::toArray($mission),
            'batteries' => $batteryReport->execute($mission),
        ]);
    }

    public function store(StoreMissionBatteryUsageRequest $request, UasMission $mission, RecordMissionBatteryUsage $recordUsage): RedirectResponse
    {
        Gate::authorize('update', $mission);

        $recordUsage->execute($mission, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('missions.show', $mission)->with('success', 'Mission battery usage recorded.');
    }
}