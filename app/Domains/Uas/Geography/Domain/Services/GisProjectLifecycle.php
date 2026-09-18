<?php

namespace App\Domains\Uas\Geography\Domain\Services;

class GisProjectLifecycle
{
    public const PROJECT_TYPES = [
        'environmental_mapping' => 'Environmental mapping',
        'infrastructure_mapping' => 'Infrastructure mapping',
        'economic_opportunity_mapping' => 'Economic opportunity mapping',
        'land_use_analysis' => 'Land-use analysis',
        'asset_inspection' => 'Asset inspection',
        'construction_monitoring' => 'Construction monitoring',
        'disaster_assessment' => 'Disaster assessment',
        'community_development_intelligence' => 'Community development intelligence',
    ];

    public const STATES = [
        'plan' => 'Plan',
        'authorise' => 'Authorise',
        'fly' => 'Fly',
        'capture' => 'Capture',
        'process' => 'Process',
        'map' => 'Map',
        'analyse' => 'Analyse',
        'report' => 'Report',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    private const ORDERED_STATES = ['plan', 'authorise', 'fly', 'capture', 'process', 'map', 'analyse', 'report', 'closed'];

    public function canTransition(string $from, string $to): bool
    {
        if ($to === 'cancelled') {
            return $from !== 'closed';
        }

        if ($from === 'cancelled') {
            return false;
        }

        $current = array_search($from, self::ORDERED_STATES, true);
        $next = array_search($to, self::ORDERED_STATES, true);

        return $current !== false && $next !== false && $next === $current + 1;
    }
}
