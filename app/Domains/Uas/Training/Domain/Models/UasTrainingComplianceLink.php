<?php

namespace App\Domains\Uas\Training\Domain\Models;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasTrainingComplianceLink extends Model
{
    protected $fillable = [
        'training_course_id',
        'training_competency_id',
        'training_competency_record_id',
        'regulatory_requirement_id',
        'source_type',
        'requirement_reference',
        'title',
        'responsible_role',
        'applicability',
        'evidence_required',
        'validity_period',
        'retention_period',
        'due_date',
        'link_status',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(UasTrainingCourse::class, 'training_course_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(UasTrainingCompetency::class, 'training_competency_id');
    }

    public function competencyRecord(): BelongsTo
    {
        return $this->belongsTo(UasTrainingCompetencyRecord::class, 'training_competency_record_id');
    }

    public function regulatoryRequirement(): BelongsTo
    {
        return $this->belongsTo(RegulatoryRequirement::class, 'regulatory_requirement_id');
    }
}
