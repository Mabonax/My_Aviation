<?php

namespace App\Domains\Uas\Training\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasTrainingCourse extends Model
{
    protected $fillable = ['created_by', 'code', 'title', 'classification', 'status', 'summary', 'authority_approval_reference', 'regulatory_source', 'regulatory_source_version', 'regulatory_effective_date', 'regulatory_applicability'];

    protected function casts(): array
    {
        return ['regulatory_effective_date' => 'date'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function modules(): HasMany { return $this->hasMany(UasTrainingModule::class, 'training_course_id'); }
    public function assessments(): HasMany { return $this->hasMany(UasTrainingAssessment::class, 'training_course_id'); }
    public function competencies(): HasMany { return $this->hasMany(UasTrainingCompetency::class, 'training_course_id'); }
    public function competencyRecords(): HasMany { return $this->hasMany(UasTrainingCompetencyRecord::class, 'training_course_id'); }
    public function complianceLinks(): HasMany { return $this->hasMany(UasTrainingComplianceLink::class, 'training_course_id'); }
}
