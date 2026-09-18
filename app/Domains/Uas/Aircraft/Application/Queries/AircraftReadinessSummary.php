<?php

namespace App\Domains\Uas\Aircraft\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Services\AircraftServiceabilityEvaluator;
use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Batteries\Domain\Services\BatteryHealthEvaluator;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;

class AircraftReadinessSummary
{
    private const CLOSED_DEFECT_STATUSES = ['closed', 'resolved', 'rectified', 'cancelled'];

    private const BLOCKING_DEFECT_IMPACTS = ['grounded', 'flight_restricted', 'maintenance_required'];

    private const VALID_REGISTRATION_STATES = ['active', 'valid', 'issued', 'current'];

    private const VALID_APPROVAL_STATUSES = ['active', 'valid', 'issued', 'current'];

    public function __construct(
        private readonly AircraftServiceabilityEvaluator $serviceability,
        private readonly BatteryHealthEvaluator $batteryHealth,
    ) {}

    public function execute(UasAircraft $aircraft): array
    {
        $aircraft->loadMissing(['catalogueModel.manufacturer', 'registrations', 'approvals', 'defects', 'batteries']);

        $checks = [
            $this->catalogueCheck($aircraft),
            $this->serviceabilityCheck($aircraft),
            $this->registrationCheck($aircraft),
            $this->approvalCheck($aircraft),
            $this->defectCheck($aircraft),
            $this->batteryCheck($aircraft),
        ];

        $status = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'red')
            ? 'red'
            : (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'amber') ? 'amber' : 'green');

        return [
            'status' => $status,
            'label' => match ($status) {
                'green' => 'Ready',
                'amber' => 'Review required',
                default => 'Not ready',
            },
            'as_of' => now()->toDateString(),
            'checks' => $checks,
            'blocking_reasons' => collect($checks)
                ->where('status', 'red')
                ->pluck('summary')
                ->values()
                ->all(),
            'review_reasons' => collect($checks)
                ->where('status', 'amber')
                ->pluck('summary')
                ->values()
                ->all(),
        ];
    }

    private function catalogueCheck(UasAircraft $aircraft): array
    {
        if ($aircraft->catalogueModel === null) {
            return $this->check('catalogue_model', 'Catalogue model', 'amber', 'No governed catalogue model is linked to this physical aircraft.');
        }

        return $this->check(
            'catalogue_model',
            'Catalogue model',
            'green',
            "{$aircraft->catalogueModel->manufacturer->name} {$aircraft->catalogueModel->model} is linked.",
            [
                'model_id' => $aircraft->catalogueModel->id,
                'catalogue_status' => $aircraft->catalogueModel->catalogue_status,
                'verified_at' => $aircraft->catalogueModel->verified_at?->toDateString(),
            ],
        );
    }

    private function serviceabilityCheck(UasAircraft $aircraft): array
    {
        if (! $this->serviceability->mayBeAssignedToReleasedFlight($aircraft)) {
            return $this->check(
                'serviceability',
                'Aircraft serviceability',
                'red',
                "Operational status {$aircraft->operational_status} blocks release.",
                ['operational_status' => $aircraft->operational_status],
            );
        }

        return $this->check(
            'serviceability',
            'Aircraft serviceability',
            'green',
            "Operational status {$aircraft->operational_status} permits release.",
            ['operational_status' => $aircraft->operational_status],
        );
    }

    private function registrationCheck(UasAircraft $aircraft): array
    {
        $registration = $aircraft->registrations
            ->sortByDesc(fn (AircraftRegistration $registration) => $registration->expiry_date?->timestamp ?? 0)
            ->first(fn (AircraftRegistration $registration): bool => in_array(strtolower($registration->lifecycle_state), self::VALID_REGISTRATION_STATES, true));

        if ($registration === null) {
            return $this->check('registration', 'Registration', 'red', 'No active aircraft registration record is available.');
        }

        if ($registration->expiry_date !== null && $registration->expiry_date->isPast()) {
            return $this->check(
                'registration',
                'Registration',
                'red',
                "Registration {$registration->registration_number} expired on {$registration->expiry_date->toDateString()}.",
                $this->registrationEvidence($registration),
            );
        }

        $status = $registration->expiry_date !== null && $registration->expiry_date->lte(now()->addDays(30)) ? 'amber' : 'green';
        $summary = $registration->expiry_date === null
            ? "Registration {$registration->registration_number} has no expiry date captured."
            : "Registration {$registration->registration_number} valid until {$registration->expiry_date->toDateString()}.";

        return $this->check('registration', 'Registration', $status, $summary, $this->registrationEvidence($registration));
    }

    private function approvalCheck(UasAircraft $aircraft): array
    {
        $approval = $aircraft->approvals
            ->filter(fn (AircraftApproval $approval): bool => in_array(strtolower($approval->approval_type), ['uasla', 'rla'], true))
            ->sortByDesc(fn (AircraftApproval $approval) => $approval->expiry_date?->timestamp ?? 0)
            ->first(fn (AircraftApproval $approval): bool => in_array(strtolower($approval->status), self::VALID_APPROVAL_STATUSES, true));

        if ($approval === null) {
            return $this->check('uasla_approval', 'UASLA/RLA approval', 'red', 'No active UASLA/RLA approval is available.');
        }

        if ($approval->expiry_date !== null && $approval->expiry_date->isPast()) {
            return $this->check(
                'uasla_approval',
                'UASLA/RLA approval',
                'red',
                "{$approval->approval_type} {$approval->approval_number} expired on {$approval->expiry_date->toDateString()}.",
                $this->approvalEvidence($approval),
            );
        }

        $status = $approval->expiry_date !== null && $approval->expiry_date->lte(now()->addDays(30)) ? 'amber' : 'green';
        $summary = $approval->expiry_date === null
            ? "{$approval->approval_type} {$approval->approval_number} has no expiry date captured."
            : "{$approval->approval_type} {$approval->approval_number} valid until {$approval->expiry_date->toDateString()}.";

        return $this->check('uasla_approval', 'UASLA/RLA approval', $status, $summary, $this->approvalEvidence($approval));
    }

    private function defectCheck(UasAircraft $aircraft): array
    {
        $openDefects = $aircraft->defects
            ->reject(fn (UasAircraftDefect $defect): bool => in_array(strtolower($defect->status), self::CLOSED_DEFECT_STATUSES, true));

        $blockingDefects = $openDefects
            ->filter(fn (UasAircraftDefect $defect): bool => in_array(strtolower($defect->serviceability_impact), self::BLOCKING_DEFECT_IMPACTS, true));

        if ($blockingDefects->isNotEmpty()) {
            return $this->check(
                'defects',
                'Open defects',
                'red',
                $blockingDefects->count().' open serviceability defect(s) block release.',
                [
                    'open_count' => $openDefects->count(),
                    'blocking_count' => $blockingDefects->count(),
                    'blocking_defects' => $blockingDefects->pluck('defect_number')->values()->all(),
                ],
            );
        }

        if ($openDefects->isNotEmpty()) {
            return $this->check(
                'defects',
                'Open defects',
                'amber',
                $openDefects->count().' open non-blocking defect(s) require review.',
                ['open_count' => $openDefects->count()],
            );
        }

        return $this->check('defects', 'Open defects', 'green', 'No open aircraft defects recorded.', ['open_count' => 0]);
    }

    private function batteryCheck(UasAircraft $aircraft): array
    {
        $batteryStates = $aircraft->batteries
            ->map(fn (UasBattery $battery): array => [
                'id' => $battery->id,
                'battery_uid' => $battery->battery_uid,
                'state' => $this->batteryHealth->status($battery),
                'cycle_count' => $battery->cycle_count,
                'maximum_cycles' => $battery->maximum_cycles,
            ]);

        if ($batteryStates->isEmpty()) {
            return $this->check('batteries', 'Compatible batteries', 'amber', 'No compatible battery records are linked to this aircraft.');
        }

        $serviceable = $batteryStates->where('state', 'serviceable')->count();

        if ($serviceable === 0) {
            return $this->check(
                'batteries',
                'Compatible batteries',
                'red',
                'No serviceable compatible batteries are available.',
                ['total' => $batteryStates->count(), 'states' => $batteryStates->values()->all()],
            );
        }

        $watched = $batteryStates->filter(fn (array $state): bool => $state['state'] !== 'serviceable');
        $status = $watched->isNotEmpty() ? 'amber' : 'green';
        $summary = $watched->isNotEmpty()
            ? "{$serviceable} serviceable battery/batteries available; {$watched->count()} require review."
            : "{$serviceable} serviceable compatible battery/batteries available.";

        return $this->check(
            'batteries',
            'Compatible batteries',
            $status,
            $summary,
            ['total' => $batteryStates->count(), 'serviceable' => $serviceable, 'states' => $batteryStates->values()->all()],
        );
    }

    private function registrationEvidence(AircraftRegistration $registration): array
    {
        return [
            'id' => $registration->id,
            'registration_number' => $registration->registration_number,
            'lifecycle_state' => $registration->lifecycle_state,
            'expiry_date' => $registration->expiry_date?->toDateString(),
        ];
    }

    private function approvalEvidence(AircraftApproval $approval): array
    {
        return [
            'id' => $approval->id,
            'approval_type' => $approval->approval_type,
            'approval_number' => $approval->approval_number,
            'status' => $approval->status,
            'expiry_date' => $approval->expiry_date?->toDateString(),
        ];
    }

    private function check(string $code, string $label, string $status, string $summary, array $evidence = []): array
    {
        return [
            'code' => $code,
            'label' => $label,
            'status' => $status,
            'summary' => $summary,
            'evidence' => $evidence,
        ];
    }
}
