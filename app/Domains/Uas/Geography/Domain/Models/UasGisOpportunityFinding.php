<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasGisOpportunityFinding extends Model
{
    protected $fillable = [
        'uas_gis_feature_id',
        'record_type',
        'category',
        'title',
        'description',
        'significance',
        'recommended_action',
        'priority',
        'status',
        'evidence_reference',
        'due_date',
        'responsible_role',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(UasGisFeature::class, 'uas_gis_feature_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
