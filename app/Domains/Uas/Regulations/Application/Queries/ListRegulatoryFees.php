<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;

class ListRegulatoryFees
{
    public function execute(): array
    {
        return RegulatoryFee::query()
            ->withCount('supersedingFees')
            ->orderBy('regulation_part')
            ->orderBy('transaction_code')
            ->orderByDesc('effective_from')
            ->get()
            ->map(fn (RegulatoryFee $fee): array => [
                'id' => $fee->id,
                'regulation_part' => $fee->regulation_part,
                'transaction_code' => $fee->transaction_code,
                'description' => $fee->description,
                'amount' => $fee->amount,
                'currency' => $fee->currency,
                'effective_from' => $fee->effective_from?->toDateString(),
                'effective_to' => $fee->effective_to?->toDateString(),
                'source' => $fee->source,
                'source_version' => $fee->source_version,
                'status' => $fee->status ?? 'active',
                'verified_at' => $fee->verified_at?->toISOString(),
                'superseding_versions_count' => $fee->superseding_fees_count,
            ])
            ->all();
    }
}
