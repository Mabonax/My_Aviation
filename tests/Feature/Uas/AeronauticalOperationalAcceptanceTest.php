<?php

use App\Domains\Uas\AeronauticalInformation\Application\Actions\GenerateMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Queries\BriefingReadiness;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderCapabilities;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\MissionAeronauticalBriefing;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class LimitedAimContractProvider extends OperationalAeronauticalTestProvider
{
    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(providerId: $this->key(), providerName: 'Incomplete test capabilities', authority: $this->classification(), operational: true,
            informationTypes: ['NOTAM'], coverageTypes: ['bbox'], datasetModes: ['full_snapshot']);
    }
}

beforeEach(function () {
    $this->withoutVite();
    $this->freezeTime();
});

it('rejects operational readiness when an approved adapter lacks lifecycle and timestamp capabilities', function () {
    aimSync();
    config(['aeronautical.providers.operational_test.adapter' => LimitedAimContractProvider::class]);
    $state = app(BriefingReadiness::class)->execute(aimMission());
    expect($state['blocking'])->toBeTrue()->and(collect($state['providers'])->firstWhere('provider', 'operational_test')['health_status'])->toBe('authority_insufficient');
});

it('filters the authenticated register and exposes linked replacement history', function () {
    $actor = User::factory()->create(['role' => 'super_admin']);
    aimSync([aimRecord(), aimRecord(['source_identifier' => 'EXPIRED', 'effective_from' => now()->subDays(3)->toISOString(), 'effective_until' => now()->subDay()->toISOString()]),
        aimRecord(['source_identifier' => 'FUTURE', 'effective_from' => now()->addDay()->toISOString(), 'effective_until' => now()->addDays(2)->toISOString()])]);
    foreach (['active', 'expired', 'future'] as $validity) {
        $this->actingAs($actor)->getJson('/api/v1/aeronautical-information?type=NOTAM&provider=operational_test&validity='.$validity)->assertOk()->assertJsonPath('data.items.total', 1);
    }
    $old = AeronauticalInformationItem::where('source_identifier', 'TEST-A0001/26')->first();
    aimSync([aimRecord(['source_identifier' => 'REPLACEMENT', 'normalized' => ['replaces_identifier' => $old->source_identifier]])]);
    $replacement = AeronauticalInformationItem::where('source_identifier', 'REPLACEMENT')->first();
    $this->getJson('/api/v1/aeronautical-information?status=superseded')->assertOk()->assertJsonPath('data.items.total', 1);
    $this->getJson('/api/v1/aeronautical-information?search=NO-SUCH-RECORD')->assertOk()->assertJsonPath('data.items.total', 0);
    $this->getJson('/api/v1/aeronautical-information/'.$replacement->id)->assertOk()->assertJsonCount(2, 'data.history');
    $this->get('/aeronautical-information/'.$old->id)->assertInertia(fn ($page) => $page->component('aeronautical-information/show')->has('history', 2));
});

it('accepts authenticated web warning review and keeps historical briefing separate from current compliance', function () {
    aimSync([aimRecord()]);
    $actor = User::factory()->create(['role' => 'super_admin']);
    $mission = aimMission();
    $this->actingAs($actor)->post('/missions/'.$mission->id.'/briefing')->assertRedirect();
    $first = MissionAeronauticalBriefing::where('mission_id', $mission->id)->first();
    $this->post('/missions/'.$mission->id.'/briefing/'.$first->id.'/acknowledge', ['reviewed' => true])->assertRedirect();
    $this->get('/missions/'.$mission->id.'/briefing')->assertInertia(fn ($page) => $page->where('compliance.status', 'amber')->where('compliance.acknowledged', true));
    app(GenerateMissionBriefing::class)->execute($mission, $actor);
    $this->get('/missions/'.$mission->id.'/briefing?revision=1')->assertInertia(fn ($page) => $page->where('briefing.revision', 1)->where('permissions.acknowledge', false)->where('compliance.acknowledged', false));
});

it('prevents ordinary pilots from importing or modifying provider configuration', function () {
    $this->actingAs(User::factory()->create(['role' => 'pilot']))->post('/aeronautical-information/import', [
        'provider' => 'manual', 'file' => UploadedFile::fake()->createWithContent('reference.json', '{}'),
    ])->assertForbidden();
    $this->putJson('/api/v1/aeronautical-providers/atns_aim', ['approved' => true])->assertNotFound();
    expect(config('aeronautical.providers.atns_aim.operational'))->toBeFalse();
});
