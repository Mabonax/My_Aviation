<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Application\Actions\AssignAircraftToOperator;
use App\Domains\Uas\Operators\Application\Actions\AssignPilotToOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Http\Requests\AssignOperatorAircraftRequest;
use App\Domains\Uas\Operators\Http\Requests\AssignOperatorPilotRequest;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class OperatorAssignmentController extends Controller
{
    public function storePilot(AssignOperatorPilotRequest $request, UasOperator $operator, AssignPilotToOperator $assignPilot): RedirectResponse
    {
        $data = $request->validated();

        $assignPilot->execute(
            $operator,
            UasPilot::query()->findOrFail($data['uas_pilot_id']),
            $request->user(),
            $data['assignment_role'] ?? 'remote_pilot',
            $data['status'] ?? 'active',
            $data['notes'] ?? null,
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('success', 'Pilot assigned to operator.');
    }

    public function storeAircraft(AssignOperatorAircraftRequest $request, UasOperator $operator, AssignAircraftToOperator $assignAircraft): RedirectResponse
    {
        $data = $request->validated();

        $assignAircraft->execute(
            $operator,
            UasAircraft::query()->findOrFail($data['uas_aircraft_id']),
            $request->user(),
            $data['assignment_role'] ?? 'operated_aircraft',
            $data['status'] ?? 'active',
            $data['notes'] ?? null,
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('success', 'Aircraft assigned to operator.');
    }
}
