<?php

namespace App\Domains\Uas\Documents\Domain\Policies;

use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Documents\Application\Support\EvidenceOperatorResolver;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EvidenceDocumentPolicy
{
    public function __construct(
        private readonly CurrentOperatorContext $operatorContext,
        private readonly EvidenceOperatorResolver $operatorResolver,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->operatorContext->hasGlobalOperatorAccess($user) || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, EvidenceDocument $document): bool
    {
        return $document->uas_operator_id !== null
            && $this->operatorContext->canAccessOperator($user, $document->uas_operator_id);
    }

    public function create(User $user, ?Model $target = null): bool
    {
        $operatorId = $this->operatorResolver->operatorIdFor($target);
        return $operatorId !== null && $this->operatorContext->canManageOperator($user, $operatorId);
    }

    public function attach(User $user, Model $target): bool
    {
        if ($target instanceof ComplianceFinding && $target->compliable) {
            return $this->attach($user, $target->compliable);
        }
        return $this->create($user, $target);
    }
}
