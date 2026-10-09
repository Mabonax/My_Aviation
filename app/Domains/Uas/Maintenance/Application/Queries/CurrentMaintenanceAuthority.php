<?php

namespace App\Domains\Uas\Maintenance\Application\Queries;

use App\Domains\Uas\Maintenance\Domain\Models\MaintenanceAuthority;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;

class CurrentMaintenanceAuthority
{
    public function find(int $operatorId, int $aircraftId, int $userId, bool $lock = false, bool $release = false): ?MaintenanceAuthority
    {
        $membership = UasOperatorMembership::query()->where('uas_operator_id', $operatorId)
            ->where('user_id', $userId)->where('status', UasOperatorMembership::STATUS_ACTIVE);
        if ($lock) {
            $membership->lockForUpdate();
        }
        if (! $membership->first()) {
            return null;
        }
        $query = MaintenanceAuthority::query()->where('uas_operator_id', $operatorId)
            ->where('uas_aircraft_id', $aircraftId)->where('user_id', $userId)
            ->whereNull('revoked_at')->whereDate('valid_until', '>=', today())->latest('id');
        if ($release) {
            $query->where('can_return_to_service', true);
        }
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->first();
    }

    public function require(int $operatorId, int $aircraftId, int $userId, bool $release = false): MaintenanceAuthority
    {
        $authority = $this->find($operatorId, $aircraftId, $userId, true, $release);
        abort_unless($authority, 403, 'Current aircraft maintenance certification authority is required.');
        return $authority;
    }
}
