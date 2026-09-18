<?php

namespace App\Domains\Uas\Operators\Domain\Models;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Documents\Domain\Models\EvidenceLink;
use App\Domains\Uas\Documents\Domain\Models\RegulatoryDocument;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UasOperator extends Model
{
    protected $fillable = [
        'legal_entity',
        'trading_name',
        'registration_number',
        'uasoc_number',
        'certificate_issue_date',
        'certificate_expiry_date',
        'status',
        'accountable_manager',
        'responsible_person_flight_operations',
        'responsible_person_aircraft',
        'safety_manager',
        'security_coordinator',
        'operating_bases',
        'approved_aircraft',
        'approved_pilots',
        'operations_specifications',
        'evidence_references',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
        'responsible_role',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'certificate_issue_date' => 'date',
            'certificate_expiry_date' => 'date',
            'operating_bases' => 'array',
            'approved_aircraft' => 'array',
            'approved_pilots' => 'array',
            'operations_specifications' => 'array',
            'evidence_references' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function certificateCases(): HasMany
    {
        return $this->hasMany(UasOperatorCertificateCase::class, 'uas_operator_id');
    }

    public function manualRevisions(): HasMany
    {
        return $this->hasMany(UasOperationsManualRevision::class, 'uas_operator_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UasOperatorMembership::class, 'uas_operator_id');
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', UasOperatorMembership::STATUS_ACTIVE);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'uas_operator_memberships', 'uas_operator_id', 'user_id')
            ->withPivot(['membership_role', 'status', 'joined_at', 'left_at', 'invited_at', 'activated_at'])
            ->withTimestamps();
    }

    public function pilots(): BelongsToMany
    {
        return $this->belongsToMany(UasPilot::class, 'uas_operator_pilots', 'uas_operator_id', 'uas_pilot_id')
            ->withPivot(['assignment_role', 'status', 'approved_from', 'approved_until', 'notes'])
            ->withTimestamps();
    }

    public function aircraft(): BelongsToMany
    {
        return $this->belongsToMany(UasAircraft::class, 'uas_operator_aircraft', 'uas_operator_id', 'uas_aircraft_id')
            ->withPivot(['assignment_role', 'status', 'approved_from', 'approved_until', 'notes'])
            ->withTimestamps();
    }

    public function missions(): HasMany
    {
        return $this->hasMany(UasMission::class, 'uas_operator_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(RegulatoryDocument::class, 'documentable');
    }

    public function evidenceLinks(): MorphMany
    {
        return $this->morphMany(EvidenceLink::class, 'evidenceable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
