<?php

namespace App\Domains\Uas\Pilots\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilotCertificate extends Model
{
    protected $fillable = ['uas_pilot_id', 'certificate_number', 'issue_date', 'expiry_date', 'last_revalidation_date', 'post_revalidation_submission_due_at', 'status', 'regulatory_source', 'regulatory_source_version', 'regulatory_effective_date', 'evidence_references'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'last_revalidation_date' => 'date',
            'post_revalidation_submission_due_at' => 'date',
            'regulatory_effective_date' => 'date',
            'evidence_references' => 'array',
        ];
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(UasPilot::class, 'uas_pilot_id');
    }
}
