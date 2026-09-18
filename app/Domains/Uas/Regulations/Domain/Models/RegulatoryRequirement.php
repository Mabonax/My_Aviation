<?php

namespace App\Domains\Uas\Regulations\Domain\Models;

use App\Domains\Uas\Training\Domain\Models\UasTrainingComplianceLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulatoryRequirement extends Model
{
    protected $fillable = ['previous_requirement_id', 'requirement_id', 'regulation_part', 'clause_reference', 'title', 'requirement_text', 'responsible_party', 'applicability', 'system_control', 'evidence_required', 'frequency', 'validity_period', 'retention_period', 'effective_date', 'superseded_date', 'official_source', 'source_version', 'status'];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'superseded_date' => 'date'];
    }

    public function trainingComplianceLinks(): HasMany
    {
        return $this->hasMany(UasTrainingComplianceLink::class, 'regulatory_requirement_id');
    }

    public function previousRequirement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_requirement_id');
    }

    public function supersedingRequirements(): HasMany
    {
        return $this->hasMany(self::class, 'previous_requirement_id');
    }
}
