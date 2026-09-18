<?php

namespace App\Domains\Uas\Training\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasTrainingAssessment extends Model
{
    protected $fillable = ['training_course_id', 'title', 'assessment_type', 'pass_mark'];
    public function course(): BelongsTo { return $this->belongsTo(UasTrainingCourse::class, 'training_course_id'); }
}
