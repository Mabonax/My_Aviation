<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasOperatorCertificateCase extends Model
{
    protected $fillable = [
        'uas_operator_id',
        'opened_by',
        'updated_by',
        'case_number',
        'case_type',
        'status',
        'deadline_at',
        'evidence_requirements',
        'outstanding_documents',
        'fleet_scope',
        'personnel_scope',
        'ops_spec_scope',
        'operations_manual_revision',
        'fees',
        'submission_status',
        'authority_correspondence',
        'outcome',
        'submitted_at',
        'decided_at',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'date',
            'evidence_requirements' => 'array',
            'outstanding_documents' => 'array',
            'fleet_scope' => 'array',
            'personnel_scope' => 'array',
            'ops_spec_scope' => 'array',
            'fees' => 'array',
            'authority_correspondence' => 'array',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UasOperator::class, 'uas_operator_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}