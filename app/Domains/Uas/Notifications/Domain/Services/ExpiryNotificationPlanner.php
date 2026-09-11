<?php

namespace App\Domains\Uas\Notifications\Domain\Services;

use Carbon\CarbonInterface;

class ExpiryNotificationPlanner
{
    public function alertDates(CarbonInterface $expiryDate): array
    {
        return collect([120, 90, 60, 30, 14, 0])
            ->mapWithKeys(fn (int $days): array => [$days => $expiryDate->copy()->subDays($days)->toDateString()])
            ->all();
    }
}
