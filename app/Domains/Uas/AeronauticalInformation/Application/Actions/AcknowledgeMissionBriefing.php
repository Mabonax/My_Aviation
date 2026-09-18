<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Actions;

use App\Domains\Uas\AeronauticalInformation\Application\Queries\BriefingReadiness;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalRepositoryInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\BriefingAcknowledgement;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\MissionAeronauticalBriefing;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AcknowledgeMissionBriefing
{
    public function __construct(private readonly AeronauticalRepositoryInterface $repository, private readonly BriefingReadiness $readiness, private readonly AuditAeronauticalEvent $audit) {}

    public function execute(UasMission $mission, MissionAeronauticalBriefing $briefing, User $actor): BriefingAcknowledgement
    {
        abort_unless($briefing->mission_id === $mission->id, 404);
        Gate::forUser($actor)->authorize('acknowledgeBriefing', $mission);

        return DB::transaction(function () use ($mission, $briefing, $actor) {
            $this->repository->lockDataset();
            $mission = UasMission::query()->lockForUpdate()->findOrFail($mission->id);
            Gate::forUser($actor)->authorize('acknowledgeBriefing', $mission);
            $state = $this->readiness->execute($mission);
            if (! $state['current'] || $state['briefing_id'] !== $briefing->id || $state['blockers'] > 0 || in_array($mission->lifecycle_state->value, ['ready_for_flight', 'in_progress', 'completed', 'post_flight_review', 'closed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['briefing' => 'Only the current, valid briefing without hard blockers can be acknowledged before release.']);
            }
            $ack = $briefing->acknowledgements()->firstOrCreate(['user_id' => $actor->id], ['acknowledged_at' => now()]);
            if ($ack->wasRecentlyCreated) {
                $this->audit->execute($briefing, 'aeronautical.briefing.acknowledged', $ack->toArray(), $actor);
            }

            return $ack;
        }, 3);
    }
}
