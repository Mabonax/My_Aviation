<?php

namespace App\Domains\Uas\Documents\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EvidenceLink extends Model
{
    protected $table = 'uas_evidence_links';

    protected $fillable = [
        'uas_evidence_document_id',
        'evidenceable_type',
        'evidenceable_id',
        'evidence_role',
        'requirement_id',
        'notes',
        'attached_by_user_id',
        'attached_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attached_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EvidenceDocument::class, 'uas_evidence_document_id');
    }

    public function evidenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function attachedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attached_by_user_id');
    }
}
