<?php

namespace App\Domains\Uas\Geography\Domain\Services;

class GisProjectMissionAssignment
{
    public const OUTPUTS = [
        'imagery' => 'Imagery',
        'orthomosaic' => 'Orthomosaic',
        'spatial_layer' => 'Spatial layer',
        'inspection_evidence' => 'Inspection evidence',
        'opportunity_finding' => 'Opportunity or finding',
        'report_input' => 'Report input',
    ];

    public const FIELD_VERIFICATION = [
        'required' => 'Required',
        'not_required' => 'Not required',
        'completed' => 'Completed',
    ];

    public const STATUSES = [
        'planned' => 'Planned',
        'assigned' => 'Assigned',
        'captured' => 'Captured',
        'processed' => 'Processed',
        'cancelled' => 'Cancelled',
    ];
}
