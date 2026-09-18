<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftPresenter;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Application\Queries\ListAircraft;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AircraftController extends Controller
{
    public function index(Request $request, ListAircraft $aircraft): JsonResponse
    {
        Gate::authorize('viewAny', UasAircraft::class);

        return ApiResponse::success([
            'aircraft' => $aircraft->execute($request->user()),
        ]);
    }

    public function show(UasAircraft $aircraft): JsonResponse
    {
        Gate::authorize('view', $aircraft);

        return ApiResponse::success([
            'aircraft' => AircraftPresenter::toArray($aircraft),
        ]);
    }
}
