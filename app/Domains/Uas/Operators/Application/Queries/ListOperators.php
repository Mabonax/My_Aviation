<?php

namespace App\Domains\Uas\Operators\Application\Queries;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Models\User;

class ListOperators
{
    public function execute(?User $user = null): array
    {
        $query = UasOperator::query();

        if ($user !== null) {
            $query = app(CurrentOperatorContext::class)->scopeOperatorsFor($user);
        }

        return $query
            ->orderBy('legal_entity')
            ->get()
            ->map(fn (UasOperator $operator): array => [
                'id' => $operator->id,
                'legal_entity' => $operator->legal_entity,
                'trading_name' => $operator->trading_name,
                'uasoc_number' => $operator->uasoc_number,
                'status' => $operator->status,
                'certificate_expiry_date' => $operator->certificate_expiry_date?->toDateString(),
                'accountable_manager' => $operator->accountable_manager,
                'operating_bases_count' => count($operator->operating_bases ?? []),
                'approved_aircraft_count' => count($operator->approved_aircraft ?? []),
                'approved_pilots_count' => count($operator->approved_pilots ?? []),
            ])->values()->all();
    }
}
