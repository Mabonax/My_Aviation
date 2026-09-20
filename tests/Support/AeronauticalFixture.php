<?php

use App\Domains\Uas\AeronauticalInformation\Application\Actions\GenerateMissionBriefing;
use App\Domains\Uas\AeronauticalInformation\Application\Actions\SyncAeronauticalInformation;
use App\Domains\Uas\AeronauticalInformation\Application\ProviderRegistry;
use App\Domains\Uas\AeronauticalInformation\Domain\Contracts\AeronauticalInformationProviderInterface;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderCapabilities;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderDataset;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\DatasetMode;
use App\Domains\Uas\AeronauticalInformation\Domain\Enums\SourceClassification;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\ProviderSync;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Models\User;
use Carbon\CarbonImmutable;

// Test-only approved-feed stand-in. Never registered by production application code.
class OperationalAeronauticalTestProvider implements AeronauticalInformationProviderInterface
{
    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            providerId: $this->key(), providerName: 'Synthetic contract provider', authority: $this->classification(), operational: true,
            informationTypes: ['NOTAM', 'SIGMET', 'AIP_SUPPLEMENT'], coverageTypes: ['bbox'], datasetModes: ['full_snapshot', 'incremental'],
            supportsCancellation: true, supportsReplacement: true, supportsDatasetTimestamp: true,
            supportsGeometry: true, supportsAltitude: true, supportsFir: true, supportsAerodrome: true,
        );
    }

    public function key(): string
    {
        return 'operational_test';
    }

    public function classification(): SourceClassification
    {
        return SourceClassification::OfficialLive;
    }

    public function usableForRelease(): bool
    {
        return true;
    }

    public function fetch(ProviderRequest $request): ProviderDataset
    {
        return new ProviderDataset($request->payload['records'] ?? [], CarbonImmutable::parse($request->payload['dataset_timestamp'] ?? now()), $request->payload['coverage'] ?? [], DatasetMode::from($request->payload['dataset_mode'] ?? 'full_snapshot'), $request->payload['metadata'] ?? []);
    }
}

function aimSync(array $records = [], array $overrides = []): ProviderSync
{
    config(['aeronautical.providers.operational_test' => ['adapter' => OperationalAeronauticalTestProvider::class, 'max_age_minutes' => 60, 'operational' => true, 'approved' => true, 'approval_evidence' => array_fill_keys(ProviderRegistry::APPROVAL_EVIDENCE, 'TEST ONLY - provider conformance fixture')], 'aeronautical.required_providers' => ['operational_test']]);

    return app(SyncAeronauticalInformation::class)->execute('operational_test', new ProviderRequest(payload: array_merge([
        'records' => $records, 'dataset_timestamp' => now()->toISOString(),
        'coverage' => ['complete' => true, 'information_types' => ['NOTAM', 'SIGMET'], 'bbox' => [10, -40, 40, -10], 'valid_from' => now()->subDay()->toISOString(), 'valid_until' => now()->addDays(7)->toISOString()],
    ], $overrides)));
}

function aimPrepareMission(UasMission $mission, ?User $actor = null): void
{
    if (! ProviderSync::query()->where('provider', 'operational_test')->exists()) {
        aimSync();
    }
    $mission->update(['aeronautical_context' => ['altitude_reference' => 'AGL']]);
    app(GenerateMissionBriefing::class)->execute($mission, $actor ?? User::factory()->create(['role' => 'super_admin']));
}

function aimRecord(array $overrides = []): array
{
    return array_replace_recursive([
        'source_identifier' => 'TEST-A0001/26', 'source_revision' => '1', 'information_type' => 'NOTAM',
        'title' => 'Synthetic test notice - not operational information',
        'raw_message' => 'TEST ONLY Q) FAJA/QWALW/IV/NBO/W/000/040/2400S02700E005',
        'issued_at' => now()->subHour()->toISOString(), 'effective_from' => now()->subDay()->toISOString(), 'effective_until' => now()->addDays(3)->toISOString(),
        'normalized' => ['latitude' => -24, 'longitude' => 27, 'radius_nm' => 1, 'geometry_type' => 'circle',
            'lower_limit_value' => 0, 'lower_limit_unit' => 'FT', 'lower_limit_reference' => 'AGL',
            'upper_limit_value' => 1000, 'upper_limit_unit' => 'FT', 'upper_limit_reference' => 'AGL', 'hazard' => 'warning'],
    ], $overrides);
}

function aimMission(array $overrides = []): UasMission
{
    $operator = $overrides['operator'] ?? UasOperator::query()->create([
        'legal_entity' => 'AIM Test Operator '.str()->upper(str()->random(5)),
        'trading_name' => 'AIM Test Operator',
        'status' => 'active',
        'accountable_manager' => 'AIM Accountable Manager',
        'responsible_person_flight_operations' => 'AIM Flight Operations',
        'responsible_person_aircraft' => 'AIM Aircraft Lead',
        'regulatory_source' => 'TEST ONLY FR-AIM',
        'regulatory_source_version' => '1.0',
        'regulatory_effective_date' => '2026-09-15',
        'regulatory_applicability' => 'Aeronautical information test tenancy fixture.',
        'responsible_role' => 'Accountable Manager',
    ]);

    return UasMission::query()->create(array_merge([
        'uas_operator_id' => $operator->id,
        'mission_number' => 'AIM-'.str()->random(10), 'purpose' => 'Synthetic AIM tests', 'location' => 'Test range', 'operation_category' => 'inspection',
        'latitude' => -24, 'longitude' => 27, 'planned_start_at' => now()->addHour(), 'planned_end_at' => now()->addHours(2),
        'maximum_altitude_ft' => 400, 'aeronautical_context' => ['altitude_reference' => 'AGL'], 'lifecycle_state' => 'approved',
        'release_gate_state' => 'amber', 'operation_visibility' => 'vlos', 'day_night' => 'day',
        'regulatory_source' => 'TEST ONLY FR-AIM', 'regulatory_source_version' => '1.0', 'regulatory_effective_date' => '2026-09-15',
        'regulatory_applicability' => 'Automated test only', 'responsible_role' => 'Test operator',
    ], \Illuminate\Support\Arr::except($overrides, ['operator'])));
}
