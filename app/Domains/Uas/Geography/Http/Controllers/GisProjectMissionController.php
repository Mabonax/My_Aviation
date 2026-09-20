<?php

namespace App\Domains\Uas\Geography\Http\Controllers;

use App\Domains\Uas\Geography\Application\Actions\AssignMissionToGisProject;
use App\Domains\Uas\Geography\Application\Queries\GisProjectMissionOptions;
use App\Domains\Uas\Geography\Application\Queries\GisProjectPresenter;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Http\Requests\StoreGisProjectMissionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GisProjectMissionController extends Controller
{
    public function create(UasGisProject $gisProject, GisProjectMissionOptions $options): Response
    {
        Gate::authorize('update', $gisProject);

        return Inertia::render('geography/projects/missions/create', [
            'project' => GisProjectPresenter::summary($gisProject),
            'options' => $options->execute($gisProject->uas_operator_id),
        ]);
    }

    public function store(UasGisProject $gisProject, StoreGisProjectMissionRequest $request, AssignMissionToGisProject $assignMission): RedirectResponse
    {
        $assignMission->execute($gisProject, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('gis-projects.show', $gisProject)->with('success', 'GIS project mission assigned.');
    }
}
