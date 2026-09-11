<?php

namespace App\Domains\Uas\Documents\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RegulatoryDocument extends Model
{
    protected $fillable = ['documentable_type', 'documentable_id', 'category', 'title', 'document_reference', 'status', 'regulatory_source', 'source_version', 'effective_date', 'retention_starts_at', 'retention_ends_at', 'locked', 'archived'];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'retention_starts_at' => 'datetime', 'retention_ends_at' => 'datetime', 'locked' => 'boolean', 'archived' => 'boolean'];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
