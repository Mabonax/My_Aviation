<?php

namespace App\Http\Middleware;

use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $workspace = null;
        $capabilities = null;

        if ($user = $request->user()) {
            $context = app(CurrentOperatorContext::class);
            $active = $context->resolveFromRequest($request);
            $operators = $context->scopeOperatorsFor($user)
                ->orderBy('legal_entity')
                ->get(['id', 'legal_entity', 'trading_name', 'uasoc_number']);

            $workspace = [
                'active_operator' => $active ? [
                    'id' => $active->id,
                    'name' => $active->trading_name ?: $active->legal_entity,
                    'uasoc_number' => $active->uasoc_number,
                ] : null,
                'operators' => $operators->map(fn ($operator) => [
                    'id' => $operator->id,
                    'name' => $operator->trading_name ?: $operator->legal_entity,
                    'uasoc_number' => $operator->uasoc_number,
                ])->values(),
                'requires_selection' => $active === null && $operators->count() > 1,
                'can_manage' => $active ? $context->canManageOperator($user, $active) : false,
            ];

            $isPlatformAdmin = $user->isSuperAdmin() || $user->hasAnyPlatformAuthority([
                'platform.super_admin',
                'platform.support',
                'platform.regulatory_admin',
            ]);
            $isPilot = $user->hasUasPermission('pilots.self-service');
            $hasOperator = $operators->isNotEmpty();

            $capabilities = [
                'persona' => $isPlatformAdmin ? 'platform_admin' : ($isPilot ? 'pilot' : ($hasOperator ? 'operator_user' : 'user')),
                'pilot_self_service' => $isPilot,
                'operator_workspace' => $hasOperator,
                'operator_manage' => $workspace['can_manage'],
                'missions' => $hasOperator || $user->hasUasPermission('missions.view') || $user->hasUasPermission('missions.create'),
                'aeronautical_information' => $hasOperator || $user->hasAnyUasPermission(['aeronautical-information.view', 'missions.view']) || $isPlatformAdmin,
                'platform_admin' => $isPlatformAdmin,
            ];
        }

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => ['user' => $request->user()],
            'operatorWorkspace' => $workspace,
            'uiCapabilities' => $capabilities,
        ]);
    }
}
