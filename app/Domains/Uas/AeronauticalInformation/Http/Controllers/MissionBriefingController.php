<?php

namespace App\Domains\Uas\AeronauticalInformation\Http\Controllers;

use App\Domains\Uas\AeronauticalInformation\Application\Actions\AcknowledgeMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\GenerateMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\MissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\MissionAeronauticalBriefing;
use App\Domains\Uas\AeronauticalInformation\Http\Requests\AcknowledgeBriefingRequest;
use App\Domains\Uas\AeronauticalInformation\Http\Requests\GenerateBriefingRequest;
use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MissionBriefingController extends Controller
{
    public function show(Request $request, UasMission $mission, MissionBriefing $query)
    {
        $input = $request->validate(['revision' => 'nullable|integer|min:1']);
        $data = $query->execute($mission, $request->user(), isset($input['revision']) ? (int) $input['revision'] : null);

        return $request->routeIs('api.v1.*') ? ApiResponse::success($data) : Inertia::render('aeronautical-information/briefing', $data);
    }

    public function store(GenerateBriefingRequest $request, UasMission $mission, GenerateMissionBriefing $generate, MissionBriefing $query)
    {
        $generate->execute($mission, $request->user(), $request->validated('aeronautical_context'));

        return $request->routeIs('api.v1.*') ? ApiResponse::success($query->execute($mission->refresh(), $request->user()), 'Briefing generated.', 201) : to_route('missions.briefing.show', $mission);
    }

    public function acknowledge(AcknowledgeBriefingRequest $request, UasMission $mission, MissionAeronauticalBriefing $briefing, AcknowledgeMissionBriefing $acknowledge, MissionBriefing $query)
    {
        $acknowledge->execute($mission, $briefing, $request->user());

        return $request->routeIs('api.v1.*') ? ApiResponse::success($query->execute($mission, $request->user()), 'Briefing acknowledged.') : to_route('missions.briefing.show', $mission);
    }
}
