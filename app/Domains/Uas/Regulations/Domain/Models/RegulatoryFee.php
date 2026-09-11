<?php

namespace App\Domains\Uas\Regulations\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class RegulatoryFee extends Model
{
    protected $fillable = ['regulation_part', 'transaction_code', 'description', 'amount', 'currency', 'effective_from', 'effective_to', 'source', 'source_version', 'verified_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date', 'verified_at' => 'datetime'];
    }
}
