<?php

namespace App\Domains\Uas\Training\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasTrainingModule extends Model
{
    protected $fillable = ['training_course_id', 'sequence', 'title', 'summary'];
    public function course(): BelongsTo { return $this->belongsTo(UasTrainingCourse::class, 'training_course_id'); }
    public function lessons(): HasMany { return $this->hasMany(UasTrainingLesson::class, 'training_module_id'); }
}
