<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Actions;

use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\Geography\Domain\Services\RegionalGeometryOverlap;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class AssessAeronauticalInformationForMission
{
    public function __construct(private readonly RegionalGeometryOverlap $geometry) {}

    public function execute(UasMission $mission, AeronauticalInformationItem $item): array
    {
        $horizontal = $this->geometry->evaluate($mission, $item->geometry_json, $item->latitude !== null ? (float) $item->latitude : null, $item->longitude !== null ? (float) $item->longitude : null, (float) $item->radius_nm * 1852, (float) config('aeronautical.horizontal_buffer_m'));
        $context = $mission->aeronautical_context ?? [];
        $fir = $item->fir_code && in_array($item->fir_code, $context['fir_codes'] ?? [], true);
        $aerodrome = $item->aerodrome_code && in_array($item->aerodrome_code, $context['aerodrome_codes'] ?? [], true);
        if ($horizontal === null && ($fir || $aerodrome)) {
            $horizontal = true;
        }
        $vertical = $this->vertical($mission, $item);
        $temporal = $this->temporal($mission, $item);
        $relevant = ! in_array(false, [$horizontal, $vertical, $temporal], true) && $item->status === 'active' && $item->superseded_at === null;
        $uncertain = in_array(null, [$horizontal, $vertical, $temporal], true);
        $hazard = $item->interpretation['hazard'] ?? 'unknown';
        $severity = 'info';
        $effect = 'none';
        if ($relevant) {
            if ($item->usable_for_release && ($hazard === 'restriction' || ($hazard === 'unknown' && in_array($item->information_type, ['NOTAM', 'SIGMET'], true)))) {
                $severity = 'critical';
                $effect = 'block';
            } elseif ($item->usable_for_release && $uncertain && in_array($item->information_type, ['NOTAM', 'SIGMET'], true)) {
                $severity = 'warning';
                $effect = 'block';
            } elseif (! $item->usable_for_release || $uncertain || $hazard === 'warning' || $hazard === 'unknown') {
                $severity = $hazard === 'warning' || $uncertain ? 'warning' : 'advisory';
                $effect = 'acknowledge';
            }
        }
        $reason = ! $relevant ? 'Outside the mission spatial, vertical or time scope, or superseded/cancelled.'
            : ($uncertain ? 'Potentially applicable; incomplete geometry, altitude datum or validity requires review.' : 'Mission geometry, altitude and planned time overlap the information scope.');
        if (! $item->usable_for_release) {
            $reason .= ' Reference only; this source cannot establish operational clearance.';
        }

        return ['mission_id' => $mission->id, 'aeronautical_information_item_id' => $item->id, 'horizontal_overlap' => $horizontal, 'vertical_overlap' => $vertical, 'temporal_overlap' => $temporal, 'fir_match' => (bool) $fir, 'aerodrome_match' => (bool) $aerodrome, 'relevant' => $relevant, 'severity' => $severity, 'release_effect' => $effect, 'reason' => $reason, 'assessed_at' => now()->toISOString(), 'assessment_version' => config('aeronautical.assessment_version')];
    }

    private function temporal(UasMission $mission, AeronauticalInformationItem $item): ?bool
    {
        if (! $mission->planned_start_at || ! $mission->planned_end_at || $mission->planned_end_at->lt($mission->planned_start_at)) {
            return null;
        }
        if ($item->effective_from?->gt($mission->planned_end_at) || $item->effective_until?->lt($mission->planned_start_at)) {
            return false;
        }
        if (! $item->effective_from || (! $item->effective_until && ! $item->permanent) || ! empty($item->interpretation['schedule'])) {
            return null;
        }

        return true;
    }

    private function vertical(UasMission $mission, AeronauticalInformationItem $item): ?bool
    {
        $datum = $mission->aeronautical_context['altitude_reference'] ?? null;
        if (! in_array($datum, ['AGL', 'AMSL'], true) || $mission->maximum_altitude_ft === null) {
            return null;
        }
        $minimum = $datum === 'AGL' ? 0 : ($mission->aeronautical_context['minimum_altitude_ft'] ?? null);
        $lower = $this->feet($item->lower_limit_value, $item->lower_limit_unit, $item->lower_limit_reference, $datum);
        $upper = $item->upper_limit_reference === 'UNL' ? INF : $this->feet($item->upper_limit_value, $item->upper_limit_unit, $item->upper_limit_reference, $datum);
        $buffer = max(0, (float) config('aeronautical.vertical_buffer_ft'));
        if ($lower !== null && $lower > $mission->maximum_altitude_ft + $buffer) {
            return false;
        }
        if ($upper !== null && $minimum !== null && $upper < $minimum - $buffer) {
            return false;
        }

        return $lower !== null && $upper !== null && $minimum !== null ? true : null;
    }

    private function feet(mixed $value, ?string $unit, ?string $reference, string $datum): ?float
    {
        if ($reference === 'SFC' && $datum === 'AGL') {
            return 0;
        }
        if ($reference !== $datum || $value === null || ! in_array($unit, ['FT', 'M'], true)) {
            return null;
        }

        return (float) $value * ($unit === 'M' ? 3.280839895 : 1);
    }
}
