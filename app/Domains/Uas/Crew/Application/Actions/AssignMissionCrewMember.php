<?php

namespace App\Domains\Uas\Crew\Application\Actions;

use App\Domains\Uas\Crew\Domain\Models\UasMissionCrewMember;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignMissionCrewMember
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-CREW-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Dedicated mission crew assignment, briefing, competency and acceptance evidence for UAS operations.',
    ];

    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(UasMission $mission, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): UasMissionCrewMember
    {
        return DB::transaction(function () use ($mission, $data, $actor, $ipAddress, $userAgent): UasMissionCrewMember {
            $member = UasMissionCrewMember::query()->create([
                'uas_mission_id' => $mission->id,
                'uas_pilot_id' => $data['uas_pilot_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'assigned_by' => $actor->id,
                'crew_role' => $data['crew_role'],
                'display_name' => $data['display_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'briefing_status' => $data['briefing_status'] ?? 'pending',
                'competency_status' => $data['competency_status'] ?? 'not_checked',
                'acceptance_status' => $data['acceptance_status'] ?? 'pending',
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                ...self::TRACEABILITY,
            ]);

            $this->recordAuditEntry->execute(new AuditEntryData(
                actor: $actor,
                auditable: $member,
                action: 'mission_crew.assigned',
                requirementId: 'FR-CREW-001',
                regulatorySource: self::TRACEABILITY['regulatory_source'],
                previousValues: null,
                newValues: $member->getAttributes(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $member;
        });
    }
}