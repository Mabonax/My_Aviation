<?php

namespace App\Domains\Uas\Telemetry\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class TelemetryImport extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['raw_csv', 'normalised_flight'];

    protected function casts(): array
    {
        return [
            'normalised_flight' => 'array',
            'propagation_results' => 'array',
            'accepted_at' => 'datetime',
        ];
    }
}
