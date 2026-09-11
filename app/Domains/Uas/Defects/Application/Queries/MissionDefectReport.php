<?php

namespace App\Domains\Uas\Defects\Application\Queries;

use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionDefectReport
{
    public function execute(UasMission $mission): array
    {
        $defects = $mission->defects()
            ->with(['aircraft', 'reporter'])
            ->latest('reported_at')
            ->get()
            ->map(fn (UasAircraftDefect $defect): array => [
                'id' => $defect->id,
                'defect_number' => $defect->defect_number,
                'source' => $defect->source,
                'severity' => $defect->severity,
                'status' => $defect->status,
                'serviceability_impact' => $defect->serviceability_impact,
                'title' => $defect->title,
                'description' => $defect->description,
                'immediate_action' => $defect->immediate_action,
                'reported_at' => $defect->reported_at?->toISOString(),
                'aircraft_registration' => $defect->aircraft?->registration,
                'reported_by' => $defect->reporter?->name,
                'regulatory_source' => $defect->regulatory_source,
                'regulatory_source_version' => $defect->regulatory_source_version,
                'regulatory_effective_date' => $defect->regulatory_effective_date?->toDateString(),
            ])->values()->all();

        return [
            'summary' => [
                'total' => count($defects),
                'open' => collect($defects)->where('status', 'open')->count(),
                'serviceability_impacts' => collect($defects)->filter(fn (array $defect): bool => $defect['serviceability_impact'] !== 'none')->count(),
                'grounding' => collect($defects)->where('serviceability_impact', 'grounded')->count(),
            ],
            'defects' => $defects,
        ];
    }
}