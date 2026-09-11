<?php

namespace App\Domains\Uas\Tracks\Http\Controllers;

use App\Domains\Uas\Missions\Application\Queries\MissionPresenter;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Tracks\Application\Actions\RecordFlightTrack;
use App\Domains\Uas\Tracks\Application\Queries\MissionTrackReport;
use App\Domains\Uas\Tracks\Http\Requests\StoreFlightTrackRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FlightTrackController extends Controller
{
    public function create(UasMission $mission, MissionTrackReport $trackReport): Response
    {
        Gate::authorize('update', $mission);

        return Inertia::render('missions/tracks/create', [
            'mission' => MissionPresenter::toArray($mission),
            'tracks' => $trackReport->execute($mission),
            'sourceTypes' => [
                'manual' => 'Manual capture',
                'telemetry_import' => 'Telemetry import',
                'rpa_controller' => 'RPA controller',
                'gcs_export' => 'GCS export',
            ],
        ]);
    }

    public function store(StoreFlightTrackRequest $request, UasMission $mission, RecordFlightTrack $recordFlightTrack): RedirectResponse
    {
        Gate::authorize('update', $mission);

        $recordFlightTrack->execute(
            $mission,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('missions.show', $mission)->with('success', 'Flight track recorded.');
    }
}