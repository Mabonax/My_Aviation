<?php

namespace App\Domains\Uas\Batteries\Application\Actions;

use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Batteries\Domain\Models\UasMissionBatteryUsage;
use App\Domains\Uas\Batteries\Domain\Services\BatteryHealthEvaluator;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordMissionBatteryUsage
{
    public function __construct(private readonly BatteryHealthEvaluator $health, private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasMission $mission, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasMissionBatteryUsage
    {
        return DB::transaction(function () use ($mission, $data, $actor, $ipAddress, $userAgent): UasMissionBatteryUsage {
            Gate::forUser($actor)->authorize('update', $mission);
            $battery = UasBattery::query()->findOrFail($data['uas_battery_id']);

            if ($mission->uas_aircraft_id !== null && $battery->compatible_uas_aircraft_id !== null && (int) $mission->uas_aircraft_id !== (int) $battery->compatible_uas_aircraft_id) {
                throw ValidationException::withMessages(['uas_battery_id' => 'Battery is not marked compatible with the mission aircraft.']);
            }

            $usage = UasMissionBatteryUsage::query()->create([
                'uas_mission_id' => $mission->id,
                'uas_battery_id' => $battery->id,
                'recorded_by' => $actor->id,
                'cycles_added' => $data['cycles_added'] ?? 1,
                'state_of_charge_start' => $data['state_of_charge_start'] ?? null,
                'state_of_charge_end' => $data['state_of_charge_end'] ?? null,
                'used_at' => $data['used_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $battery->forceFill([
                'cycle_count' => $battery->cycle_count + $usage->cycles_added,
                'last_used_at' => $usage->used_at?->toDateString(),
            ])->save();
            $battery->forceFill(['health_status' => $this->health->status($battery->refresh())])->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $usage, 'battery_usage.recorded', 'FR-BAT-001', $battery->regulatory_source, null, $usage->getAttributes(), $ipAddress, $userAgent));

            return $usage;
        });
    }
}