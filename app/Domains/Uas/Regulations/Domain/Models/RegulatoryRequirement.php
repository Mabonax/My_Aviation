<?php

namespace App\Domains\Uas\Regulations\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class RegulatoryRequirement extends Model
{
    protected $fillable = ['requirement_id', 'regulation_part', 'clause_reference', 'title', 'requirement_text', 'responsible_party', 'applicability', 'system_control', 'evidence_required', 'frequency', 'validity_period', 'retention_period', 'effective_date', 'superseded_date', 'official_source', 'source_version', 'status'];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'superseded_date' => 'date'];
    }
}
