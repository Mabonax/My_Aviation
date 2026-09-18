<?php

namespace App\Domains\Uas\Regulations\Application\Queries;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryFee;

class RegulatoryFeePresenter
{
    public static function toArray(RegulatoryFee $fee): array
    {
        $fee->loadMissing(['previousFee', 'supersedingFees']);

        return [
            'id' => $fee->id,
            'previous_fee_id' => $fee->previous_fee_id,
            'previous_fee' => $fee->previousFee ? self::summary($fee->previousFee) : null,
            'superseding_fees' => $fee->supersedingFees->map(fn (RegulatoryFee $version): array => self::summary($version))->values()->all(),
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
        ];
    }

    private static function summary(RegulatoryFee $fee): array
    {
        return [
            'id' => $fee->id,
            'transaction_code' => $fee->transaction_code,
            'description' => $fee->description,
            'amount' => $fee->amount,
            'currency' => $fee->currency,
            'source_version' => $fee->source_version,
            'status' => $fee->status ?? 'active',
            'effective_from' => $fee->effective_from?->toDateString(),
        ];
    }
}
