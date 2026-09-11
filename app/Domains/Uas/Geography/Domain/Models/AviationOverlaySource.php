<?php

namespace App\Domains\Uas\Geography\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AviationOverlaySource extends Model
{
    protected $fillable = ['name', 'publisher', 'source_url', 'source_version', 'effective_date', 'authoritative', 'usage_notes'];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'authoritative' => 'boolean'];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(AviationOverlayZone::class);
    }
}
