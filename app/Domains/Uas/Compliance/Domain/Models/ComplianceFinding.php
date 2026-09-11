<?php

namespace App\Domains\Uas\Compliance\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
}
