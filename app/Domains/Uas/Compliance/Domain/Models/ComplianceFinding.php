<?php

namespace App\Domains\Uas\Compliance\Domain\Models;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Domains\Uas\Documents\Domain\Models\EvidenceLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ComplianceFinding extends Model
{
    protected $fillable = ['compliable_type', 'compliable_id', 'requirement_id', 'state', 'severity', 'summary', 'recommended_action', 'due_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function compliable(): MorphTo
    {
        return $this->morphTo();
    }

    public function regulatoryRequirement(): BelongsTo
    {
        return $this->belongsTo(RegulatoryRequirement::class, 'requirement_id', 'requirement_id');
    }

    public function evidenceLinks(): MorphMany
    {
        return $this->morphMany(EvidenceLink::class, 'evidenceable');
    }
}
