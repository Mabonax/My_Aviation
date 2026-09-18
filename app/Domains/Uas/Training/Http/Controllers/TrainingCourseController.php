<?php

namespace App\Domains\Uas\Training\Http\Controllers;

use App\Domains\Uas\Training\Application\Actions\CreateTrainingCourse;
use App\Domains\Uas\Training\Application\Queries\ListTrainingCourses;
use App\Domains\Uas\Training\Application\Queries\TrainingCourseOptions;
use App\Domains\Uas\Training\Application\Queries\TrainingCoursePresenter;
use App\Domains\Uas\Training\Domain\Models\UasTrainingCourse;
use App\Domains\Uas\Training\Http\Requests\StoreTrainingCourseRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrainingCourseController extends Controller
{
    public function index(ListTrainingCourses $courses): Response
    {
        Gate::authorize('viewAny', UasTrainingCourse::class);
        return Inertia::render('training/courses/index', ['courses' => $courses->execute()]);
    }

    public function create(TrainingCourseOptions $options): Response
    {
        Gate::authorize('create', UasTrainingCourse::class);
        return Inertia::render('training/courses/create', ['options' => $options->execute()]);
    }

    public function store(StoreTrainingCourseRequest $request, CreateTrainingCourse $createCourse): RedirectResponse
    {
        $course = $createCourse->execute($request->validated(), $request->user(), $request->ip(), $request->userAgent());
        return redirect()->route('training-courses.show', $course)->with('success', 'Training course created.');
    }

    public function show(UasTrainingCourse $trainingCourse): Response
    {
        Gate::authorize('view', $trainingCourse);
        return Inertia::render('training/courses/show', ['course' => TrainingCoursePresenter::toArray($trainingCourse)]);
    }
}
