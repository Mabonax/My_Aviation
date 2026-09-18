<?php

namespace App\Domains\Uas\Training\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasTrainingCompetencyRecord extends Model
{
    protected $fillable = ['training_course_id', 'participant_name', 'competency_title', 'record_status', 'completed_at', 'evidence_references'];
    protected function casts(): array { return ['completed_at' => 'date', 'evidence_references' => 'array']; }
    public function course(): BelongsTo { return $this->belongsTo(UasTrainingCourse::class, 'training_course_id'); }
    public function complianceLinks(): HasMany { return $this->hasMany(UasTrainingComplianceLink::class, 'training_competency_record_id'); }
}
