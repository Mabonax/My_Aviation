<?php

namespace App\Domains\Uas\Records\Domain\Models;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UasAuditEntry extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'requirement_id',
        'regulatory_source',
        'previous_values',
        'new_values',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_values' => 'array',
            'new_values' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function regulatoryRequirement(): BelongsTo
    {
        return $this->belongsTo(RegulatoryRequirement::class, 'requirement_id', 'requirement_id');
    }
}
