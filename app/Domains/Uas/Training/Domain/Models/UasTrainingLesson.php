<?php

namespace App\Domains\Uas\Training\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasTrainingLesson extends Model
{
    protected $fillable = ['training_module_id', 'sequence', 'title', 'lesson_type', 'duration_minutes'];
    public function module(): BelongsTo { return $this->belongsTo(UasTrainingModule::class, 'training_module_id'); }
    public function resources(): HasMany { return $this->hasMany(UasTrainingResource::class, 'training_lesson_id'); }
}
