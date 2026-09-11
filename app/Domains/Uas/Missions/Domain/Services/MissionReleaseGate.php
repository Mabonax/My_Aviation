<?php

namespace App\Domains\Uas\Missions\Domain\Services;

use App\Domains\Uas\Aircraft\Domain\Services\AircraftServiceabilityEvaluator;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Pilots\Domain\Enums\PilotMedicalStatus;
use App\Domains\Uas\Pilots\Domain\Enums\PilotProfileStatus;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Pilots\Domain\Services\PilotComplianceEvaluator;

class MissionReleaseGate
{
    public function __construct(
        private readonly PilotComplianceEvaluator $pilotCompliance,
        private readonly AircraftServiceabilityEvaluator $aircraftServiceability,
    ) {}

    public function evaluate(UasMission $mission): array
    {
        $mission->loadMissing(['pilot', 'aircraft.approvals', 'aircraft.registrations']);

        $checks = [
            $this->pilotCheck($mission),
            $this->certificateCheck($mission),
            $this->medicalCheck($mission),
            $this->aircraftCheck($mission),
            $this->registrationCheck($mission),
            $this->approvalCheck($mission),
            $this->riskCheck($mission),
        ];

        return [
            'state' => $this->state($checks),
            'checks' => $checks,
            'evaluated_at' => now()->toIso8601String(),
        ];
    }

    private function pilotCheck(UasMission $mission): array
    {
        if (! $mission->pilot) {
            return $this->red('Pilot assigned', 'No pilot is assigned to the mission.', 'regulatory');
        }

        if ($mission->pilot->profile_status !== PilotProfileStatus::Active) {
            return $this->red('Pilot profile active', 'Assigned pilot profile is not active.', 'regulatory');
        }

        return $this->green('Pilot profile active', 'Assigned pilot profile is active.', 'regulatory');
    }

    private function certificateCheck(UasMission $mission): array
    {
        $certificate = PilotCertificate::query()
            ->where('uas_pilot_id', $mission->uas_pilot_id)
            ->latest('expiry_date')
            ->first();

        $state = $this->pilotCompliance->rpcState($certificate);

        return match ($state) {
            'valid' => $this->green('RPC valid', 'Pilot RPC is valid.', 'regulatory'),
            'expiring' => $this->amber('RPC expiring', 'Pilot RPC is inside the revalidation window.', 'regulatory'),
            default => $this->red('RPC valid', 'Pilot RPC is '.$state.'.', 'regulatory'),
        };
    }

    private function medicalCheck(UasMission $mission): array
    {
        if (! $mission->pilot || $mission->pilot->medical_status !== PilotMedicalStatus::Valid) {
            return $this->red('Medical requirement', 'Assigned pilot medical status is not valid.', 'regulatory');
        }

        return $this->green('Medical requirement', 'Assigned pilot medical status is valid.', 'regulatory');
    }

    private function aircraftCheck(UasMission $mission): array
    {
        if (! $mission->aircraft) {
            return $this->red('Aircraft assigned', 'No aircraft is assigned to the mission.', 'regulatory');
        }

        if (! $this->aircraftServiceability->mayBeAssignedToReleasedFlight($mission->aircraft)) {
            return $this->red('Aircraft serviceability', 'Aircraft is not serviceable for released flight.', 'regulatory');
        }

        return $this->green('Aircraft serviceability', 'Aircraft is serviceable for released flight.', 'regulatory');
    }

    private function registrationCheck(UasMission $mission): array
    {
        $registration = $mission->aircraft?->registrations->sortByDesc('issue_date')->first();

        if (! $registration) {
            return $this->red('Aircraft registration', 'No aircraft registration record is available.', 'regulatory');
        }

        if ($registration->expiry_date?->isPast()) {
            return $this->red('Aircraft registration', 'Aircraft registration is expired.', 'regulatory');
        }

        return $this->green('Aircraft registration', 'Aircraft registration evidence is available.', 'regulatory');
    }

    private function approvalCheck(UasMission $mission): array
    {
        $approval = $mission->aircraft?->approvals
            ->where('status', 'valid')
            ->sortByDesc('expiry_date')
            ->first();

        if (! $approval) {
            return $this->red('UASLA approval', 'No valid aircraft approval is available.', 'regulatory');
        }

        if ($approval->expiry_date?->isPast()) {
            return $this->red('UASLA approval', 'Aircraft approval is expired.', 'regulatory');
        }

        return $this->green('UASLA approval', 'Valid aircraft approval evidence is available.', 'regulatory');
    }

    private function riskCheck(UasMission $mission): array
    {
        if (blank($mission->risk_assessment)) {
            return $this->amber('Risk assessment', 'Risk assessment is not yet captured.', 'internal_policy');
        }

        return $this->green('Risk assessment', 'Risk assessment evidence is captured.', 'internal_policy');
    }

    private function state(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check): bool => $check['result'] === 'red')) {
            return 'red';
        }

        if (collect($checks)->contains(fn (array $check): bool => $check['result'] === 'amber')) {
            return 'amber';
        }

        return 'green';
    }

    private function green(string $label, string $message, string $basis): array
    {
        return ['label' => $label, 'result' => 'green', 'basis' => $basis, 'message' => $message];
    }

    private function amber(string $label, string $message, string $basis): array
    {
        return ['label' => $label, 'result' => 'amber', 'basis' => $basis, 'message' => $message];
    }

    private function red(string $label, string $message, string $basis): array
    {
        return ['label' => $label, 'result' => 'red', 'basis' => $basis, 'message' => $message];
    }
}
