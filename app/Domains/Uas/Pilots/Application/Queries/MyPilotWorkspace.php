<?php

namespace App\Domains\Uas\Pilots\Application\Queries;

use App\Domains\Uas\FlightLogs\Domain\Services\PilotLogbookSummary;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Support\Carbon;

class MyPilotWorkspace
{
    public function __construct(private readonly PilotLogbookSummary $logbookSummary) {}

    public function execute(UasPilot $pilot): array
    {
        $pilot->loadMissing([
            'certificates' => fn ($query) => $query->orderBy('expiry_date'),
            'missions' => fn ($query) => $query->latest()->limit(5),
        ]);

        $certificates = $pilot->certificates->map(fn ($certificate): array => [
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'status' => $certificate->status,
            'expiry_date' => $certificate->expiry_date?->toDateString(),
            'days_until_expiry' => $certificate->expiry_date
                ? Carbon::today()->diffInDays($certificate->expiry_date, false)
                : null,
        ])->values();

        return [
            'pilot' => PilotProfilePresenter::toArray($pilot),
            'certificates' => $certificates,
            'compliance' => [
                'profile_status' => $pilot->profile_status->value,
                'rpc_category' => $pilot->rpc_category->value,
                'medical_status' => $pilot->medical_status->value,
                'radiotelephony_qualification' => $pilot->radiotelephony_qualification->value,
                'certificate_count' => $certificates->count(),
                'expiring_certificates_30_days' => $certificates
                    ->filter(fn (array $certificate): bool => $certificate['days_until_expiry'] !== null
                        && $certificate['days_until_expiry'] >= 0
                        && $certificate['days_until_expiry'] <= 30)
                    ->count(),
            ],
            'logbook' => $this->logbookSummary->summarize($pilot),
            'missions' => $pilot->missions->map(fn ($mission): array => [
                'id' => $mission->id,
                'mission_number' => $mission->mission_number,
                'purpose' => $mission->purpose,
                'location' => $mission->location,
                'planned_start_at' => $mission->planned_start_at?->toISOString(),
                'lifecycle_state' => $mission->lifecycle_state?->value,
                'release_gate_state' => $mission->release_gate_state,
            ])->values(),
            'documents' => [
                'count' => $pilot->documents()->count(),
                'architecture_gap' => 'Pilot documents still use the current regulatory document morph and require the future governed evidence vault.',
            ],
        ];
    }
}
