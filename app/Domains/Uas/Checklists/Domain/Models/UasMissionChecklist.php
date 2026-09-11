<?php

namespace App\Domains\Uas\Checklists\Domain\Models;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasMissionChecklist extends Model
{
    protected $fillable = [
        'uas_mission_id',
        'uas_checklist_template_id',
        'performed_by',
        'type',
        'checklist_version',
        'performed_at',
        'results',
        'exceptions',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'results' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(UasMission::class, 'uas_mission_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(UasChecklistTemplate::class, 'uas_checklist_template_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}