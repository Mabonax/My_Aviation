<?php

use App\Domains\Uas\AeronauticalInformation\Infrastructure\Providers\FixtureAeronauticalInformationProvider;
use App\Domains\Uas\AeronauticalInformation\Infrastructure\Providers\ManualAeronauticalInformationProvider;
use App\Domains\Uas\AeronauticalInformation\Infrastructure\Providers\SacaaPublicationProvider;

return [
    // Versioned internal release policy, not a claim about a regulatory minimum.
    'assessment_version' => 'YAW-AIM-1.0',
    'horizontal_buffer_m' => 500,
    'vertical_buffer_ft' => 100,
    'briefing_valid_minutes' => 60,
    'required_providers' => ['atns_aim'],
    'providers' => [
        'manual' => ['adapter' => ManualAeronauticalInformationProvider::class, 'max_age_minutes' => 60],
        'sacaa_publications' => ['adapter' => SacaaPublicationProvider::class, 'max_age_minutes' => 1440],
        'fixture' => ['adapter' => FixtureAeronauticalInformationProvider::class, 'max_age_minutes' => 60],
        // No endpoint or operational adapter is assumed to exist.
        'atns_aim' => ['adapter' => null, 'operational' => false, 'approved' => false, 'approval_evidence' => [], 'enabled' => env('ATNS_AIM_ENABLED', false), 'base_url' => env('ATNS_AIM_BASE_URL'), 'client_id' => env('ATNS_AIM_CLIENT_ID'), 'client_secret' => env('ATNS_AIM_CLIENT_SECRET'), 'max_age_minutes' => 60],
    ],
];
