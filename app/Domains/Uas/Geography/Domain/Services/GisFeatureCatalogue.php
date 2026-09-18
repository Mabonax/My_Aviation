<?php

namespace App\Domains\Uas\Geography\Domain\Services;

class GisFeatureCatalogue
{
    public const FEATURE_TYPES = [
        'environmental_feature' => 'Environmental feature',
        'infrastructure_asset' => 'Infrastructure asset',
        'land_use_area' => 'Land-use area',
        'access_constraint' => 'Access constraint',
        'risk_indicator' => 'Risk indicator',
        'opportunity_zone' => 'Opportunity zone',
        'field_observation' => 'Field observation',
    ];

    public const VERIFICATION_STATUSES = [
        'unverified' => 'Unverified',
        'field_check_required' => 'Field check required',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    public const RECORD_TYPES = [
        'opportunity' => 'Opportunity',
        'finding' => 'Finding',
    ];

    public const CATEGORIES = [
        'environmental' => 'Environmental',
        'infrastructure' => 'Infrastructure',
        'economic' => 'Economic',
        'land_use' => 'Land use',
        'safety' => 'Safety',
        'community_development' => 'Community development',
        'asset_inspection' => 'Asset inspection',
    ];

    public const SIGNIFICANCE = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    public const PRIORITIES = [
        'routine' => 'Routine',
        'monitor' => 'Monitor',
        'action_required' => 'Action required',
        'urgent' => 'Urgent',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'review' => 'Review',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'closed' => 'Closed',
    ];
}
