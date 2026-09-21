<?php

namespace App\Domains\Uas\Missions\Application\Queries;

use App\Domains\Uas\Aircraft\Application\Queries\AircraftReadinessSummary;
use App\Domains\Uas\Checklists\Domain\Models\UasChecklistTemplate;
use App\Domains\Uas\Checklists\Domain\Models\UasMissionChecklist;
use App\Domains\Uas\Geography\Domain\Services\MissionSpatialRuleEvaluator;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Pilots\Domain\Enums\PilotMedicalStatus;
use App\Domains\Uas\Pilots\Domain\Enums\PilotProfileStatus;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Services\PilotComplianceEvaluator;
use App\Domains\Uas\Operators\Application\Services\PilotOperatorApproval;

class MissionComplianceSummary
{
    public function __construct(
        private readonly AircraftReadinessSummary $aircraftReadiness,
        private readonly PilotComplianceEvaluator $pilotCompliance,
        private readonly PilotOperatorApproval $pilotOperatorApproval,
        private readonly MissionSpatialRuleEvaluator $spatialRules,
        private readonly \App\Domains\Uas\AeronauticalInformation\Application\Queries\BriefingReadiness $aeronauticalReadiness,
    ) {}

    public function execute(UasMission $mission): array
    {
        $mission->loadMissing([
            'operator',
            'pilot',
            'aircraft.catalogueModel.manufacturer',
            'aircraft.registrations',
            'aircraft.approvals',
            'aircraft.defects',
            'aircraft.batteries',
            'checklists',
            'crewMembers',
        ]);

        $spatial = $this->spatialRules->evaluate($mission);
        $aeronautical = $this->aeronauticalReadiness->execute($mission);
        $controls = [
            $this->aircraftControl($mission),
            $this->pilotControl($mission),
            $this->pilotOperatorApprovalControl($mission),
            $this->operatorControl($mission),
            $this->geometryControl($spatial),
            $this->checklistControl($mission),
            $this->approvalControl($mission, $spatial),
            $this->riskControl($mission),
            $this->control('aeronautical_information', 'Aeronautical briefing', $aeronautical['status'], $aeronautical['blocking'] ? 'Aeronautical briefing requires action before release.' : 'Current aeronautical briefing reviewed.', $aeronautical['blocking'], $aeronautical, $aeronautical['reasons'], route('missions.briefing.show', $mission, false), 'internal_policy'),
        ];

        $blockingCount = collect($controls)->where('blocking', true)->count();
        $warningCount = collect($controls)->where('status', 'amber')->count();
        $status = $blockingCount > 0
            ? 'red'
            : ($warningCount > 0 ? 'amber' : 'green');

        return [
            'status' => $status,
            'label' => match ($status) {
                'green' => 'Release ready',
                'amber' => 'Review before release',
                default => 'Release blocked',
            },
            'blocking_count' => $blockingCount,
            'warning_count' => $warningCount,
            'controls' => $controls,
            'aeronautical_information' => $aeronautical,
            'evaluated_at' => now()->toISOString(),
        ];
    }

    public function lightweight(UasMission $mission): array
    {
        // Current source health must never be hidden behind a stored release result.
        return \Illuminate\Support\Arr::only($this->execute($mission), ['status', 'label', 'blocking_count', 'warning_count', 'aeronautical_information']);
    }

    private function aircraftControl(UasMission $mission): array
    {
        if (! $mission->aircraft) {
            return $this->control('aircraft_readiness', 'Aircraft', 'red', 'No aircraft is assigned to the mission.', true, basis: 'regulatory');
        }

        $summary = $this->aircraftReadiness->execute($mission->aircraft);

        return $this->control(
            'aircraft_readiness',
            'Aircraft',
            $summary['status'],
            $summary['label'],
            $summary['status'] === 'red',
            [
                'aircraft_id' => $mission->aircraft->id,
                'registration' => $mission->aircraft->registration,
                'aircraft_readiness' => $summary,
            ],
            $summary['blocking_reasons'] ?: $summary['review_reasons'],
            route('aircraft.show', $mission->aircraft, false),
            'regulatory',
        );
    }

    private function pilotControl(UasMission $mission): array
    {
        if (! $mission->pilot) {
            return $this->control('pilot_readiness', 'Pilot', 'red', 'No pilot is assigned to the mission.', true, basis: 'regulatory');
        }

        if ($mission->pilot->profile_status !== PilotProfileStatus::Active) {
            return $this->control('pilot_readiness', 'Pilot', 'red', 'Assigned pilot profile is not active.', true, basis: 'regulatory');
        }

        if ($mission->pilot->medical_status !== PilotMedicalStatus::Valid) {
            return $this->control('pilot_readiness', 'Pilot', 'red', 'Assigned pilot medical status is not valid.', true, basis: 'regulatory');
        }

        $certificate = PilotCertificate::query()
            ->where('uas_pilot_id', $mission->pilot->id)
            ->latest('expiry_date')
            ->first();
        $rpcState = $this->pilotCompliance->rpcState($certificate);

        return match ($rpcState) {
            'valid' => $this->control('pilot_readiness', 'Pilot', 'green', 'Pilot RPC, profile and medical status are current.', false, $this->pilotEvidence($certificate), basis: 'regulatory'),
            'expiring' => $this->control('pilot_readiness', 'Pilot', 'amber', 'Pilot RPC is inside the revalidation window.', false, $this->pilotEvidence($certificate), basis: 'regulatory'),
            default => $this->control('pilot_readiness', 'Pilot', 'red', "Pilot RPC is {$rpcState}.", true, $this->pilotEvidence($certificate), basis: 'regulatory'),
        };
    }


    private function pilotOperatorApprovalControl(UasMission $mission): array
    {
        if (! $mission->operator || ! $mission->pilot) {
            return $this->control(
                'pilot_operator_approval',
                'Pilot / Operator approval',
                'red',
                'A current pilot/operator operational approval cannot be established.',
                true,
                basis: 'internal_policy',
            );
        }

        $assignment = $this->pilotOperatorApproval->activeAssignment($mission->operator->id, $mission->pilot);

        if (! $assignment) {
            return $this->control(
                'pilot_operator_approval',
                'Pilot / Operator approval',
                'red',
                'The assigned pilot is not currently approved to operate for this operator.',
                true,
                ['operator_id' => $mission->operator->id, 'pilot_id' => $mission->pilot->id],
                basis: 'internal_policy',
            );
        }

        return $this->control(
            'pilot_operator_approval',
            'Pilot / Operator approval',
            'green',
            'Pilot operational approval for the assigned operator is current.',
            false,
            [
                'operator_id' => $mission->operator->id,
                'pilot_id' => $mission->pilot->id,
                'assignment_id' => $assignment->id,
                'approved_from' => $assignment->approved_from?->toDateString(),
                'approved_until' => $assignment->approved_until?->toDateString(),
            ],
            basis: 'internal_policy',
        );
    }

    private function operatorControl(UasMission $mission): array
    {
        if (! $mission->operator) {
            return $this->control('operator_compliance', 'Operator', 'red', 'No operator is assigned to the mission.', true, basis: 'regulatory');
        }

        if ($mission->operator->status !== 'active') {
            return $this->control(
                'operator_compliance',
                'Operator',
                'red',
                "Operator status {$mission->operator->status} blocks release.",
                true,
                ['operator_id' => $mission->operator->id, 'status' => $mission->operator->status],
                basis: 'regulatory',
            );
        }

        return $this->control(
            'operator_compliance',
            'Operator',
            'green',
            'Operator authority record is active.',
            false,
            ['operator_id' => $mission->operator->id, 'status' => $mission->operator->status],
            [],
            route('operators.show', $mission->operator, false),
            'regulatory',
        );
    }

    private function geometryControl(array $spatial): array
    {
        return match ($spatial['state']) {
            'authorisation_required' => $this->control('geometry_airspace', 'Airspace / Geometry', 'red', $spatial['summary'], true, ['spatial_review' => $spatial], collect($spatial['matches'])->pluck('message')->all(), basis: 'regulatory'),
            'review_required', 'insufficient_geometry' => $this->control('geometry_airspace', 'Airspace / Geometry', 'amber', $spatial['summary'], false, ['spatial_review' => $spatial], collect($spatial['matches'])->pluck('message')->all(), basis: 'internal_policy'),
            default => $this->control('geometry_airspace', 'Airspace / Geometry', 'green', $spatial['summary'], false, ['spatial_review' => $spatial], basis: 'regulatory'),
        };
    }

    private function checklistControl(UasMission $mission): array
    {
        $template = UasChecklistTemplate::query()
            ->where('type', 'pre_flight')
            ->where('active', true)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        if (! $template) {
            return $this->control('pre_flight_checklist', 'Checklist', 'amber', 'No active pre-flight checklist template is configured.', false, basis: 'internal_policy');
        }

        $latest = $mission->checklists
            ->where('type', 'pre_flight')
            ->sortByDesc('performed_at')
            ->first();

        if (! $latest) {
            return $this->control('pre_flight_checklist', 'Checklist', 'red', 'Required pre-flight checklist has not been completed.', true, ['template_version' => $template->version], basis: 'internal_policy');
        }

        if ($latest->state === 'blocked') {
            return $this->control('pre_flight_checklist', 'Checklist', 'red', 'Pre-flight checklist contains a blocking failed item.', true, $this->checklistEvidence($latest), basis: 'internal_policy');
        }

        if ($latest->state === 'completed_with_exceptions') {
            return $this->control('pre_flight_checklist', 'Checklist', 'amber', 'Pre-flight checklist is complete with recorded exceptions.', false, $this->checklistEvidence($latest), basis: 'internal_policy');
        }

        return $this->control('pre_flight_checklist', 'Checklist', 'green', 'Pre-flight checklist is complete.', false, $this->checklistEvidence($latest), basis: 'internal_policy');
    }

    private function approvalControl(UasMission $mission, array $spatial): array
    {
        $approvals = collect($mission->approvals ?? []);
        $mandatory = $spatial['state'] === 'authorisation_required';

        if ($mandatory && $approvals->isEmpty()) {
            return $this->control('mission_approvals', 'Approvals', 'red', 'Mandatory airspace approval evidence is missing.', true, ['mandatory' => true], basis: 'regulatory');
        }

        if ($approvals->isEmpty()) {
            return $this->control('mission_approvals', 'Approvals', 'green', 'No additional mission approval evidence is currently required.', false, ['mandatory' => false], basis: 'regulatory');
        }

        $approved = $approvals->contains(fn (array $approval): bool => in_array(strtolower((string) ($approval['status'] ?? '')), ['approved', 'valid', 'issued'], true));
        $pending = $approvals->contains(fn (array $approval): bool => in_array(strtolower((string) ($approval['status'] ?? '')), ['pending', 'review', 'submitted'], true));

        if ($mandatory && ! $approved) {
            return $this->control('mission_approvals', 'Approvals', 'red', 'Mandatory approval evidence is present but not approved.', true, ['mandatory' => true, 'approvals' => $approvals->values()->all()], basis: 'regulatory');
        }

        if ($pending) {
            return $this->control('mission_approvals', 'Approvals', 'amber', 'One or more mission approvals are still pending review.', false, ['mandatory' => $mandatory, 'approvals' => $approvals->values()->all()], basis: 'regulatory');
        }

        return $this->control('mission_approvals', 'Approvals', 'green', 'Mission approval evidence is acceptable for current controls.', false, ['mandatory' => $mandatory, 'approvals' => $approvals->values()->all()], basis: 'regulatory');
    }

    private function riskControl(UasMission $mission): array
    {
        if (blank($mission->risk_assessment)) {
            return $this->control('risk_assessment', 'Risk assessment', 'amber', 'Risk assessment is not yet captured.', false, basis: 'internal_policy');
        }

        return $this->control('risk_assessment', 'Risk assessment', 'green', 'Risk assessment evidence is captured.', false, basis: 'internal_policy');
    }

    private function pilotEvidence(?PilotCertificate $certificate): array
    {
        return [
            'certificate_id' => $certificate?->id,
            'certificate_number' => $certificate?->certificate_number,
            'expiry_date' => $certificate?->expiry_date?->toDateString(),
        ];
    }

    private function checklistEvidence(UasMissionChecklist $checklist): array
    {
        return [
            'checklist_id' => $checklist->id,
            'checklist_version' => $checklist->checklist_version,
            'state' => $checklist->state,
            'performed_at' => $checklist->performed_at?->toISOString(),
        ];
    }

    private function control(string $key, string $label, string $status, string $summary, bool $blocking, array $details = [], array $reasons = [], ?string $actionHref = null, string $basis = 'internal_policy'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'basis' => $basis,
            'summary' => $summary,
            'blocking' => $blocking,
            'details' => $details,
            'reasons' => $reasons,
            'action_href' => $actionHref,
        ];
    }
}
