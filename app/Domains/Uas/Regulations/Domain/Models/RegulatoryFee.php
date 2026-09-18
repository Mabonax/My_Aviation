<?php

namespace App\Domains\Uas\Regulations\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulatoryFee extends Model
{
    protected $fillable = ['previous_fee_id', 'regulation_part', 'transaction_code', 'description', 'amount', 'currency', 'effective_from', 'effective_to', 'source', 'source_version', 'status', 'verified_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date', 'verified_at' => 'datetime'];
    }

    public function previousFee(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_fee_id');
    }

    public function supersedingFees(): HasMany
    {
        return $this->hasMany(self::class, 'previous_fee_id');
    }
}
