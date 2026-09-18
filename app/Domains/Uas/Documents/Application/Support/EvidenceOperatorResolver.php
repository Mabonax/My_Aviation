<?php

namespace App\Domains\Uas\Documents\Application\Support;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use Illuminate\Database\Eloquent\Model;

class EvidenceOperatorResolver
{
    public function operatorIdFor(?Model $model, ?int $fallbackOperatorId = null): ?int
    {
        if ($model instanceof UasOperator) {
            return $model->id;
        }

        if ($model instanceof UasMission) {
            return $model->uas_operator_id ?? $fallbackOperatorId;
        }

        if ($model instanceof UasAircraft) {
            $model->loadMissing('operators');

            return $model->operators->first()?->id ?? $fallbackOperatorId;
        }

        if ($model instanceof ComplianceFinding) {
            return $this->operatorIdFor($model->compliable, $fallbackOperatorId);
        }

        return $fallbackOperatorId;
    }
}
