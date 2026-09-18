<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Missions\Application\Actions\PropagatePostFlightRecords;
use App\Domains\Uas\Missions\Application\Queries\ListMissions;
use App\Domains\Uas\Missions\Application\Queries\MissionComplianceSummary;
use App\Domains\Uas\Missions\Application\Queries\MissionPresenter;
use App\Domains\Uas\Missions\Application\Queries\PostFlightPropagationSummary;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Http\Requests\PropagatePostFlightRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MissionController extends Controller
{
    public function index(Request $request, ListMissions $missions): JsonResponse
    {
        Gate::authorize('viewAny', UasMission::class);

        return ApiResponse::success([
            'missions' => $missions->execute($request->user()),
        ]);
    }

    public function show(UasMission $mission): JsonResponse
    {
        Gate::authorize('view', $mission);

        return ApiResponse::success([
            'mission' => MissionPresenter::toArray($mission),
        ]);
    }

    public function compliance(UasMission $mission, MissionComplianceSummary $compliance): JsonResponse
    {
        Gate::authorize('view', $mission);

        return ApiResponse::success([
            'compliance' => $compliance->execute($mission),
        ]);
    }

    public function postFlightPropagation(UasMission $mission, PostFlightPropagationSummary $summary): JsonResponse
    {
        Gate::authorize('view', $mission);

        return ApiResponse::success([
            'post_flight_propagation' => $summary->execute($mission),
        ]);
    }

    public function propagatePostFlight(PropagatePostFlightRequest $request, UasMission $mission, PropagatePostFlightRecords $propagatePostFlightRecords): JsonResponse
    {
        return ApiResponse::success([
            'post_flight_propagation' => $propagatePostFlightRecords->execute($mission, $request->user(), $request->closureData(), $request->ip(), $request->userAgent()),
        ], 'Post-flight records propagated.');
    }
}
