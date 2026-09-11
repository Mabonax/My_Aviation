<?php

namespace App\Domains\Uas\Defects\Application\Actions;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Defects\Domain\Services\DefectServiceabilityImpact;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportAircraftDefect
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-DEF-001, FR-DEF-002 and FR-DEF-003',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Defect sources, severity capture and aircraft serviceability impact controls.',
    ];

    public function __construct(
        private readonly DefectServiceabilityImpact $impact,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(array $data, User $actor, ?UasMission $mission = null, ?string $ipAddress = null, ?string $userAgent = null): UasAircraftDefect
    {
        return DB::transaction(function () use ($data, $actor, $mission, $ipAddress, $userAgent): UasAircraftDefect {
            $aircraftId = $mission?->uas_aircraft_id ?? $data['uas_aircraft_id'] ?? null;

            if (! $aircraftId) {
                throw ValidationException::withMessages(['uas_aircraft_id' => 'An aircraft is required before a defect can be reported.']);
            }

            if ($mission && isset($data['uas_aircraft_id']) && (int) $data['uas_aircraft_id'] !== (int) $mission->uas_aircraft_id) {
                throw ValidationException::withMessages(['uas_aircraft_id' => 'Defect aircraft must match the mission aircraft.']);
            }

            $aircraft = UasAircraft::query()->findOrFail($aircraftId);
            $serviceabilityImpact = $this->impact->impactForSeverity($data['severity']);

            $defect = UasAircraftDefect::query()->create([
                ...$data,
                'uas_aircraft_id' => $aircraft->id,
                'uas_mission_id' => $mission?->id ?? $data['uas_mission_id'] ?? null,
                'reported_by' => $actor->id,
                'defect_number' => $data['defect_number'] ?? $this->nextDefectNumber(),
                'status' => $data['status'] ?? 'open',
                'serviceability_impact' => $serviceabilityImpact,
                'reported_at' => $data['reported_at'] ?? now(),
                'evidence_references' => $data['evidence_references'] ?? null,
                ...self::TRACEABILITY,
            ]);

            $aircraftStatus = $this->impact->aircraftStatusForImpact($serviceabilityImpact);

            if ($aircraftStatus !== null) {
                $aircraft->forceFill(['operational_status' => $aircraftStatus])->save();
            }

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $defect, 'defect.reported', 'FR-DEF-001', self::TRACEABILITY['regulatory_source'], null, $defect->getAttributes(), $ipAddress, $userAgent));

            return $defect;
        });
    }

    private function nextDefectNumber(): string
    {
        $next = UasAircraftDefect::query()->lockForUpdate()->count() + 1;

        return 'DEF-'.now()->format('Ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}