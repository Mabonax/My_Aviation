<?php

namespace App\Domains\Uas\Pilots\Domain\Models;

use App\Domains\Uas\Pilots\Domain\Enums\PilotMedicalStatus;
use App\Domains\Uas\Pilots\Domain\Enums\PilotProfileStatus;
use App\Domains\Uas\Pilots\Domain\Enums\RadiotelephonyQualification;
use App\Domains\Uas\Pilots\Domain\Enums\RpcCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasPilot extends Model
{
    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'preferred_name',
        'email',
        'phone',
        'nationality',
        'date_of_birth',
        'sacaa_certificate_number',
        'rpc_category',
        'ratings',
        'medical_status',
        'radiotelephony_qualification',
        'language_proficiency',
        'training_history',
        'examiner_records',
        'operator_affiliations',
        'supporting_document_references',
        'profile_status',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
        'responsible_role',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'rpc_category' => RpcCategory::class,
            'ratings' => 'array',
            'medical_status' => PilotMedicalStatus::class,
            'radiotelephony_qualification' => RadiotelephonyQualification::class,
            'training_history' => 'array',
            'examiner_records' => 'array',
            'operator_affiliations' => 'array',
            'supporting_document_references' => 'array',
            'profile_status' => PilotProfileStatus::class,
            'regulatory_effective_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
