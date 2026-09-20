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
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MissionController extends Controller
{
    public function index(Request $request, ListMissions $missions, CurrentOperatorContext $context): JsonResponse
    {
        Gate::authorize('viewAny', UasMission::class);
        $operator = $context->requireFromRequest($request);
        return ApiResponse::success(['missions' => $missions->execute($request->user(), $operator->id)]);
    }

    public function show(Request $request, UasMission $mission, CurrentOperatorContext $context): JsonResponse
    {
        $this->authorizeInContext($request, $mission, $context);
        return ApiResponse::success(['mission' => MissionPresenter::toArray($mission)]);
    }

    public function compliance(Request $request, UasMission $mission, MissionComplianceSummary $compliance, CurrentOperatorContext $context): JsonResponse
    {
        $this->authorizeInContext($request, $mission, $context);
        return ApiResponse::success(['compliance' => $compliance->execute($mission)]);
    }

    public function postFlightPropagation(Request $request, UasMission $mission, PostFlightPropagationSummary $summary, CurrentOperatorContext $context): JsonResponse
    {
        $this->authorizeInContext($request, $mission, $context);
        return ApiResponse::success(['post_flight_propagation' => $summary->execute($mission)]);
    }

    public function propagatePostFlight(PropagatePostFlightRequest $request, UasMission $mission, PropagatePostFlightRecords $action, CurrentOperatorContext $context): JsonResponse
    {
        $this->authorizeInContext($request, $mission, $context);
        return ApiResponse::success([
            'post_flight_propagation' => $action->execute($mission, $request->user(), $request->closureData(), $request->ip(), $request->userAgent()),
        ], 'Post-flight records propagated.');
    }

    private function authorizeInContext(Request $request, UasMission $mission, CurrentOperatorContext $context): void
    {
        $operator = $context->requireFromRequest($request);
        abort_unless((int) $mission->uas_operator_id === (int) $operator->id, 404);
        Gate::authorize('view', $mission);
    }
}
