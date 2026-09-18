<?php

namespace App\Domains\Uas\Geography\Http\Controllers;

use App\Domains\Uas\Geography\Application\Actions\CreateGisProject;
use App\Domains\Uas\Geography\Application\Actions\TransitionGisProjectLifecycle;
use App\Domains\Uas\Geography\Application\Queries\GisProjectOptions;
use App\Domains\Uas\Geography\Application\Queries\GisProjectPresenter;
use App\Domains\Uas\Geography\Application\Queries\ListGisProjects;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Http\Requests\StoreGisProjectRequest;
use App\Domains\Uas\Geography\Http\Requests\TransitionGisProjectRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GisProjectController extends Controller
{
    public function index(ListGisProjects $projects): Response
    {
        Gate::authorize('viewAny', UasGisProject::class);

        return Inertia::render('geography/projects/index', ['projects' => $projects->execute()]);
    }

    public function create(GisProjectOptions $options): Response
    {
        Gate::authorize('create', UasGisProject::class);

        return Inertia::render('geography/projects/create', ['options' => $options->execute()]);
    }

    public function store(StoreGisProjectRequest $request, CreateGisProject $createProject): RedirectResponse
    {
        $project = $createProject->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('gis-projects.show', $project)->with('success', 'GIS project created.');
    }

    public function show(UasGisProject $gisProject, GisProjectOptions $options): Response
    {
        Gate::authorize('view', $gisProject);

        return Inertia::render('geography/projects/show', [
            'project' => GisProjectPresenter::toArray($gisProject),
            'options' => $options->execute(),
        ]);
    }

    public function transition(TransitionGisProjectRequest $request, UasGisProject $gisProject, TransitionGisProjectLifecycle $transition): RedirectResponse
    {
        $transition->execute($gisProject, $request->validated('lifecycle_state'), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('gis-projects.show', $gisProject)->with('success', 'GIS project lifecycle updated.');
    }
}
