<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasGisSpatialLayer extends Model
{
    protected $fillable = [
        'uas_gis_dataset_id',
        'layer_name',
        'layer_type',
        'geometry_type',
        'source_uri',
        'style_metadata',
        'analysis_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'style_metadata' => 'array',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(UasGisDataset::class, 'uas_gis_dataset_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(UasGisFeature::class, 'uas_gis_spatial_layer_id');
    }
}
