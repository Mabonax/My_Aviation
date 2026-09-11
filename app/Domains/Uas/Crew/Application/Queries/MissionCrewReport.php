<?php

namespace App\Domains\Uas\Crew\Application\Queries;

use App\Domains\Uas\Crew\Domain\Models\UasMissionCrewMember;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionCrewReport
{
    public function execute(UasMission $mission): array
    {
        $members = $mission->crewMembers()
            ->with(['pilot', 'user', 'assigner'])
            ->orderBy('crew_role')
            ->orderBy('display_name')
            ->get()
            ->map(fn (UasMissionCrewMember $member): array => [
                'id' => $member->id,
                'crew_role' => $member->crew_role,
                'display_name' => $member->display_name,
                'email' => $member->email,
                'phone' => $member->phone,
                'briefing_status' => $member->briefing_status,
                'competency_status' => $member->competency_status,
                'acceptance_status' => $member->acceptance_status,
                'emergency_contact_name' => $member->emergency_contact_name,
                'emergency_contact_phone' => $member->emergency_contact_phone,
                'notes' => $member->notes,
                'linked_pilot' => $member->pilot ? ['id' => $member->pilot->id, 'display_name' => $member->pilot->display_name] : null,
                'linked_user' => $member->user ? ['id' => $member->user->id, 'name' => $member->user->name] : null,
                'assigned_by' => $member->assigner?->name,
                'regulatory_source' => $member->regulatory_source,
                'regulatory_source_version' => $member->regulatory_source_version,
                'regulatory_effective_date' => $member->regulatory_effective_date?->toDateString(),
            ])
            ->values()
            ->all();

        return [
            'members' => $members,
            'summary' => [
                'total' => count($members),
                'briefed' => collect($members)->where('briefing_status', 'briefed')->count(),
                'accepted' => collect($members)->where('acceptance_status', 'accepted')->count(),
                'competency_verified' => collect($members)->where('competency_status', 'verified')->count(),
                'attention_required' => collect($members)->filter(fn (array $member): bool => $member['briefing_status'] !== 'briefed' || $member['acceptance_status'] !== 'accepted' || $member['competency_status'] === 'expired')->count(),
            ],
        ];
    }
}