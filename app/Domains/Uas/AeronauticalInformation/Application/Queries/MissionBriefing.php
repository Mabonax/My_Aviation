<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Queries;

use App\Domains\Uas\AeronauticalInformation\Domain\Models\MissionAeronauticalBriefing;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class MissionBriefing
{
    public function __construct(private readonly BriefingReadiness $readiness) {}

    public function execute(UasMission $mission, User $actor, ?int $revision = null): array
    {
        Gate::forUser($actor)->authorize('view', $mission);
        $query = MissionAeronauticalBriefing::query()->where('mission_id', $mission->id);
        $briefing = $revision ? (clone $query)->where('revision', $revision)->firstOrFail() : (clone $query)->latest('revision')->first();
        $state = $this->readiness->execute($mission);
        $snapshot = $briefing ? [
            'id' => $briefing->id, 'mission_id' => $mission->id, 'revision' => $briefing->revision,
            'status' => $briefing->overall_status, 'generated_at' => $briefing->generated_at->toISOString(), 'generated_by' => $briefing->generated_by,
            'valid_until' => $briefing->valid_until->toISOString(), 'source_dataset_timestamp' => $briefing->source_dataset_timestamp?->toISOString(),
            'source_dataset_hash' => $briefing->source_dataset_hash, 'assessment_version' => $briefing->assessment_version,
            'acknowledgement_required' => $briefing->acknowledgement_required,
            'acknowledged' => $briefing->acknowledgements()->exists(), 'acknowledgements' => $briefing->acknowledgements()->orderBy('id')->get()->toArray(),
            ...$briefing->snapshot, 'items' => $briefing->items()->orderBy('id')->get()->pluck('snapshot')->all(),
        ] : null;

        return ['mission' => $mission->only(['id', 'mission_number', 'location', 'lifecycle_state', 'aeronautical_context']), 'briefing' => $snapshot, 'compliance' => $state,
            'revisions' => $query->orderByDesc('revision')->get(['id', 'revision', 'generated_at', 'overall_status'])->toArray(),
            'permissions' => ['generate' => ! in_array($mission->lifecycle_state->value, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed', 'cancelled'], true) && Gate::forUser($actor)->allows('generateBriefing', $mission), 'acknowledge' => ! in_array($mission->lifecycle_state->value, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed', 'cancelled'], true) && Gate::forUser($actor)->allows('acknowledgeBriefing', $mission) && $state['current'] && $state['blockers'] === 0 && $briefing?->id === $state['briefing_id']],
        ];
    }
}
