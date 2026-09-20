<?php

namespace App\Domains\Uas\Records\Application\Services;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditOperatorContext
{
    public function __construct(private readonly CurrentOperatorContext $operators) {}

    /** @return array{0:?int,1:?string} */
    public function resolve(?Request $request, Model $auditable, ?int $explicitOperatorId = null): array
    {
        if ($explicitOperatorId !== null) {
            return [$explicitOperatorId, 'explicit'];
        }

        if ($request !== null && $request->user()) {
            $operator = $this->operators->resolveFromRequest($request);
            if ($operator) {
                return [(int) $operator->id, $request->header(CurrentOperatorContext::API_HEADER) ? 'api_header' : 'web_session'];
            }
        }

        if ($auditable instanceof UasOperator) {
            return [(int) $auditable->id, 'auditable'];
        }

        if ($auditable instanceof UasMission && $auditable->uas_operator_id) {
            return [(int) $auditable->uas_operator_id, 'auditable'];
        }

        if ($auditable instanceof UasAircraftDefect) {
            $missionOperatorId = $auditable->mission?->uas_operator_id;
            if ($missionOperatorId) return [(int) $missionOperatorId, 'auditable_relation'];
        }

        if ($auditable instanceof UasAircraft) {
            $ids = $auditable->operators()->wherePivot('status', 'active')->pluck('uas_operators.id');
            if ($ids->count() === 1) return [(int) $ids->first(), 'auditable_relation'];
        }

        return [null, null];
    }
}
