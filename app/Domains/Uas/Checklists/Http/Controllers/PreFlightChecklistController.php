<?php

namespace App\Domains\Uas\Checklists\Http\Controllers;

use App\Domains\Uas\Checklists\Application\Actions\RecordMissionChecklist;
use App\Domains\Uas\Checklists\Application\Queries\MissionChecklistReport;
use App\Domains\Uas\Checklists\Http\Requests\StorePreFlightChecklistRequest;
use App\Domains\Uas\Missions\Application\Queries\MissionPresenter;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PreFlightChecklistController extends Controller
{
    public function create(UasMission $mission, MissionChecklistReport $report): Response
    {
        Gate::authorize('update', $mission);

        return Inertia::render('missions/checklists/pre-flight', [
            'mission' => MissionPresenter::toArray($mission),
            'checklist' => $report->execute($mission, 'pre_flight'),
        ]);
    }

    public function store(StorePreFlightChecklistRequest $request, UasMission $mission, RecordMissionChecklist $recordChecklist): RedirectResponse
    {
        Gate::authorize('update', $mission);

        $recordChecklist->execute(
            $mission,
            'pre_flight',
            $request->validated('results'),
            $request->validated('exceptions'),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('missions.show', $mission)->with('success', 'Pre-flight checklist recorded.');
    }
}