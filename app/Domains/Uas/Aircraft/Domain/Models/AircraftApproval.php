<?php

namespace App\Domains\Uas\Aircraft\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AircraftApproval extends Model
{
    protected $fillable = ['uas_aircraft_id', 'approval_type', 'approval_number', 'issue_date', 'expiry_date', 'scope', 'restrictions', 'status', 'evidence_references'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date', 'evidence_references' => 'array'];
    }

    public function aircraft(): BelongsTo
    {
        return $this->belongsTo(UasAircraft::class, 'uas_aircraft_id');
    }
}
