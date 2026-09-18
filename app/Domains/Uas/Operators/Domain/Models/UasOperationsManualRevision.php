<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasOperationsManualRevision extends Model
{
    protected $fillable = [
        'uas_operator_id',
        'superseded_revision_id',
        'created_by',
        'updated_by',
        'manual_name',
        'revision_code',
        'effective_date',
        'approval_status',
        'authority_approval_reference',
        'sections',
        'change_summary',
        'evidence_references',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'sections' => 'array',
            'evidence_references' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UasOperator::class, 'uas_operator_id');
    }

    public function supersededRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_revision_id');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(UasOperationsManualDistribution::class, 'manual_revision_id');
    }

    public function trainingRequirements(): HasMany
    {
        return $this->hasMany(UasOperationsManualTrainingRequirement::class, 'manual_revision_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

