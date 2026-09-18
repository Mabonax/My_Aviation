<?php

namespace App\Domains\Uas\Training\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasTrainingCompetency extends Model
{
    protected $fillable = ['training_course_id', 'title', 'standard'];
    public function course(): BelongsTo { return $this->belongsTo(UasTrainingCourse::class, 'training_course_id'); }
    public function complianceLinks(): HasMany { return $this->hasMany(UasTrainingComplianceLink::class, 'training_competency_id'); }
}
