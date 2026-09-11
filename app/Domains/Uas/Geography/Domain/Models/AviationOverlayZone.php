<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AviationOverlayZone extends Model
{
    protected $fillable = ['aviation_overlay_source_id', 'zone_type', 'name', 'identifier', 'status', 'geometry', 'operational_notes'];

    protected function casts(): array
    {
        return ['geometry' => 'array'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AviationOverlaySource::class, 'aviation_overlay_source_id');
    }
}
