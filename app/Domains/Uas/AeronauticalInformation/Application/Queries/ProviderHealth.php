<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Queries;

use App\Domains\Uas\AeronauticalInformation\Application\ProviderRegistry;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use App\Domains\Uas\Geography\Domain\Services\RegionalGeometryOverlap;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Carbon\CarbonImmutable;
use Throwable;

class ProviderHealth
{
    public function __construct(private readonly ProviderRegistry $registry, private readonly RegionalGeometryOverlap $geometry) {}

    public function execute(?UasMission $mission = null): array
    {
        $required = config('aeronautical.required_providers', []);
        $keys = array_unique([...array_keys(config('aeronautical.providers')), ...$required]);
        // Two bounded queries for all providers, independent of provider count/history size.
        $outcomes = ProviderSync::query()->whereIn('id', ProviderSync::query()->selectRaw('MAX(id)')->whereIn('status', ['succeeded', 'failed'])->groupBy('provider'))->get()->keyBy('provider');
        $successes = ProviderSync::query()->whereIn('id', ProviderSync::query()->selectRaw('MAX(id)')->where('status', 'succeeded')->groupBy('provider'))->get()->keyBy('provider');
        $health = [];
        foreach ($keys as $key) {
            $sync = $outcomes->get($key);
            $success = $successes->get($key);
            $provider = null;
            $operational = false;
            $state = null;
            if (! config("aeronautical.providers.{$key}.adapter")) {
                $state = 'unconfigured';
            } elseif (config("aeronautical.providers.{$key}.enabled", true) !== true) {
                $state = 'unavailable';
            } else {
                try {
                    $provider = $this->registry->resolve($key);
                    $operational = $this->registry->operationallyApproved($provider);
                } catch (Throwable) {
                    $state = 'authority_insufficient';
                }
            }
            $deadline = $success?->dataset_timestamp?->addMinutes(max(1, (int) config("aeronautical.providers.{$key}.max_age_minutes", 60)));
            $fresh = $deadline && $deadline->gt(now()) && $success->dataset_timestamp->lte(now());
            $authority = $operational && $success?->usable_for_release && $success->approval_fingerprint === $this->registry->approvalFingerprint($provider);
            $coverage = $success?->dataset_mode === 'full_snapshot' && $mission && $this->covers($success->coverage ?? [], $mission);
            $state ??= match (true) {
                $sync?->status === 'failed' => 'sync_failed',
                ! $success => 'unavailable',
                ! $fresh => 'stale',
                ! $authority => 'authority_insufficient',
                $mission !== null && ! $coverage => 'coverage_insufficient',
                default => 'healthy',
            };
            $reason = match ($state) {
                'unconfigured' => 'Official operational provider is not configured.',
                'unavailable' => 'Provider is disabled or has no successful synchronization.',
                'sync_failed' => 'The latest provider synchronization failed. Retry or contact the integration administrator.',
                'stale' => 'The source dataset timestamp is outside the allowed freshness window.',
                'authority_insufficient' => 'Operational approval evidence or required provider capabilities are missing or changed.',
                'coverage_insufficient' => 'A complete snapshot covering the mission area, NOTAM types and planned time is required. Incremental updates alone cannot establish completeness.',
                default => $mission ? 'Approved source is current and covers the mission.' : 'Approved source is current. Coverage must be checked against a mission.',
            };
            $usable = $state === 'healthy' && $coverage && $authority;
            // Preserve V1 freshness vocabulary; add health_status for the specific backend diagnosis.
            $status = in_array($state, ['unconfigured', 'unavailable', 'sync_failed'], true) ? 'unavailable' : ($state === 'stale' ? 'stale' : 'fresh');
            $health[] = ['provider' => $key, 'required' => in_array($key, $required, true), 'status' => $status,
                'health_status' => $state, 'reason' => $reason, 'operational' => (bool) $authority,
                'usable_for_release' => (bool) $usable, 'operational_authority' => (bool) $authority,
                'coverage_complete' => (bool) $coverage, 'dataset_mode' => $success?->dataset_mode ?? 'unknown',
                'dataset_timestamp' => $success?->dataset_timestamp?->toISOString(), 'last_successful_sync_at' => $success?->completed_at?->toISOString(),
                'last_sync_status' => $sync?->status, 'valid_until' => $deadline?->toISOString(),
                'error' => $sync?->status === 'failed' ? 'Provider synchronization failed. See source-health reason.' : null,
                'capabilities' => $provider?->capabilities()->toArray()];
        }
        if ($required === []) {
            $health[] = ['provider' => 'required_source_unconfigured', 'required' => true, 'status' => 'unavailable',
                'health_status' => 'unconfigured', 'reason' => 'No required operational source is configured.',
                'usable_for_release' => false, 'operational_authority' => false, 'operational' => false, 'coverage_complete' => false,
                'dataset_mode' => 'unknown', 'dataset_timestamp' => null, 'last_successful_sync_at' => null,
                'last_sync_status' => null, 'valid_until' => null, 'error' => null, 'capabilities' => null];
        }

        return $health;
    }

    private function covers(array $coverage, UasMission $mission): bool
    {
        if (! is_array($coverage['information_types'] ?? null) || ! is_array($coverage['bbox'] ?? null) || ($coverage['complete'] ?? false) !== true || ! in_array('NOTAM', $coverage['information_types'] ?? [], true) || count($coverage['bbox'] ?? []) !== 4 || ! $mission->planned_start_at || ! $mission->planned_end_at) {
            return false;
        }
        try {
            if (! isset($coverage['valid_from'], $coverage['valid_until']) || CarbonImmutable::parse($coverage['valid_from'])->gt($mission->planned_start_at) || CarbonImmutable::parse($coverage['valid_until'])->lt($mission->planned_end_at)) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }
        [$west, $south, $east, $north] = $coverage['bbox'];
        if (! is_numeric($west) || ! is_numeric($south) || ! is_numeric($east) || ! is_numeric($north) || $west < -180 || $east > 180 || $south < -90 || $north > 90 || $west >= $east || $south >= $north) {
            return false;
        }
        $points = $this->geometry->points($mission);
        if ($points === []) {
            return false;
        }
        foreach ($points as $point) {
            $lat = $point['latitude'];
            $lon = $point['longitude'];
            if (abs($lat) > 75) {
                return false;
            }
            $padding = max(0, (float) $mission->flight_radius_m) + max(0, (float) config('aeronautical.horizontal_buffer_m'));
            $latPad = $padding / 110500;
            $lonPad = $latPad / max(0.01, cos(deg2rad($lat)));
            if ($lon - $lonPad < $west || $lon + $lonPad > $east || $lat - $latPad < $south || $lat + $latPad > $north) {
                return false;
            }
        }

        return true;
    }
}
