<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionAeronauticalBriefing extends ImmutableEvidence
{
    protected $table = 'uas_mission_briefings';

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'acknowledgement_required' => 'boolean', 'generated_at' => 'immutable_datetime', 'valid_until' => 'immutable_datetime', 'source_dataset_timestamp' => 'immutable_datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MissionBriefingItem::class, 'briefing_id');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(BriefingAcknowledgement::class, 'briefing_id');
    }
}
