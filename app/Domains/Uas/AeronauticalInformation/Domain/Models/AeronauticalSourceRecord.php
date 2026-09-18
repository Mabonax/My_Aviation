<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Models;

class AeronauticalSourceRecord extends ImmutableEvidence
{
    protected $table = 'uas_aeronautical_source_records';

    protected function casts(): array
    {
        return ['raw_payload' => 'array', 'received_at' => 'immutable_datetime', 'issued_at' => 'immutable_datetime', 'effective_from' => 'immutable_datetime', 'effective_until' => 'immutable_datetime'];
    }
}
