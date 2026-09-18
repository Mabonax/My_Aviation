<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasGisProjectMission extends Model
{
    protected $fillable = [
        'uas_gis_project_id',
        'uas_mission_id',
        'mapping_objective',
        'capture_plan',
        'expected_outputs',
        'field_verification_required',
        'evidence_notes',
        'status',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'expected_outputs' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(UasGisProject::class, 'uas_gis_project_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(UasMission::class, 'uas_mission_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(UasGisDataset::class, 'uas_gis_project_mission_id');
    }
}
