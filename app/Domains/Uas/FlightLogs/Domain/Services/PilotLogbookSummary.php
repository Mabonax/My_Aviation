<?php

namespace App\Domains\Uas\FlightLogs\Domain\Services;

use App\Domains\Uas\FlightLogs\Domain\Models\PilotLogEntry;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Carbon\CarbonInterface;

class PilotLogbookSummary
{
    public function summarize(UasPilot $pilot, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $query = PilotLogEntry::query()->where('uas_pilot_id', $pilot->id);

        if ($from) {
            $query->whereDate('flight_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('flight_date', '<=', $to->toDateString());
        }

        $entries = $query->get();

        return [
            'pilot_id' => $pilot->id,
            'entry_count' => $entries->count(),
            'total_hours' => round((float) $entries->sum('flight_hours'), 2),
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
        ];
    }
}
