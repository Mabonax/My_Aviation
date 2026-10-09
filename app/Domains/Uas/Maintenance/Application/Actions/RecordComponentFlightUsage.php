<?php

namespace App\Domains\Uas\Maintenance\Application\Actions;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordComponentFlightUsage
{
    /** Called inside the post-flight transaction after locking mission then aircraft. */
    public function execute(UasMission $mission, float $hours): array
    {
        UasAircraft::query()->lockForUpdate()->findOrFail($mission->uas_aircraft_id);
        $history = $mission->aircraft->components()->get();
        foreach ($history as $component) {
            $replacement = $history->firstWhere('replaces_component_id', $component->id);
            if (($component->installed_at && $component->installed_at->gt($mission->actual_takeoff_at)
                    && $component->installed_at->lt($mission->actual_landing_at))
                || ($component->removed_at && $component->removed_at->lt($mission->actual_landing_at)
                    && (! $replacement || ! $replacement->installed_at || $replacement->installed_at->gt($mission->actual_takeoff_at)))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'mission' => 'The recorded flight overlaps a component change or an unfilled installation slot; review the flight evidence.',
                ]);
            }
        }
        $components = $mission->aircraft->components()->where(function ($query) {
                $query->where('status', 'active')->orWhere(function ($query) {
                    $query->whereIn('status', ['removed', 'awaiting_replacement'])->whereNotNull('removed_at');
                });
            })
            ->whereNotNull('installed_at')->where('installed_at', '<=', $mission->actual_takeoff_at)
            ->where(fn ($query) => $query->whereNull('removed_at')->orWhere('removed_at', '>=', $mission->actual_landing_at))
            ->orderBy('id')->lockForUpdate()->get();
        $ids = [];
        foreach ($components as $component) {
            $alreadyRecorded = DB::table('uas_component_flight_usage')
                ->where('uas_aircraft_component_id', $component->id)->where('uas_mission_id', $mission->id)->exists();
            if (! $alreadyRecorded) {
                // Match DECIMAL(10, 2) and unsigned integer storage on every database.
                $hourHundredths = (int) round((float) $component->accumulated_hours * 100)
                    + (int) round($hours * 100);
                $cycles = (int) $component->accumulated_cycles + 1;
                if ($hourHundredths > 9999999999 || $cycles > 4294967295) {
                    throw ValidationException::withMessages([
                        'mission' => 'Component usage exceeds the supported lifetime counter range; review the component records before accepting this flight.',
                    ]);
                }
                DB::table('uas_component_flight_usage')->insert([
                'uas_aircraft_component_id' => $component->id,
                'uas_mission_id' => $mission->id,
                'flight_hours' => $hours,
                'flight_cycles' => 1,
                'recorded_at' => now(),
                ]);
                $component->forceFill([
                    'accumulated_hours' => $hourHundredths / 100,
                    'accumulated_cycles' => $cycles,
                ])->save();
            }
            $ids[] = $component->id;
        }
        return ['component_ids' => $ids, 'flight_hours' => $hours, 'flight_cycles' => 1];
    }
}
