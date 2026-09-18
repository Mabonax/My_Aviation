<?php

namespace App\Domains\Uas\Documents\Domain\Models;

use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvidenceDocument extends Model
{
    protected $table = 'uas_evidence_documents';

    protected $fillable = [
        'document_uid',
        'uas_operator_id',
        'uploaded_by_user_id',
        'title',
        'category',
        'status',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'version',
        'access_level',
        'source_reference',
        'effective_date',
        'expires_at',
        'retention_ends_at',
        'metadata',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'expires_at' => 'datetime',
            'retention_ends_at' => 'datetime',
            'metadata' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UasOperator::class, 'uas_operator_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(EvidenceLink::class, 'uas_evidence_document_id');
    }
}
