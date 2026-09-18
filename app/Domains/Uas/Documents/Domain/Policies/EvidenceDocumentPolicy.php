<?php

namespace App\Domains\Uas\Documents\Domain\Policies;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Documents\Application\Support\EvidenceOperatorResolver;
use App\Domains\Uas\Documents\Domain\Models\EvidenceDocument;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
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
        return $user->hasAnyUasPermission(['documents.view', 'operators.view', 'missions.view'])
            || $user->activeOperatorMemberships()->exists();
    }

    public function view(User $user, EvidenceDocument $document): bool
    {
        if ($user->hasAnyUasPermission(['documents.view', 'operators.view'])) {
            return true;
        }

        return $document->uas_operator_id !== null
            && $this->operatorContext->canAccessOperator($user, $document->uas_operator_id);
    }

    public function create(User $user, ?Model $target = null): bool
    {
        if ($user->hasAnyUasPermission(['documents.create', 'operators.update'])) {
            return true;
        }

        $operatorId = $this->operatorResolver->operatorIdFor($target);

        return $operatorId !== null && $this->operatorContext->canManageOperator($user, $operatorId);
    }

    public function attach(User $user, Model $target): bool
    {
        if ($target instanceof ComplianceFinding && $target->compliable) {
            return $this->attach($user, $target->compliable);
        }

        if ($target instanceof UasOperator || $target instanceof UasMission || $target instanceof UasAircraft) {
            return $this->create($user, $target);
        }

        return $user->hasUasPermission('documents.create');
    }
}
