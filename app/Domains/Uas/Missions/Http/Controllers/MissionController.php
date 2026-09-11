<?php

namespace App\Domains\Uas\Missions\Http\Controllers;

use App\Domains\Uas\Batteries\Application\Queries\MissionBatteryReport;
use App\Domains\Uas\Checklists\Application\Queries\MissionChecklistReport;
use App\Domains\Uas\Crew\Application\Queries\MissionCrewReport;
use App\Domains\Uas\Defects\Application\Queries\MissionDefectReport;
use App\Domains\Uas\Geography\Domain\Services\MissionSpatialRuleEvaluator;
use App\Domains\Uas\Missions\Application\Actions\CreateMission;
use App\Domains\Uas\Missions\Application\DTOs\MissionData;
use App\Domains\Uas\Missions\Application\Queries\ListMissions;
use App\Domains\Uas\Missions\Application\Queries\MissionOptions;
use App\Domains\Uas\Missions\Application\Queries\MissionPresenter;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Http\Requests\StoreMissionRequest;
use App\Domains\Uas\Tracks\Application\Queries\MissionTrackReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MissionController extends Controller
{
    public function index(ListMissions $missions): Response
    {
        Gate::authorize('viewAny', UasMission::class);

        return Inertia::render('missions/index', [
            'missions' => $missions->execute(),
        ]);
    }

    public function create(MissionOptions $options): Response
    {
        Gate::authorize('create', UasMission::class);

        return Inertia::render('missions/create', [
            'options' => $options->execute(),
        ]);
    }

    public function store(StoreMissionRequest $request, CreateMission $createMission): RedirectResponse
    {
        $mission = $createMission->execute(
            MissionData::fromArray($request->validated()),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('missions.show', $mission)->with('success', 'Mission created.');
    }

    public function show(UasMission $mission, MissionSpatialRuleEvaluator $spatialRules, MissionChecklistReport $checklists, MissionCrewReport $crewReport, MissionTrackReport $trackReport, MissionBatteryReport $batteryReport, MissionDefectReport $defectReport): Response
    {
        Gate::authorize('view', $mission);

        return Inertia::render('missions/show', [
            'mission' => MissionPresenter::toArray($mission),
            'spatialRuleReview' => $spatialRules->evaluate($mission),
            'preFlightChecklist' => $checklists->execute($mission, 'pre_flight'),
            'postFlightChecklist' => $checklists->execute($mission, 'post_flight'),
            'crew' => $crewReport->execute($mission),
            'tracks' => $trackReport->execute($mission),
            'batteries' => $batteryReport->execute($mission),
            'defects' => $defectReport->execute($mission),
        ]);
    }
}
