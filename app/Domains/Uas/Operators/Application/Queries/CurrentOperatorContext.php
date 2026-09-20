<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CurrentOperatorContext
{
    public const API_HEADER = 'X-YAW-Operator';

    public function requestedOperatorId(\Illuminate\Http\Request $request): ?int
    {
        $value = $request->header(self::API_HEADER);
        if (($value === null || $value === '') && $request->hasSession()) {
            $value = $request->session()->get('yaw_operator_id');
        }

        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    public function resolveFromRequest(\Illuminate\Http\Request $request): ?UasOperator
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        return $this->resolve($user, $this->requestedOperatorId($request));
    }

    public function requireFromRequest(\Illuminate\Http\Request $request): UasOperator
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $requestedId = $this->requestedOperatorId($request);
        $operator = $this->resolve($user, $requestedId);

        if ($requestedId !== null && $operator === null) {
            abort(403, 'The requested YAW operator context is not accessible.');
        }

        if ($operator === null) {
            abort(409, 'An active YAW operator context is required.');
        }

        return $operator;
    }
    public function resolve(User $user, ?int $operatorId = null): ?UasOperator
    {
        if ($this->hasGlobalOperatorAccess($user)) {
            return $operatorId ? UasOperator::query()->find($operatorId) : null;
        }

        $query = $this->activeMembershipQuery($user)->with('operator');

        if ($operatorId !== null) {
            $query->where('uas_operator_id', $operatorId);
        }

        $memberships = $query->get();

        if ($memberships->count() !== 1) {
            return null;
        }

        return $memberships->first()->operator;
    }

    public function canAccessOperator(User $user, UasOperator|int $operator): bool
    {
        $operatorId = $operator instanceof UasOperator ? $operator->id : $operator;

        return $this->hasGlobalOperatorAccess($user)
            || $this->activeMembershipQuery($user)->where('uas_operator_id', $operatorId)->exists();
    }

    public function canManageOperator(User $user, UasOperator|int $operator): bool
    {
        $operatorId = $operator instanceof UasOperator ? $operator->id : $operator;

        if ($this->hasGlobalOperatorAccess($user)) {
            return true;
        }

        return $this->activeMembershipQuery($user)
            ->where('uas_operator_id', $operatorId)
            ->whereIn('membership_role', UasOperatorMembership::managerRoles())
            ->exists();
    }

    public function accessibleOperatorIds(User $user): array
    {
        if ($this->hasGlobalOperatorAccess($user)) {
            return UasOperator::query()->pluck('id')->all();
        }

        return $this->activeMembershipQuery($user)->pluck('uas_operator_id')->all();
    }

    public function scopeOperatorsFor(User $user): Builder
    {
        $query = UasOperator::query();

        if ($this->hasGlobalOperatorAccess($user)) {
            return $query;
        }

        return $query->whereIn('id', $this->accessibleOperatorIds($user));
    }

    public function hasGlobalOperatorAccess(User $user): bool
    {
        return $user->hasAnyPlatformAuthority([
            'platform.super_admin',
            'platform.support',
            'platform.tenant_admin',
        ]);
    }

    private function activeMembershipQuery(User $user): Builder
    {
        return UasOperatorMembership::query()
            ->where('user_id', $user->id)
            ->where('status', UasOperatorMembership::STATUS_ACTIVE);
    }
}
