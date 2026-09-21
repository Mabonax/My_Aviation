<?php

namespace App\Domains\Uas\Missions\Application\Actions;

use App\Domains\Uas\AeronauticalInformation\Application\Actions\AuditAeronauticalEvent;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalRepositoryInterface;
use App\Domains\Uas\Missions\Application\Queries\MissionComplianceSummary;
use App\Domains\Uas\Operators\Application\Services\PilotOperatorApproval;
use App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Missions\Domain\Services\MissionLifecycle;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReleaseMission
{
    public function __construct(
        private readonly MissionComplianceSummary $compliance,
        private readonly MissionLifecycle $lifecycle,
        private readonly RecordAuditEntry $recordAuditEntry,
        private readonly PilotOperatorApproval $pilotApproval,
    ) {}

    public function execute(UasMission $mission, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasMission
    {
        $blockedSummary = null;
        try {
            return DB::transaction(function () use ($mission, $actor, $ipAddress, $userAgent, &$blockedSummary): UasMission {
                app(AeronauticalRepositoryInterface::class)->lockDataset();
                $mission = UasMission::query()->lockForUpdate()->findOrFail($mission->id);
                Gate::forUser($actor)->authorize('update', $mission);

                if ($mission->uas_operator_id && $mission->uas_pilot_id
                    && ! $this->pilotApproval->isApproved((int) $mission->uas_operator_id, (int) $mission->uas_pilot_id)) {
                    throw ValidationException::withMessages([
                        'uas_pilot_id' => 'Mission release is blocked because the assigned pilot is no longer approved to operate for this operator.',
                    ]);
                }

                $previous = $mission->getAttributes();
                $summary = $this->compliance->execute($mission);

                if ($summary['status'] === 'red') {
                    $blockedSummary = $summary;
                    throw ValidationException::withMessages([
                        'mission' => 'Mission release is blocked by compliance readiness controls.',
                    ]);
                }

                if (! $this->lifecycle->canTransition($mission->lifecycle_state, MissionLifecycleState::ReadyForFlight)) {
                    throw ValidationException::withMessages([
                        'lifecycle_state' => 'Mission must reach the approved lifecycle state before release.',
                    ]);
                }

                $releasedAt = now()->toISOString();
                $releaseEvidence = [
                    'mission_id' => $mission->id,
                    'released_by' => $actor->id,
                    'released_at' => $releasedAt,
                    'compliance_status' => $summary['status'],
                    'blocking_count' => $summary['blocking_count'],
                    'warning_count' => $summary['warning_count'],
                    'aircraft_readiness_status' => data_get($summary, 'controls.0.details.aircraft_readiness.status'),
                    'control_snapshot' => $summary['controls'],
                    'aeronautical_briefing_id' => $summary['aeronautical_information']['briefing_id'],
                    'aeronautical_briefing_revision' => $summary['aeronautical_information']['revision'],
                    'aeronautical_acknowledged_by' => $summary['aeronautical_information']['acknowledged_by'],
                    'aeronautical_acknowledged_at' => $summary['aeronautical_information']['acknowledged_at'],
                ];

                $mission->forceFill([
                    'lifecycle_state' => MissionLifecycleState::ReadyForFlight,
                    'release_gate_state' => $summary['status'],
                    'release_gate_results' => [
                        ...$summary,
                        'release_evidence' => $releaseEvidence,
                    ],
                    'updated_by' => $actor->id,
                ])->save();

                $this->recordAuditEntry->execute(new AuditEntryData(
                    actor: $actor,
                    auditable: $mission,
                    action: 'mission.released',
                    requirementId: 'FR-MIS-002',
                    regulatorySource: $mission->regulatory_source,
                    previousValues: $previous,
                    newValues: [
                        ...$mission->getAttributes(),
                        'release_evidence' => $releaseEvidence,
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                ));

                return $mission;
            }, 3);
        } catch (ValidationException $exception) {
            if (($blockedSummary['aeronautical_information']['blocking'] ?? false) === true) {
                $state = $blockedSummary['aeronautical_information'];
                $event = $state['freshness'] !== 'fresh' || ! $state['current'] ? 'aeronautical.release.stale_or_unavailable' : 'aeronautical.release.blocked';
                app(AuditAeronauticalEvent::class)->execute($mission, $event, $state, $actor);
            }
            throw $exception;
        }
    }
}
