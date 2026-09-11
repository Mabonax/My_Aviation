<?php

namespace App\Domains\Uas\Defects\Application\Queries;

use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;

class ListAircraftDefects
{
    public function execute(): array
    {
        return UasAircraftDefect::query()
            ->with(['aircraft', 'mission', 'reporter'])
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
                'reported_at' => $defect->reported_at?->toISOString(),
                'aircraft' => $defect->aircraft ? ['id' => $defect->aircraft->id, 'registration' => $defect->aircraft->registration, 'operational_status' => $defect->aircraft->operational_status] : null,
                'mission' => $defect->mission ? ['id' => $defect->mission->id, 'mission_number' => $defect->mission->mission_number] : null,
                'reported_by' => $defect->reporter?->name,
            ])->values()->all();
    }
}