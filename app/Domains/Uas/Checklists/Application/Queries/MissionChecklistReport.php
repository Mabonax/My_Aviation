<?php

namespace App\Domains\Uas\Checklists\Application\Queries;

use App\Domains\Uas\Checklists\Domain\Models\UasChecklistTemplate;
use App\Domains\Uas\Missions\Domain\Models\UasMission;

class MissionChecklistReport
{
    public function execute(UasMission $mission, string $type = 'pre_flight'): array
    {
        $template = UasChecklistTemplate::query()
            ->where('type', $type)
            ->where('active', true)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        $latest = $mission->checklists()
            ->with(['template', 'performer'])
            ->where('type', $type)
            ->latest('performed_at')
            ->first();

        return [
            'template' => $template ? [
                'id' => $template->id,
                'type' => $template->type,
                'name' => $template->name,
                'version' => $template->version,
                'effective_date' => $template->effective_date?->toDateString(),
                'items' => collect($template->items)->sortBy('sequence')->values()->all(),
                'regulatory_source' => $template->regulatory_source,
                'regulatory_source_version' => $template->regulatory_source_version,
                'regulatory_effective_date' => $template->regulatory_effective_date?->toDateString(),
            ] : null,
            'latest' => $latest ? [
                'id' => $latest->id,
                'type' => $latest->type,
                'checklist_version' => $latest->checklist_version,
                'performed_at' => $latest->performed_at?->toISOString(),
                'performed_by' => $latest->performer?->name,
                'results' => $latest->results ?? [],
                'exceptions' => $latest->exceptions,
                'state' => $latest->state,
            ] : null,
        ];
    }
}