<?php

use App\Domains\Uas\Geography\Application\Queries\AviationOverlayReport;
use App\Domains\Uas\Geography\Domain\Models\AviationOverlaySource;
use App\Domains\Uas\Geography\Domain\Models\AviationOverlayZone;
use App\Domains\Uas\Geography\Domain\Services\AviationOverlayCatalogue;
use App\Models\User;

it('seeds source-aware aviation overlays for FR-GEO-002', function () {
    expect(AviationOverlaySource::query()->count())->toBe(3)
        ->and(AviationOverlaySource::query()->where('authoritative', true)->count())->toBe(3)
        ->and(AviationOverlayZone::query()->count())->toBe(6);

    $summary = app(AviationOverlayCatalogue::class)->layerSummary();

    expect($summary)->toHaveKeys([
        'aerodrome',
        'approved_operating_zone',
        'controlled_airspace',
        'prohibited_airspace',
        'restricted_airspace',
        'strategic_area',
    ]);

    $report = app(AviationOverlayReport::class)->execute();

    expect($report['sources'][0])->toHaveKeys(['name', 'publisher', 'source_url', 'source_version', 'authoritative'])
        ->and($report['zones'][0]['source'])->toHaveKeys(['publisher', 'source_url', 'source_version', 'authoritative']);
});

it('requires authentication for the aviation overlay registry', function () {
    $this->withoutVite();

    $this->get('/aviation-overlays')->assertRedirect('/login');

    $user = User::factory()->create();

    $this->actingAs($user)->get('/aviation-overlays')->assertOk();
});