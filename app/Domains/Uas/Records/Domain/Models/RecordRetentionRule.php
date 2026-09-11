<?php

namespace App\Domains\Uas\Records\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class RecordRetentionRule extends Model
{
    protected $fillable = ['record_category', 'regulatory_source', 'source_version', 'effective_date', 'retention_period', 'applicability'];

    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }
}
