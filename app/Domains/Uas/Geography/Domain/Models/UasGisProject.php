<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasGisProject extends Model
{
    protected $fillable = [
        'project_code',
        'name',
        'project_type',
        'client_or_stakeholder',
        'area_name',
        'location_search_query',
        'centroid_latitude',
        'centroid_longitude',
        'area_boundary',
        'lifecycle_state',
        'source_reference',
        'source_version',
        'data_governance_notes',
        'evidence_required',
        'responsible_role',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'centroid_latitude' => 'decimal:7',
            'centroid_longitude' => 'decimal:7',
            'area_boundary' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function projectMissions(): HasMany
    {
        return $this->hasMany(UasGisProjectMission::class, 'uas_gis_project_id');
    }
}
