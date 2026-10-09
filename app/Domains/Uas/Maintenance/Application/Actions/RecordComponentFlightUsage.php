<?php

namespace App\Domains\Uas\Maintenance\Application\Actions;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Support\Facades\DB;

class RecordComponentFlightUsage
{
    /** Called inside the post-flight transaction after locking mission then aircraft. */
    public function execute(UasMission $mission, float $hours): array
    {
        UasAircraft::query()->lockForUpdate()->findOrFail($mission->uas_aircraft_id);
        $components = $mission->aircraft->components()->where('status', 'active')
            ->whereNotNull('installed_at')->where('installed_at', '<=', $mission->actual_takeoff_at)
            ->orderBy('id')->lockForUpdate()->get();
        $ids = [];
        foreach ($components as $component) {
            $alreadyRecorded = DB::table('uas_component_flight_usage')
                ->where('uas_aircraft_component_id', $component->id)->where('uas_mission_id', $mission->id)->exists();
            if (! $alreadyRecorded) {
                DB::table('uas_component_flight_usage')->insert([
                'uas_aircraft_component_id' => $component->id,
                'uas_mission_id' => $mission->id,
                'flight_hours' => $hours,
                'flight_cycles' => 1,
                'recorded_at' => now(),
                ]);
                $component->forceFill([
                    'accumulated_hours' => round((float) $component->accumulated_hours + $hours, 2),
                    'accumulated_cycles' => $component->accumulated_cycles + 1,
                ])->save();
            }
            $ids[] = $component->id;
        }
        return ['component_ids' => $ids, 'flight_hours' => $hours, 'flight_cycles' => 1];
    }
}
