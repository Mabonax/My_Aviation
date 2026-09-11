<?php

namespace App\Domains\Uas\Aircraft\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AircraftRegistration extends Model
{
    protected $fillable = ['uas_aircraft_id', 'registration_number', 'lifecycle_state', 'issue_date', 'expiry_date', 'evidence_references'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date', 'evidence_references' => 'array'];
    }

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'uas_aircraft_id');
    }
}
