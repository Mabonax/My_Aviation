<?php

namespace App\Domains\Uas\Checklists\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasChecklistTemplate extends Model
{
    protected $fillable = [
        'type',
        'name',
        'version',
        'effective_date',
        'active',
        'items',
        'regulatory_source',
        'regulatory_source_version',
        'regulatory_effective_date',
        'regulatory_applicability',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'active' => 'boolean',
            'items' => 'array',
            'regulatory_effective_date' => 'date',
        ];
    }

    public function missionChecklists(): HasMany
    {
        return $this->hasMany(UasMissionChecklist::class, 'uas_checklist_template_id');
    }
}