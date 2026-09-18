<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasGisDataset extends Model
{
    protected $fillable = [
        'uas_gis_project_mission_id',
        'dataset_code',
        'title',
        'dataset_type',
        'capture_source',
        'storage_uri',
        'checksum',
        'coordinate_reference_system',
        'resolution_cm',
        'captured_at',
        'processed_at',
        'processing_status',
        'quality_status',
        'provenance_notes',
        'evidence_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'resolution_cm' => 'decimal:2',
            'captured_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function projectMission(): BelongsTo
    {
        return $this->belongsTo(UasGisProjectMission::class, 'uas_gis_project_mission_id');
    }

    public function layers(): HasMany
    {
        return $this->hasMany(UasGisSpatialLayer::class, 'uas_gis_dataset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
