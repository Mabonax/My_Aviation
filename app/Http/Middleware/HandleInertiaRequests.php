<?php

namespace App\Http\Middleware;

use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $workspace = null;

        if ($request->user()) {
            $context = app(CurrentOperatorContext::class);
            $active = $context->resolveFromRequest($request);
            $operators = $context->scopeOperatorsFor($request->user())
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
                'can_manage' => $active ? $context->canManageOperator($request->user(), $active) : false,
            ];
        }

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'operatorWorkspace' => $workspace,
        ]);
    }
}
