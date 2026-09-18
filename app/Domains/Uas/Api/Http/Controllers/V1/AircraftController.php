<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftPresenter;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Application\Queries\ListAircraft;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AircraftController extends Controller
{
    public function index(Request $request, ListAircraft $aircraft, CurrentOperatorContext $context): JsonResponse
    {
        Gate::authorize('viewAny', UasAircraft::class);
        $operator = $context->requireFromRequest($request);

        return ApiResponse::success(['aircraft' => $aircraft->execute($request->user(), $operator->id)]);
    }

    public function show(Request $request, UasAircraft $aircraft, CurrentOperatorContext $context): JsonResponse
    {
        Gate::authorize('view', $aircraft);
        $operator = $context->requireFromRequest($request);

        abort_unless($aircraft->operators()->where('uas_operators.id', $operator->id)->wherePivot('status', 'active')->exists(), 404);

        return ApiResponse::success(['aircraft' => AircraftPresenter::toArray($aircraft)]);
    }
}
