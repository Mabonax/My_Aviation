<?php

namespace App\Domains\Uas\Tracks\Application\Actions;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Domains\Uas\Tracks\Domain\Models\UasFlightTrack;
use App\Domains\Uas\Tracks\Domain\Services\FlightTrackSummariser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordFlightTrack
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-TRK-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Mission flight track capture, telemetry evidence and post-flight traceability for UAS operations.',
    ];

    public function __construct(
        private readonly FlightTrackSummariser $summariser,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(UasMission $mission, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasFlightTrack
    {
        return DB::transaction(function () use ($mission, $data, $actor, $ipAddress, $userAgent): UasFlightTrack {
            $points = $this->summariser->normalise($data['points']);
            $summary = $this->summariser->summary($points);

            $track = UasFlightTrack::query()->create([
                'uas_mission_id' => $mission->id,
                'captured_by' => $actor->id,
                'source_type' => $data['source_type'],
                'track_reference' => $data['track_reference'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'ended_at' => $data['ended_at'] ?? null,
                'points' => $points,
                'point_count' => $summary['point_count'],
                'total_distance_km' => $summary['total_distance_km'],
                'max_altitude_ft' => $summary['max_altitude_ft'],
                'anomalies' => $data['anomalies'] ?? null,
                'notes' => $data['notes'] ?? null,
                ...self::TRACEABILITY,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $track,
                action: 'flight_track.recorded',
                requirementId: 'FR-TRK-001',
                regulatorySource: self::TRACEABILITY['regulatory_source'],
                previousValues: null,
                newValues: $track->getAttributes(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $track;
        });
    }
}