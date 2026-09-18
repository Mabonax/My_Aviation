<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasOperationsManualDistribution extends Model
{
    protected $fillable = [
        'manual_revision_id',
        'created_by',
        'recipient_name',
        'recipient_role',
        'recipient_email',
        'distribution_channel',
        'distribution_status',
        'acknowledgement_status',
        'required_by',
        'distributed_at',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgement_statement',
        'acknowledgement_notes',
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
            'required_by' => 'date',
            'distributed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'evidence_references' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function manualRevision(): BelongsTo
    {
        return $this->belongsTo(UasOperationsManualRevision::class, 'manual_revision_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
