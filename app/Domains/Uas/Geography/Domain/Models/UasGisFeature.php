<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasGisFeature extends Model
{
    protected $fillable = [
        'uas_gis_spatial_layer_id',
        'feature_code',
        'name',
        'feature_type',
        'geometry_reference',
        'confidence_score',
        'verification_status',
        'interpretation_notes',
        'evidence_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
        ];
    }

    public function spatialLayer(): BelongsTo
    {
        return $this->belongsTo(UasGisSpatialLayer::class, 'uas_gis_spatial_layer_id');
    }

    public function opportunitiesFindings(): HasMany
    {
        return $this->hasMany(UasGisOpportunityFinding::class, 'uas_gis_feature_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
