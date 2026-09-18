<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Models;

class BriefingAcknowledgement extends ImmutableEvidence
{
    protected $table = 'uas_mission_briefing_acknowledgements';

    protected function casts(): array
    {
        return ['acknowledged_at' => 'immutable_datetime'];
    }
}
