<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasOperationsManualTrainingRequirement extends Model
{
    protected $fillable = [
        'manual_revision_id',
        'created_by',
        'title',
        'requirement_type',
        'training_status',
        'affected_roles',
        'due_date',
        'competency_standard',
        'trigger_reason',
        'evidence_references',
        'notes',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'affected_roles' => 'array',
            'due_date' => 'date',
            'evidence_references' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function manualRevision(): BelongsTo
    {
        return $this->belongsTo(UasOperationsManualRevision::class, 'manual_revision_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
