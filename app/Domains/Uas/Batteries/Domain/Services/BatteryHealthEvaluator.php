<?php

namespace App\Domains\Uas\Batteries\Domain\Services;

use App\Domains\Uas\Batteries\Domain\Models\UasBattery;

class BatteryHealthEvaluator
{
    public function status(UasBattery $battery): string
    {
        if ($battery->retirement_status !== 'active') {
            return 'retired';
        }

        if (filled($battery->damage_incidents)) {
            return 'quarantine';
        }

        if ($battery->maximum_cycles !== null && $battery->cycle_count >= $battery->maximum_cycles) {
            return 'expired_cycles';
        }

        if ($battery->maximum_cycles !== null && $battery->cycle_count >= (int) floor($battery->maximum_cycles * 0.9)) {
            return 'cycle_watch';
        }

        return 'serviceable';
    }
}