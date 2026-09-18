<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Models;

class MissionBriefingItem extends ImmutableEvidence
{
    protected $table = 'uas_mission_briefing_items';

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
