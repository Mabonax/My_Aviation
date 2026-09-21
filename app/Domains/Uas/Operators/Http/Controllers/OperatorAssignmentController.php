<?php

namespace App\Domains\Uas\Operators\Http\Controllers;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Operators\Application\Actions\AssignAircraftToOperator;
use App\Domains\Uas\Operators\Application\Actions\AssignPilotToOperator;
use App\Domains\Uas\Operators\Application\Actions\ApprovePilotForOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Http\Requests\AssignOperatorAircraftRequest;
use App\Domains\Uas\Operators\Http\Requests\AssignOperatorPilotRequest;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class OperatorAssignmentController extends Controller
{
    public function storePilot(AssignOperatorPilotRequest $request, UasOperator $operator, AssignPilotToOperator $assignPilot, ApprovePilotForOperator $approvePilot): RedirectResponse
    {
        $data = $request->validated();

        $pilot = UasPilot::query()->findOrFail($data['uas_pilot_id']);
        $status = $data['status'] ?? 'active';

        if ($status === 'active') {
            $approvePilot->execute(
                $operator,
                $pilot,
                $data['assignment_role'] ?? 'remote_pilot',
                $request->user(),
                notes: $data['notes'] ?? null,
            );
        } else {
            $assignPilot->execute(
                $operator,
                $pilot,
                $request->user(),
                $data['assignment_role'] ?? 'remote_pilot',
                $status,
                $data['notes'] ?? null,
                $request->ip(),
                $request->userAgent(),
            );
        }

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
