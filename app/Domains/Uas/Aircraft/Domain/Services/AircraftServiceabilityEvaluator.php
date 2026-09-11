<?php

namespace App\Domains\Uas\Aircraft\Domain\Services;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;

class AircraftServiceabilityEvaluator
{
    private const BLOCKED_STATES = ['grounded', 'unserviceable', 'suspended', 'de_registered', 'sold', 'flight_restricted'];

    public function state(UasAircraft $aircraft): string
    {
        return $aircraft->operational_status;
    }

    public function mayBeAssignedToReleasedFlight(UasAircraft $aircraft): bool
    {
        return ! in_array($aircraft->operational_status, self::BLOCKED_STATES, true);
    }
}
