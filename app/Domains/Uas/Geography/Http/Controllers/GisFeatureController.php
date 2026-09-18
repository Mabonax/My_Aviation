<?php

namespace App\Domains\Uas\Geography\Http\Controllers;

use App\Domains\Uas\Geography\Application\Actions\CreateGisFeature;
use App\Domains\Uas\Geography\Application\Queries\GisFeatureOptions;
use App\Domains\Uas\Geography\Domain\Models\UasGisSpatialLayer;
use App\Domains\Uas\Geography\Http\Requests\StoreGisFeatureRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GisFeatureController extends Controller
{
    public function create(UasGisSpatialLayer $spatialLayer, GisFeatureOptions $options): Response
    {
        $spatialLayer->loadMissing(['dataset.projectMission.project', 'dataset.projectMission.mission']);

        Gate::authorize('update', $spatialLayer->dataset->projectMission->project);

        return Inertia::render('geography/projects/features/create', [
            'spatialLayer' => [
                'id' => $spatialLayer->id,
                'layer_name' => $spatialLayer->layer_name,
                'layer_type' => $spatialLayer->layer_type,
                'geometry_type' => $spatialLayer->geometry_type,
                'dataset' => [
                    'id' => $spatialLayer->dataset->id,
                    'dataset_code' => $spatialLayer->dataset->dataset_code,
                    'title' => $spatialLayer->dataset->title,
                ],
                'project' => [
                    'id' => $spatialLayer->dataset->projectMission->project->id,
                    'project_code' => $spatialLayer->dataset->projectMission->project->project_code,
                    'name' => $spatialLayer->dataset->projectMission->project->name,
                ],
                'mission' => [
                    'id' => $spatialLayer->dataset->projectMission->mission->id,
                    'mission_number' => $spatialLayer->dataset->projectMission->mission->mission_number,
                    'purpose' => $spatialLayer->dataset->projectMission->mission->purpose,
                ],
            ],
            'options' => $options->execute(),
        ]);
    }

    public function store(UasGisSpatialLayer $spatialLayer, StoreGisFeatureRequest $request, CreateGisFeature $createFeature): RedirectResponse
    {
        $spatialLayer->loadMissing('dataset.projectMission.project');

        $createFeature->execute($spatialLayer, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('gis-projects.show', $spatialLayer->dataset->projectMission->project)->with('success', 'GIS feature captured.');
    }
}
