<?php

namespace App\Domains\Uas\Pilots\Http\Controllers;

use App\Domains\Uas\Pilots\Application\Queries\CurrentPilotProfile;
use App\Domains\Uas\Pilots\Application\Queries\MyPilotWorkspace;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MyComplianceController extends Controller
{
    public function show(CurrentPilotProfile $currentPilot, MyPilotWorkspace $workspace): Response
    {
        $pilot = $currentPilot->resolve(request()->user());
        abort_unless($pilot instanceof UasPilot, 404);

        Gate::authorize('viewOwn', $pilot);

        return Inertia::render('my/compliance/show', [
            'workspace' => $workspace->execute($pilot),
        ]);
    }
}
