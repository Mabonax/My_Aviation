<?php

namespace App\Domains\Uas\Telemetry\Http\Controllers;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Telemetry\Application\Actions\ImportMissionTelemetry;
use App\Domains\Uas\Telemetry\Domain\Models\TelemetryImport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MissionTelemetryController extends Controller
{
    public function page(Request $request, UasMission $mission, CurrentOperatorContext $context): \Inertia\Response
    {
        $this->authorizeMission($request, $mission, $context);
        return \Inertia\Inertia::render('missions/telemetry', [
            'mission' => [
                'id' => $mission->id,
                'mission_number' => $mission->mission_number,
                'pilot' => $mission->pilot?->first_name.' '.$mission->pilot?->last_name,
                'aircraft' => $mission->aircraft?->registration,
                'serial' => $mission->aircraft?->serial_number,
                'can_import' => Gate::allows('update', $mission)
                    && in_array($mission->lifecycle_state, [
                        \App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState::Completed,
                        \App\Domains\Uas\Missions\Domain\Enums\MissionLifecycleState::PostFlightReview,
                    ], true) && $mission->post_flight_propagated_at === null,
            ],
            'imports' => TelemetryImport::query()->where('uas_mission_id', $mission->id)
                ->latest('id')->paginate(20)->withQueryString()->through(fn ($import) => $this->present($import)),
        ]);
    }

    public function webStore(Request $request, UasMission $mission, CurrentOperatorContext $context, ImportMissionTelemetry $action): \Illuminate\Http\RedirectResponse
    {
        $this->store($request, $mission, $context, $action);
        return redirect()->route('missions.telemetry.page', $mission);
    }

    public function webAccept(Request $request, UasMission $mission, TelemetryImport $import, CurrentOperatorContext $context, ImportMissionTelemetry $action): \Illuminate\Http\RedirectResponse
    {
        $this->accept($request, $mission, $import, $context, $action);
        return redirect()->route('missions.telemetry.page', $mission);
    }

    public function index(Request $request, UasMission $mission, CurrentOperatorContext $context): JsonResponse
    {
        $this->authorizeMission($request, $mission, $context);
        return ApiResponse::success(['telemetry_imports' => TelemetryImport::query()
            ->where('uas_mission_id', $mission->id)->latest('id')->paginate(20)
            ->through(fn ($import) => $this->present($import))]);
    }

    public function store(Request $request, UasMission $mission, CurrentOperatorContext $context, ImportMissionTelemetry $action): JsonResponse
    {
        $this->authorizeMission($request, $mission, $context, 'update');
        $request->validate(['file' => ['required', 'file', 'max:2048']]);
        $csv = file_get_contents($request->file('file')->getRealPath());
        abort_if($csv === false, 422, 'Unable to read the uploaded file.');
        return ApiResponse::success(['telemetry_import' => $this->present($action->stage($mission, $request->user(), $csv))],
            'Telemetry staged for review; operational records have not been updated.');
    }

    public function accept(Request $request, UasMission $mission, TelemetryImport $import, CurrentOperatorContext $context, ImportMissionTelemetry $action): JsonResponse
    {
        $this->authorizeMission($request, $mission, $context, 'update');
        abort_unless((int) $import->uas_mission_id === (int) $mission->id
            && (int) $import->uas_operator_id === (int) $mission->uas_operator_id, 404);
        $data = $request->validate([
            'telemetry_confirmed' => ['required', 'accepted'],
            'pilot_confirmed' => ['required', 'accepted'],
            'aircraft_confirmed' => ['required', 'accepted'],
            'defects_declared' => ['required', 'boolean'],
            'occurrence_declared' => ['required', 'boolean'],
            'closure_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        foreach (['telemetry_confirmed', 'pilot_confirmed', 'aircraft_confirmed', 'defects_declared', 'occurrence_declared'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        return ApiResponse::success(['telemetry_import' => $this->present($action->accept($mission, $import, $request->user(), $data))],
            'Reviewed telemetry propagated into post-flight records.');
    }

    private function authorizeMission(Request $request, UasMission $mission, CurrentOperatorContext $context, string $ability = 'view'): void
    {
        $operator = $context->requireFromRequest($request);
        abort_unless((int) $mission->uas_operator_id === (int) $operator->id, 404);
        Gate::authorize($ability, $mission);
    }

    private function present(TelemetryImport $import): array
    {
        return [
            'id' => $import->id, 'state' => $import->state, 'format' => $import->format,
            'content_sha256' => $import->content_sha256, 'flight_sha256' => $import->flight_sha256,
            'flight' => array_diff_key($import->normalised_flight, ['points' => true]),
            'accepted_at' => $import->accepted_at?->toISOString(),
            'propagation_results' => $import->propagation_results,
        ];
    }
}
