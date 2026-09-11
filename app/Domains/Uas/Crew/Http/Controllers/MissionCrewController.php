<?php

namespace App\Domains\Uas\Crew\Http\Controllers;

use App\Domains\Uas\Crew\Application\Actions\AssignMissionCrewMember;
use App\Domains\Uas\Crew\Application\Queries\MissionCrewReport;
use App\Domains\Uas\Crew\Http\Requests\StoreMissionCrewMemberRequest;
use App\Domains\Uas\Missions\Application\Queries\MissionPresenter;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MissionCrewController extends Controller
{
    public function create(UasMission $mission, MissionCrewReport $crewReport): Response
    {
        Gate::authorize('update', $mission);

        return Inertia::render('missions/crew/create', [
            'mission' => MissionPresenter::toArray($mission),
            'crew' => $crewReport->execute($mission),
            'options' => [
                'pilots' => UasPilot::query()->orderBy('last_name')->orderBy('first_name')->get()->map(fn (UasPilot $pilot): array => [
                    'id' => $pilot->id,
                    'label' => $pilot->display_name,
                    'email' => $pilot->email,
                ])->values()->all(),
                'users' => User::query()->orderBy('name')->get()->map(fn (User $user): array => [
                    'id' => $user->id,
                    'label' => $user->name,
                    'email' => $user->email,
                ])->values()->all(),
                'crew_roles' => [
                    'remote_pilot' => 'Remote pilot',
                    'observer' => 'Observer',
                    'payload_operator' => 'Payload operator',
                    'visual_observer' => 'Visual observer',
                    'safety_officer' => 'Safety officer',
                    'operations_supervisor' => 'Operations supervisor',
                ],
                'briefing_statuses' => ['pending' => 'Pending', 'briefed' => 'Briefed'],
                'competency_statuses' => ['not_checked' => 'Not checked', 'verified' => 'Verified', 'expired' => 'Expired', 'not_required' => 'Not required'],
                'acceptance_statuses' => ['pending' => 'Pending', 'accepted' => 'Accepted', 'declined' => 'Declined'],
            ],
        ]);
    }

    public function store(StoreMissionCrewMemberRequest $request, UasMission $mission, AssignMissionCrewMember $assignCrewMember): RedirectResponse
    {
        Gate::authorize('update', $mission);

        $assignCrewMember->execute(
            $mission,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('missions.show', $mission)->with('success', 'Mission crew member assigned.');
    }
}