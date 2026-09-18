<?php

namespace App\Domains\Uas\Geography\Domain\Services;

class GisDatasetCatalogue
{
    public const DATASET_TYPES = [
        'imagery' => 'Imagery',
        'orthomosaic' => 'Orthomosaic',
        'point_cloud' => 'Point cloud',
        'digital_surface_model' => 'Digital surface model',
        'digital_terrain_model' => 'Digital terrain model',
        'vector_layer' => 'Vector layer',
        'field_verification' => 'Field verification',
    ];

    public const PROCESSING_STATUSES = [
        'raw' => 'Raw',
        'processing' => 'Processing',
        'processed' => 'Processed',
        'published' => 'Published',
        'rejected' => 'Rejected',
    ];

    public const QUALITY_STATUSES = [
        'unchecked' => 'Unchecked',
        'passed' => 'Passed',
        'needs_review' => 'Needs review',
        'failed' => 'Failed',
    ];

    public const LAYER_TYPES = [
        'orthomosaic_reference' => 'Orthomosaic reference',
        'feature_extraction' => 'Feature extraction',
        'environmental_indicator' => 'Environmental indicator',
        'infrastructure_asset' => 'Infrastructure asset',
        'opportunity_area' => 'Opportunity area',
        'risk_or_finding' => 'Risk or finding',
    ];

    public const GEOMETRY_TYPES = [
        'raster' => 'Raster',
        'point' => 'Point',
        'line' => 'Line',
        'polygon' => 'Polygon',
        'mixed' => 'Mixed',
    ];

    public const LAYER_STATUSES = [
        'draft' => 'Draft',
        'review' => 'Review',
        'approved' => 'Approved',
        'retired' => 'Retired',
    ];
}
