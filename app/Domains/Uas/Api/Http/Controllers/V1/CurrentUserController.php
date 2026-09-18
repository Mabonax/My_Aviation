<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Pilots\Application\Queries\CurrentPilotProfile;
use App\Domains\Uas\Pilots\Application\Queries\PilotProfilePresenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function pilot(Request $request, CurrentPilotProfile $currentPilot): JsonResponse
    {
        $pilot = $currentPilot->resolve($request->user());

        return ApiResponse::success([
            'pilot' => $pilot ? PilotProfilePresenter::toArray($pilot) : null,
        ]);
    }

    public function operators(Request $request, CurrentOperatorContext $operatorContext): JsonResponse
    {
        $user = $request->user();

        if ($operatorContext->hasGlobalOperatorAccess($user)) {
            $operators = $operatorContext->scopeOperatorsFor($user)
                ->orderBy('legal_entity')
                ->get()
                ->map(fn ($operator): array => [
                    'id' => $operator->id,
                    'legal_entity' => $operator->legal_entity,
                    'trading_name' => $operator->trading_name,
                    'uasoc_number' => $operator->uasoc_number,
                    'membership_role' => null,
                    'membership_status' => 'global',
                ])
                ->values()
                ->all();

            return ApiResponse::success(['operators' => $operators]);
        }

        $operators = $user->operatorMemberships()
            ->with('operator')
            ->where('status', UasOperatorMembership::STATUS_ACTIVE)
            ->orderBy('membership_role')
            ->get()
            ->map(fn (UasOperatorMembership $membership): array => [
                'id' => $membership->operator->id,
                'legal_entity' => $membership->operator->legal_entity,
                'trading_name' => $membership->operator->trading_name,
                'uasoc_number' => $membership->operator->uasoc_number,
                'membership_role' => $membership->membership_role,
                'membership_status' => $membership->status,
            ])
            ->values()
            ->all();

        return ApiResponse::success(['operators' => $operators]);
    }
}
