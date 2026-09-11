<?php

namespace App\Domains\Uas\Batteries\Application\Queries;

use App\Domains\Uas\Batteries\Domain\Models\UasBattery;

class ListBatteries
{
    public function execute(): array
    {
        return UasBattery::query()
            ->with('compatibleAircraft')
            ->orderBy('battery_uid')
            ->get()
            ->map(fn (UasBattery $battery): array => [
                'id' => $battery->id,
                'battery_uid' => $battery->battery_uid,
                'manufacturer' => $battery->manufacturer,
                'model' => $battery->model,
                'serial_number' => $battery->serial_number,
                'compatible_aircraft' => $battery->compatibleAircraft ? ['id' => $battery->compatibleAircraft->id, 'registration' => $battery->compatibleAircraft->registration] : null,
                'cycle_count' => $battery->cycle_count,
                'maximum_cycles' => $battery->maximum_cycles,
                'health_status' => $battery->health_status,
                'last_used_at' => $battery->last_used_at?->toDateString(),
                'retirement_status' => $battery->retirement_status,
            ])->values()->all();
    }
}