<?php

namespace App\Domains\Uas\Operators\Domain\Policies;

use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Models\User;

class OperatorPolicy
{
    public function __construct(private readonly CurrentOperatorContext $operatorContext) {}

    public function viewAny(User $user): bool
    {
        return $user->hasUasPermission('operators.view')
            || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, UasOperator $operator): bool
    {
        return $this->operatorContext->canAccessOperator($user, $operator);
    }

    public function create(User $user): bool
    {
        return $user->hasUasPermission('operators.create');
    }

    public function update(User $user, UasOperator $operator): bool
    {
        return $this->operatorContext->canManageOperator($user, $operator);
    }

    public function manageMemberships(User $user, UasOperator $operator): bool
    {
        return $this->operatorContext->canManageOperator($user, $operator);
    }

    public function delete(User $user, UasOperator $operator): bool
    {
        return false;
    }
}
