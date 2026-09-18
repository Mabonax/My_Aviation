<?php

namespace App\Domains\Uas\Api\Http\Controllers\V1;

use App\Domains\Uas\Api\Application\ApiResponse;
use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Compliance\Domain\Models\ComplianceFinding;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Geography\Application\Queries\GisProjectPresenter;
use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantOperationalController extends Controller
{
    public function defects(Request $request, CurrentOperatorContext $context): JsonResponse
    {
        $operator = $context->requireFromRequest($request);

        $defects = UasAircraftDefect::query()
            ->where(function ($query) use ($operator) {
                $query->whereHas('aircraft.operators', fn ($operators) => $operators
                    ->where('uas_operators.id', $operator->id)
                    ->where('uas_operator_aircraft.status', 'active'))
                    ->orWhereHas('mission', fn ($mission) => $mission->where('uas_operator_id', $operator->id));
            })
            ->with(['aircraft', 'mission', 'reporter'])->latest('reported_at')->get()
            ->map(fn (UasAircraftDefect $defect): array => [
                'id' => $defect->id,
                'defect_number' => $defect->defect_number,
                'source' => $defect->source,
                'severity' => $defect->severity,
                'status' => $defect->status,
                'serviceability_impact' => $defect->serviceability_impact,
                'title' => $defect->title,
                'reported_at' => $defect->reported_at?->toISOString(),
                'aircraft' => $defect->aircraft ? ['id' => $defect->aircraft->id, 'registration' => $defect->aircraft->registration] : null,
                'mission' => $defect->mission ? ['id' => $defect->mission->id, 'mission_number' => $defect->mission->mission_number] : null,
            ])->values()->all();

        return ApiResponse::success(['defects' => $defects]);
    }

    public function batteries(Request $request, CurrentOperatorContext $context): JsonResponse
    {
        $operator = $context->requireFromRequest($request);

        $batteries = UasBattery::query()
            ->whereHas('compatibleAircraft.operators', fn ($operators) => $operators
                ->where('uas_operators.id', $operator->id)
                ->where('uas_operator_aircraft.status', 'active'))
            ->with('compatibleAircraft')->orderBy('battery_uid')->get()
            ->map(fn (UasBattery $battery): array => [
                'id' => $battery->id,
                'battery_uid' => $battery->battery_uid,
                'manufacturer' => $battery->manufacturer,
                'model' => $battery->model,
                'serial_number' => $battery->serial_number,
                'compatible_aircraft' => $battery->compatibleAircraft ? [
                    'id' => $battery->compatibleAircraft->id,
                    'registration' => $battery->compatibleAircraft->registration,
                ] : null,
                'cycle_count' => $battery->cycle_count,
                'maximum_cycles' => $battery->maximum_cycles,
                'health_status' => $battery->health_status,
                'retirement_status' => $battery->retirement_status,
            ])->values()->all();

        return ApiResponse::success(['batteries' => $batteries]);
    }

    public function gisProjects(Request $request, CurrentOperatorContext $context): JsonResponse
    {
        $operator = $context->requireFromRequest($request);

        $projects = UasGisProject::query()
            ->whereHas('projectMissions.mission', fn ($mission) => $mission->where('uas_operator_id', $operator->id))
            ->with('creator')->orderByDesc('created_at')->get()
            ->map(fn (UasGisProject $project): array => GisProjectPresenter::summary($project))->values()->all();

        return ApiResponse::success(['gis_projects' => $projects]);
    }

    public function complianceFindings(Request $request, CurrentOperatorContext $context): JsonResponse
    {
        $operator = $context->requireFromRequest($request);

        $findings = ComplianceFinding::query()
            ->where(function ($query) use ($operator) {
                $query->where(function ($direct) use ($operator) {
                    $direct->where('compliable_type', UasOperator::class)->where('compliable_id', $operator->id);
                })->orWhereHasMorph('compliable', [\App\Domains\Uas\Missions\Domain\Models\UasMission::class],
                    fn ($mission) => $mission->where('uas_operator_id', $operator->id))
                  ->orWhereHasMorph('compliable', [\App\Domains\Uas\Aircraft\Domain\Models\UasAircraft::class],
                    fn ($aircraft) => $aircraft->whereHas('operators', fn ($operators) => $operators
                        ->where('uas_operators.id', $operator->id)->where('uas_operator_aircraft.status', 'active')));
            })
            ->whereNull('resolved_at')->orderBy('due_at')->get()
            ->map(fn (ComplianceFinding $finding): array => [
                'id' => $finding->id,
                'requirement_id' => $finding->requirement_id,
                'state' => $finding->state,
                'severity' => $finding->severity,
                'summary' => $finding->summary,
                'recommended_action' => $finding->recommended_action,
                'due_at' => $finding->due_at?->toISOString(),
                'compliable_type' => $finding->compliable_type,
                'compliable_id' => $finding->compliable_id,
            ])->values()->all();

        return ApiResponse::success(['compliance_findings' => $findings]);
    }
}
