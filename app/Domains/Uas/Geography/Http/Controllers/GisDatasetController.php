<?php

namespace App\Domains\Uas\Geography\Http\Controllers;

use App\Domains\Uas\Geography\Application\Actions\CreateGisDataset;
use App\Domains\Uas\Geography\Application\Queries\GisDatasetOptions;
use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Geography\Http\Requests\StoreGisDatasetRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GisDatasetController extends Controller
{
    public function create(UasGisProjectMission $projectMission, GisDatasetOptions $options): Response
    {
        $projectMission->loadMissing(['project', 'mission']);

        Gate::authorize('update', $projectMission->project);

        return Inertia::render('geography/projects/datasets/create', [
            'projectMission' => [
                'id' => $projectMission->id,
                'mapping_objective' => $projectMission->mapping_objective,
                'project' => [
                    'id' => $projectMission->project->id,
                    'project_code' => $projectMission->project->project_code,
                    'name' => $projectMission->project->name,
                ],
                'mission' => [
                    'id' => $projectMission->mission->id,
                    'mission_number' => $projectMission->mission->mission_number,
                    'purpose' => $projectMission->mission->purpose,
                ],
            ],
            'options' => $options->execute(),
        ]);
    }

    public function store(UasGisProjectMission $projectMission, StoreGisDatasetRequest $request, CreateGisDataset $createDataset): RedirectResponse
    {
        $projectMission->loadMissing('project');

        $createDataset->execute($projectMission, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('gis-projects.show', $projectMission->project)->with('success', 'GIS dataset captured.');
    }
}
