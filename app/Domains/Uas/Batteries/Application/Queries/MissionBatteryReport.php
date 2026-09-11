<?php

namespace App\Domains\Uas\Batteries\Application\Queries;

use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Batteries\Domain\Models\UasMissionBatteryUsage;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionBatteryReport
{
    public function execute(UasMission $mission): array
    {
        $usages = $mission->batteryUsages()
            ->with(['battery', 'recorder'])
            ->latest('used_at')
            ->get()
            ->map(fn (UasMissionBatteryUsage $usage): array => [
                'id' => $usage->id,
                'battery_uid' => $usage->battery->battery_uid,
                'serial_number' => $usage->battery->serial_number,
                'cycles_added' => $usage->cycles_added,
                'state_of_charge_start' => $usage->state_of_charge_start,
                'state_of_charge_end' => $usage->state_of_charge_end,
                'used_at' => $usage->used_at?->toISOString(),
                'notes' => $usage->notes,
                'recorded_by' => $usage->recorder?->name,
                'battery_health_status' => $usage->battery->health_status,
                'battery_cycle_count' => $usage->battery->cycle_count,
            ])->values()->all();

        return [
            'usages' => $usages,
            'summary' => [
                'total_batteries' => count($usages),
                'cycles_added' => collect($usages)->sum('cycles_added'),
                'attention_required' => collect($usages)->filter(fn (array $usage): bool => in_array($usage['battery_health_status'], ['quarantine', 'expired_cycles', 'retired'], true))->count(),
            ],
            'available_batteries' => UasBattery::query()
                ->with('compatibleAircraft')
                ->where('retirement_status', 'active')
                ->when($mission->uas_aircraft_id, fn ($query) => $query->where(function ($inner) use ($mission) {
                    $inner->whereNull('compatible_uas_aircraft_id')->orWhere('compatible_uas_aircraft_id', $mission->uas_aircraft_id);
                }))
                ->orderBy('battery_uid')
                ->get()
                ->map(fn (UasBattery $battery): array => [
                    'id' => $battery->id,
                    'label' => "{$battery->battery_uid} / {$battery->serial_number}",
                    'health_status' => $battery->health_status,
                    'cycle_count' => $battery->cycle_count,
                    'maximum_cycles' => $battery->maximum_cycles,
                ])->values()->all(),
        ];
    }
}