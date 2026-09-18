<?php

namespace App\Domains\Uas\Regulations\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class RegulatoryExternalIntegration extends Model
{
    protected $fillable = [
        'name',
        'authority',
        'classification',
        'regulatory_area',
        'supported_process',
        'authoritative_url',
        'evidence_required',
        'workflow_notes',
        'api_assumption_blocked',
        'status',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'api_assumption_blocked' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
}
