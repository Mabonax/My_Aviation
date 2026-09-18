<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Queries;

use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalRepositoryInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\MissionAeronauticalBriefing;
use App\Domains\Uas\AeronauticalInformation\Domain\Services\BriefingFingerprint;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class BriefingReadiness
{
    public function __construct(private readonly ProviderHealth $health, private readonly AeronauticalRepositoryInterface $repository, private readonly BriefingFingerprint $fingerprint) {}

    public function execute(UasMission $mission): array
    {
        $briefing = MissionAeronauticalBriefing::query()->where('mission_id', $mission->id)->latest('revision')->first();
        $providers = $this->health->execute($mission);
        $required = collect($providers)->where('required', true);
        $reasons = $required->filter(fn ($p) => ! $p['usable_for_release'])->map(fn ($p) => $p['provider'].': '.$p['reason'])->values()->all();
        $current = $briefing !== null;
        if (! $briefing) {
            $reasons[] = 'Generate a mission briefing before release.';
        }
        if ($briefing && ($briefing->valid_until->lte(now()) || $briefing->source_dataset_hash !== $this->repository->datasetHash() || $briefing->mission_hash !== $this->fingerprint->mission($mission) || $briefing->policy_hash !== $this->fingerprint->policy())) {
            $current = false;
            $reasons[] = 'Briefing expired or the mission, source dataset or assessment policy changed. Generate a new revision.';
        }
        $ack = $briefing?->acknowledgements()->oldest('id')->first();
        $ackRequired = (bool) $briefing?->acknowledgement_required;
        $blockers = max((int) $briefing?->blockers_count, count($reasons));
        if ($briefing?->blockers_count > 0) {
            $reasons = array_values(array_unique([...$reasons, ...($briefing->snapshot['blockers'] ?? [])]));
        }
        if ($ackRequired && ! $ack) {
            $reasons[] = 'Briefing acknowledgement is required before release.';
        }
        $blocking = $blockers > 0 || ! $current || ($ackRequired && ! $ack);
        $status = $blocking ? 'red' : (($briefing?->warnings_count > 0 || $ackRequired) ? 'amber' : 'green');
        $freshness = $required->contains('status', 'unavailable') ? 'unavailable' : ($required->contains('status', 'stale') ? 'stale' : ($required->contains('usable_for_release', false) ? 'unavailable' : 'fresh'));

        return ['status' => $status, 'briefing_id' => $briefing?->id, 'revision' => $briefing?->revision, 'generated_at' => $briefing?->generated_at?->toISOString(), 'valid_until' => $briefing?->valid_until?->toISOString(), 'blockers' => $blockers, 'warnings' => (int) $briefing?->warnings_count, 'blocking' => $blocking, 'current' => $current, 'freshness' => $freshness, 'acknowledgement_required' => $ackRequired, 'acknowledged' => $ack !== null, 'acknowledged_at' => $ack?->acknowledged_at?->toISOString(), 'acknowledged_by' => $ack?->user_id, 'reasons' => $reasons, 'providers' => $providers];
    }
}
