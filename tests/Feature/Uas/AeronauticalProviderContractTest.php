<?php

namespace Tests\Feature\Uas;

use App\Domains\Uas\AeronauticalInformation\Application\Actions\SyncAeronauticalInformation;
use App\Domains\Uas\AeronauticalInformation\Application\ProviderRegistry;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalInformationProviderInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Contracts\AeronauticalProviderContract;
use Tests\TestCase;

class AeronauticalProviderContractTest extends TestCase
{
    use AeronauticalProviderContract, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        config(['aeronautical.required_providers' => ['operational_test'], 'aeronautical.providers.operational_test' => [
            'adapter' => \OperationalAeronauticalTestProvider::class, 'operational' => true, 'approved' => true, 'max_age_minutes' => 60,
            'approval_evidence' => array_fill_keys(ProviderRegistry::APPROVAL_EVIDENCE, 'TEST ONLY - conformance harness'),
        ]]);
    }

    protected function provider(): AeronauticalInformationProviderInterface
    {
        return app(ProviderRegistry::class)->resolve('operational_test');
    }

    protected function ingest(array $records = [], array $envelope = []): ProviderSync
    {
        return app(SyncAeronauticalInformation::class)->execute('operational_test', new ProviderRequest(payload: array_replace([
            'records' => $records, 'dataset_timestamp' => now()->toISOString(),
            'coverage' => ['complete' => true, 'information_types' => ['NOTAM'], 'bbox' => [10, -40, 40, -10], 'valid_from' => now()->subDay()->toISOString(), 'valid_until' => now()->addDays(7)->toISOString()],
        ], $envelope)));
    }
}
