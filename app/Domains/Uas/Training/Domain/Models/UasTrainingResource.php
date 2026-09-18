<?php

namespace App\Domains\Uas\Training\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasTrainingResource extends Model
{
    protected $fillable = ['training_lesson_id', 'title', 'resource_type', 'reference'];
    public function lesson(): BelongsTo { return $this->belongsTo(UasTrainingLesson::class, 'training_lesson_id'); }
}
