<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Actions;

use App\Domains\Uas\AeronauticalInformation\Application\Queries\AeronauticalItemPresenter;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\ProviderHealth;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalRepositoryInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\MissionAeronauticalBriefing;
use App\Domains\Uas\AeronauticalInformation\Domain\Services\BriefingFingerprint;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GenerateMissionBriefing
{
    public function __construct(private readonly AeronauticalRepositoryInterface $repository, private readonly AssessAeronauticalInformationForMission $assess, private readonly ProviderHealth $health, private readonly BriefingFingerprint $fingerprint, private readonly AuditAeronauticalEvent $audit) {}

    public function execute(UasMission $mission, User $actor, ?array $context = null): MissionAeronauticalBriefing
    {
        Gate::forUser($actor)->authorize('generateBriefing', $mission);

        return DB::transaction(function () use ($mission, $actor, $context) {
            $this->repository->lockDataset();
            $mission = UasMission::query()->lockForUpdate()->findOrFail($mission->id);
            Gate::forUser($actor)->authorize('generateBriefing', $mission);
            if (in_array($mission->lifecycle_state->value, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['briefing' => 'Release evidence is sealed. A released or closed mission cannot replace its briefing.']);
            }
            if ($context !== null) {
                if (isset($context['minimum_altitude_ft']) && $context['minimum_altitude_ft'] > $mission->maximum_altitude_ft) {
                    throw ValidationException::withMessages(['aeronautical_context.minimum_altitude_ft' => 'Minimum altitude cannot exceed the mission maximum.']);
                }
                $mission->update(['aeronautical_context' => $context]);
            }
            $providers = $this->health->execute($mission);
            $required = collect($providers)->where('required', true);
            $blockers = $required->filter(fn ($p) => ! $p['usable_for_release'])->map(fn ($p) => $p['provider'].': '.$p['reason'])->values()->all();
            if ($blockers !== []) {
                $this->audit->execute($mission, 'aeronautical.briefing.source_health_blocked', ['providers' => $required->map(fn ($p) => ['provider' => $p['provider'], 'health_status' => $p['health_status']])->values()->all()], $actor);
            }
            $items = [];
            foreach ($this->repository->currentItems() as $item) {
                $assessment = $this->assess->execute($mission, $item);
                if (! $assessment['relevant']) {
                    continue;
                }
                $items[] = [...AeronauticalItemPresenter::toArray($item), ...$assessment];
                if ($assessment['release_effect'] === 'block') {
                    $blockers[] = $item->source_identifier.': '.$assessment['reason'];
                }
            }
            $warnings = collect($items)->whereIn('severity', ['warning', 'advisory'])->count();
            $ackRequired = collect($items)->contains('release_effect', 'acknowledge');
            $validUntil = CarbonImmutable::now()->addMinutes(max(1, (int) config('aeronautical.briefing_valid_minutes')));
            foreach ($required as $p) {
                if ($p['valid_until'] && CarbonImmutable::parse($p['valid_until'])->lt($validUntil)) {
                    $validUntil = CarbonImmutable::parse($p['valid_until']);
                }
            }
            $revision = (int) MissionAeronauticalBriefing::query()->where('mission_id', $mission->id)->max('revision') + 1;
            $briefing = MissionAeronauticalBriefing::query()->create([
                'mission_id' => $mission->id, 'revision' => $revision, 'generated_at' => now(), 'generated_by' => $actor->id,
                'valid_until' => $validUntil, 'source_dataset_timestamp' => $required->pluck('dataset_timestamp')->filter()->sort()->first(),
                'source_dataset_hash' => $this->repository->datasetHash(), 'mission_hash' => $this->fingerprint->mission($mission), 'policy_hash' => $this->fingerprint->policy(),
                'assessment_version' => config('aeronautical.assessment_version'), 'overall_status' => $blockers !== [] ? 'red' : ($warnings > 0 ? 'amber' : 'green'),
                'blockers_count' => count($blockers), 'warnings_count' => $warnings, 'acknowledgement_required' => $ackRequired,
                'snapshot' => ['mission' => $mission->only(['id', 'mission_number', 'location', 'latitude', 'longitude', 'takeoff_point', 'landing_point', 'mission_polygon', 'flight_route', 'flight_radius_m', 'planned_start_at', 'planned_end_at', 'maximum_altitude_ft', 'aeronautical_context']), 'providers' => $providers, 'blockers' => $blockers, 'warnings' => collect($items)->whereIn('severity', ['warning', 'advisory'])->pluck('reason')->all(), 'summary' => ['items' => count($items), 'blockers' => count($blockers), 'warnings' => $warnings, 'advisories' => collect($items)->where('severity', 'advisory')->count()], 'empty_data_message' => count($items) === 0 ? ($blockers === [] ? 'No applicable records found in the complete, current operational dataset.' : 'No applicable records are available; required source availability or coverage is unresolved.') : null],
            ]);
            foreach ($items as $item) {
                $briefing->items()->create(['aeronautical_information_item_id' => $item['id'], 'snapshot' => $item]);
            }
            $this->audit->execute($briefing, $revision === 1 ? 'aeronautical.briefing.generated' : 'aeronautical.briefing.regenerated', ['mission_id' => $mission->id, 'revision' => $revision, 'source_dataset_hash' => $briefing->source_dataset_hash, 'status' => $briefing->overall_status], $actor);

            return $briefing;
        }, 3);
    }
}
