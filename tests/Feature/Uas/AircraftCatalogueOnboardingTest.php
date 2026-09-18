<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Aircraft\Application\Actions\CreatePhysicalAircraft;
use App\Domains\Uas\Aircraft\Application\Actions\ImportAircraftCatalogue;
use App\Domains\Uas\Aircraft\Application\Actions\InstantiateAircraftPackage;
use App\Domains\Uas\Aircraft\Application\Queries\AircraftReadinessSummary;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftApproval;
use App\Domains\Uas\Aircraft\Domain\Models\AircraftRegistration;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftComponent;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Domains\Uas\Aircraft\Domain\Models\UasManufacturer;
use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Defects\Domain\Models\UasAircraftDefect;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use App\Domains\Uas\Operators\Domain\Models\UasOperatorMembership;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

function aircraftCatalogueUser(array $permissions = [], array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    if ($permissions !== []) {
        $role = UasRole::query()->create([
            'name' => 'aircraft-catalogue-'.uniqid(),
            'label' => 'Aircraft Catalogue Test Role',
            'permissions' => $permissions,
        ]);

        $role->users()->attach($user);
    }

    return $user;
}

function aircraftCatalogueOperator(array $overrides = []): UasOperator
{
    return UasOperator::query()->create(array_merge([
        'legal_entity' => 'Catalogue Operator '.str()->upper(str()->random(5)),
        'trading_name' => null,
        'registration_number' => 'CAT-'.str()->upper(str()->random(6)),
        'uasoc_number' => 'UASOC-CAT-'.str()->upper(str()->random(4)),
        'certificate_issue_date' => '2026-01-01',
        'certificate_expiry_date' => '2027-01-01',
        'status' => 'active',
        'accountable_manager' => 'Accountable Manager',
        'responsible_person_flight_operations' => 'Flight Operations Lead',
        'responsible_person_aircraft' => 'Aircraft Lead',
        'safety_manager' => 'Safety Manager',
        'security_coordinator' => 'Security Coordinator',
        'operating_bases' => ['Midrand'],
        'approved_aircraft' => [],
        'approved_pilots' => [],
        'operations_specifications' => ['VLOS'],
        'evidence_references' => [],
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-AIR-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Aircraft catalogue onboarding test record.',
        'responsible_role' => 'Accountable Manager',
    ], $overrides));
}

function aircraftCataloguePhysicalAircraft(array $overrides = []): UasAircraft
{
    return UasAircraft::query()->create(array_merge([
        'registration' => 'ZT-CAT-'.str()->upper(str()->random(5)),
        'manufacturer' => 'VMT',
        'model' => 'Surveyor Two',
        'serial_number' => 'SN-CAT-'.str()->upper(str()->random(8)),
        'operational_status' => 'active_serviceable',
    ], $overrides));
}

function aircraftCatalogueRegistration(UasAircraft $aircraft, array $overrides = []): AircraftRegistration
{
    return AircraftRegistration::query()->create(array_merge([
        'uas_aircraft_id' => $aircraft->id,
        'registration_number' => $aircraft->registration,
        'lifecycle_state' => 'active',
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'evidence_references' => ['registration-certificate.pdf'],
    ], $overrides));
}

function aircraftCatalogueApproval(UasAircraft $aircraft, array $overrides = []): AircraftApproval
{
    return AircraftApproval::query()->create(array_merge([
        'uas_aircraft_id' => $aircraft->id,
        'approval_type' => 'uasla',
        'approval_number' => 'UASLA-CAT-'.str()->upper(str()->random(6)),
        'issue_date' => now()->subMonth()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'scope' => 'VLOS operations',
        'restrictions' => null,
        'status' => 'active',
        'evidence_references' => ['uasla.pdf'],
    ], $overrides));
}

function aircraftCatalogueBattery(UasAircraft $aircraft, array $overrides = []): UasBattery
{
    return UasBattery::query()->create(array_merge([
        'battery_uid' => 'BAT-CAT-'.str()->upper(str()->random(6)),
        'manufacturer' => 'DJI',
        'model' => 'TB-series',
        'serial_number' => 'BAT-SN-CAT-'.str()->upper(str()->random(8)),
        'compatible_uas_aircraft_id' => $aircraft->id,
        'cycle_count' => 12,
        'maximum_cycles' => 200,
        'health_status' => 'normal',
        'retirement_status' => 'active',
        'damage_incidents' => null,
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-BAT-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Aircraft readiness battery test record.',
    ], $overrides));
}

function aircraftCatalogueDefect(UasAircraft $aircraft, array $overrides = []): UasAircraftDefect
{
    return UasAircraftDefect::query()->create(array_merge([
        'uas_aircraft_id' => $aircraft->id,
        'reported_by' => null,
        'defect_number' => 'DEF-CAT-'.str()->upper(str()->random(6)),
        'source' => 'inspection',
        'severity' => 'minor',
        'status' => 'open',
        'serviceability_impact' => 'none',
        'title' => 'Minor readiness note',
        'description' => 'Captured for readiness summary testing.',
        'reported_at' => now(),
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-DEF-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Aircraft readiness defect test record.',
    ], $overrides));
}

it('imports the governed aircraft catalogue idempotently without duplicate manufacturers or models', function () {
    $first = app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));
    $second = app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));

    expect($first['manufacturers_created'])->toBe(13)
        ->and($first['models_created'])->toBe(26)
        ->and($first['rows_rejected'])->toBe(0)
        ->and($second['manufacturers_created'])->toBe(0)
        ->and($second['models_created'])->toBe(0)
        ->and($second['models_unchanged'])->toBe(26)
        ->and(UasManufacturer::query()->count())->toBe(13)
        ->and(UasAircraftModel::query()->count())->toBe(26);
});

it('preserves aircraft catalogue source metadata nulls and verification dates', function () {
    app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));

    $model = UasAircraftModel::query()
        ->where('model', 'Matrice 350 RTK')
        ->with('manufacturer')
        ->firstOrFail();

    expect($model->manufacturer->name)->toBe('DJI')
        ->and($model->catalogue_status)->toBe(UasAircraftModel::CATALOGUE_VERIFIED)
        ->and($model->max_flight_time_min)->toBe(55)
        ->and($model->wingspan_mm)->toBeNull()
        ->and($model->source_url)->toStartWith('https://')
        ->and($model->image_source_url)->toStartWith('https://')
        ->and($model->image_license_status)->not->toBeEmpty()
        ->and($model->verified_at?->toDateString())->toBe('2026-09-11');
});

it('rejects malformed catalogue rows without importing partial aircraft model data', function () {
    $path = storage_path('framework/testing/malformed-aircraft-catalogue.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode([
        [
            'manufacturer' => 'Bad Source',
            'model' => 'Broken Numeric Payload',
            'aircraft_type' => 'Multirotor',
            'mtow_kg' => 'not-a-number',
            'source_url' => 'not-a-url',
        ],
    ], JSON_THROW_ON_ERROR));

    $summary = app(ImportAircraftCatalogue::class)->execute($path);

    expect($summary['rows_rejected'])->toBe(1)
        ->and($summary['models_created'])->toBe(0)
        ->and($summary['validation_errors'][0]['errors'])->toHaveKeys(['mtow_kg', 'source_url'])
        ->and(UasAircraftModel::query()->where('model', 'Broken Numeric Payload')->exists())->toBeFalse();
});

it('links physical aircraft to catalogue models while preserving legacy aircraft without a model link', function () {
    app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));
    $model = UasAircraftModel::query()->where('model', 'Matrice 400')->firstOrFail();

    $linked = aircraftCataloguePhysicalAircraft([
        'aircraft_model_id' => $model->id,
        'registration' => 'ZT-LINK-001',
        'manufacturer' => 'DJI',
        'model' => 'Matrice 400',
        'serial_number' => 'SN-LINK-001',
        'internal_asset_number' => 'ASSET-001',
    ]);
    $legacy = aircraftCataloguePhysicalAircraft([
        'registration' => 'ZT-LEGACY-001',
        'serial_number' => 'SN-LEGACY-001',
    ]);

    expect($linked->catalogueModel()->first()?->is($model))->toBeTrue()
        ->and($legacy->catalogueModel)->toBeNull();
});

it('derives green aircraft readiness from catalogue registration approval defect and battery controls', function () {
    app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));
    $model = UasAircraftModel::query()->where('model', 'Matrice 400')->firstOrFail();
    $aircraft = aircraftCataloguePhysicalAircraft([
        'aircraft_model_id' => $model->id,
        'registration' => 'ZT-READY-GREEN',
        'manufacturer' => 'DJI',
        'model' => 'Matrice 400',
        'serial_number' => 'SN-READY-GREEN',
        'operational_status' => 'active_serviceable',
    ]);
    aircraftCatalogueRegistration($aircraft);
    aircraftCatalogueApproval($aircraft);
    aircraftCatalogueBattery($aircraft);

    $summary = app(AircraftReadinessSummary::class)->execute($aircraft);

    expect($summary['status'])->toBe('green')
        ->and($summary['label'])->toBe('Ready')
        ->and($summary['blocking_reasons'])->toBeEmpty()
        ->and(collect($summary['checks'])->pluck('status')->unique()->values()->all())->toBe(['green']);
});

it('derives amber aircraft readiness when review evidence is incomplete or approaching limits', function () {
    $aircraft = aircraftCataloguePhysicalAircraft([
        'registration' => 'ZT-READY-AMBER',
        'serial_number' => 'SN-READY-AMBER',
        'operational_status' => 'active_serviceable',
    ]);
    aircraftCatalogueRegistration($aircraft, ['expiry_date' => now()->addDays(20)->toDateString()]);
    aircraftCatalogueApproval($aircraft, ['expiry_date' => now()->addDays(20)->toDateString()]);
    aircraftCatalogueBattery($aircraft, ['cycle_count' => 12, 'maximum_cycles' => 100]);
    aircraftCatalogueBattery($aircraft, ['cycle_count' => 91, 'maximum_cycles' => 100]);
    aircraftCatalogueDefect($aircraft);

    $summary = app(AircraftReadinessSummary::class)->execute($aircraft);

    expect($summary['status'])->toBe('amber')
        ->and($summary['blocking_reasons'])->toBeEmpty()
        ->and($summary['review_reasons'])->not->toBeEmpty()
        ->and(collect($summary['checks'])->where('code', 'catalogue_model')->first()['status'])->toBe('amber')
        ->and(collect($summary['checks'])->where('code', 'batteries')->first()['status'])->toBe('amber');
});

it('derives red aircraft readiness from blocking serviceability compliance and defect failures', function () {
    $aircraft = aircraftCataloguePhysicalAircraft([
        'registration' => 'ZT-READY-RED',
        'serial_number' => 'SN-READY-RED',
        'operational_status' => 'grounded',
    ]);
    aircraftCatalogueRegistration($aircraft, ['expiry_date' => now()->subDay()->toDateString()]);
    aircraftCatalogueDefect($aircraft, [
        'severity' => 'ground_aircraft',
        'serviceability_impact' => 'grounded',
        'title' => 'Grounding defect',
    ]);
    aircraftCatalogueBattery($aircraft, ['cycle_count' => 100, 'maximum_cycles' => 100]);

    $summary = app(AircraftReadinessSummary::class)->execute($aircraft);

    expect($summary['status'])->toBe('red')
        ->and($summary['label'])->toBe('Not ready')
        ->and($summary['blocking_reasons'])->not->toBeEmpty()
        ->and(collect($summary['checks'])->where('code', 'serviceability')->first()['status'])->toBe('red')
        ->and(collect($summary['checks'])->where('code', 'registration')->first()['status'])->toBe('red')
        ->and(collect($summary['checks'])->where('code', 'uasla_approval')->first()['status'])->toBe('red')
        ->and(collect($summary['checks'])->where('code', 'defects')->first()['status'])->toBe('red')
        ->and(collect($summary['checks'])->where('code', 'batteries')->first()['status'])->toBe('red');
});

it('serves catalogue list detail filters and deprecated records through the API envelope', function () {
    app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));
    $manufacturer = UasManufacturer::query()->where('name', 'DJI')->firstOrFail();
    $deprecated = UasAircraftModel::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'model' => 'Legacy Test Airframe',
        'aircraft_type' => 'Fixed-wing',
        'status' => 'Discontinued',
        'catalogue_status' => UasAircraftModel::CATALOGUE_DEPRECATED,
    ]);

    Sanctum::actingAs(aircraftCatalogueUser());

    $this->getJson('/api/v1/aircraft-catalogue?search=Matrice%20350&manufacturer=dji&aircraft_type=Multirotor')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.aircraft_models.0.model', 'Matrice 350 RTK')
        ->assertJsonPath('data.aircraft_models.0.manufacturer.name', 'DJI')
        ->assertJsonPath('meta.contract_version', 'v1.0');

    $this->getJson('/api/v1/aircraft-catalogue?catalogue_status=deprecated')
        ->assertOk()
        ->assertJsonFragment(['model' => 'Legacy Test Airframe'])
        ->assertJsonMissing(['model' => 'Matrice 350 RTK']);

    $this->getJson("/api/v1/aircraft-catalogue/{$deprecated->id}")
        ->assertOk()
        ->assertJsonPath('data.aircraft_model.catalogue_status', UasAircraftModel::CATALOGUE_DEPRECATED);
});

it('returns catalogue summaries on scoped physical aircraft API records and blocks unauthorized aircraft', function () {
    app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));
    $member = aircraftCatalogueUser();
    $operatorA = aircraftCatalogueOperator(['legal_entity' => 'Scoped Catalogue Operator A']);
    $operatorB = aircraftCatalogueOperator(['legal_entity' => 'Scoped Catalogue Operator B']);
    $model = UasAircraftModel::query()->where('model', 'Matrice 30T')->firstOrFail();
    $aircraftA = aircraftCataloguePhysicalAircraft([
        'aircraft_model_id' => $model->id,
        'registration' => 'ZT-SCOPE-A',
        'manufacturer' => 'DJI',
        'model' => 'Matrice 30T',
        'serial_number' => 'SN-SCOPE-A',
    ]);
    $aircraftB = aircraftCataloguePhysicalAircraft([
        'registration' => 'ZT-SCOPE-B',
        'serial_number' => 'SN-SCOPE-B',
    ]);

    UasOperatorMembership::query()->create([
        'uas_operator_id' => $operatorA->id,
        'user_id' => $member->id,
        'membership_role' => 'remote_pilot',
        'status' => 'active',
    ]);
    $operatorA->aircraft()->attach($aircraftA->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);
    $operatorB->aircraft()->attach($aircraftB->id, ['assignment_role' => 'operated_aircraft', 'status' => 'active']);

    Sanctum::actingAs($member);

    $this->getJson('/api/v1/aircraft')
        ->assertOk()
        ->assertJsonPath('data.aircraft.0.registration', 'ZT-SCOPE-A')
        ->assertJsonPath('data.aircraft.0.catalogue_model.model', 'Matrice 30T')
        ->assertJsonMissing(['registration' => 'ZT-SCOPE-B']);

    $this->getJson("/api/v1/aircraft/{$aircraftA->id}")
        ->assertOk()
        ->assertJsonPath('data.aircraft.catalogue_model.model', 'Matrice 30T')
        ->assertJsonPath('data.aircraft.readiness.status', 'red')
        ->assertJsonPath('data.aircraft.readiness.checks.0.code', 'catalogue_model');

    $this->getJson("/api/v1/aircraft/{$aircraftB->id}")
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

it('instantiates batteries components and maintenance baseline from catalogue package definitions during onboarding', function () {
    $admin = aircraftCatalogueUser([], ['role' => 'super_admin']);
    $operator = aircraftCatalogueOperator(['legal_entity' => 'Package Operator']);
    $manufacturer = UasManufacturer::query()->create(['name' => 'DJI', 'slug' => 'dji', 'status' => 'active']);
    $model = UasAircraftModel::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'model' => 'Package Test UAS',
        'aircraft_type' => 'Multirotor',
        'catalogue_status' => UasAircraftModel::CATALOGUE_VERIFIED,
        'battery_package' => [
            ['key' => 'tb67', 'name' => 'TB67 flight battery', 'model' => 'TB67', 'quantity' => 2, 'maximum_cycles' => 200],
        ],
        'component_package' => [
            ['key' => 'camera-h20t', 'component_type' => 'payload', 'name' => 'H20T payload', 'life_limit_hours' => 500],
            ['key' => 'propeller-set', 'component_type' => 'propulsion', 'name' => 'Propeller set', 'quantity' => 2, 'life_limit_cycles' => 150],
        ],
        'maintenance_package' => [
            ['key' => 'preflight', 'name' => 'Pre-flight inspection', 'interval' => 'each_flight'],
        ],
        'package_status' => 'configured',
    ]);

    $aircraft = app(CreatePhysicalAircraft::class)->execute([
        'aircraft_model_id' => $model->id,
        'uas_operator_id' => $operator->id,
        'registration' => 'ZT-PKG-001',
        'serial_number' => 'SN-PKG-001',
        'aircraft_category' => 'uas',
        'operational_status' => 'pending_registration',
        'onboarding_status' => 'onboarding',
    ], $admin);

    $aircraft->refresh();

    expect($aircraft->package_instantiation_state)->toBe('instantiated')
        ->and($aircraft->package_instantiation_results['battery_count'])->toBe(2)
        ->and($aircraft->package_instantiation_results['component_count'])->toBe(3)
        ->and(UasBattery::query()->where('compatible_uas_aircraft_id', $aircraft->id)->where('source_aircraft_model_id', $model->id)->count())->toBe(2)
        ->and(UasAircraftComponent::query()->where('uas_aircraft_id', $aircraft->id)->where('source_aircraft_model_id', $model->id)->count())->toBe(3)
        ->and(UasAuditEntry::query()->where('action', 'aircraft.package_instantiated')->where('auditable_id', $aircraft->id)->exists())->toBeTrue();

    $this->assertDatabaseHas('uas_batteries', [
        'compatible_uas_aircraft_id' => $aircraft->id,
        'package_item_key' => 'tb67-1',
        'battery_uid' => 'ZT-PKG-001-BAT-01',
        'health_status' => 'serviceable',
    ]);
    $this->assertDatabaseHas('uas_aircraft_components', [
        'uas_aircraft_id' => $aircraft->id,
        'package_item_key' => 'camera-h20t-1',
        'component_type' => 'payload',
        'name' => 'H20T payload',
    ]);

    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/aircraft/{$aircraft->id}")
        ->assertOk()
        ->assertJsonPath('data.aircraft.package_instantiation.state', 'instantiated')
        ->assertJsonPath('data.aircraft.package_instantiation.battery_count', 2)
        ->assertJsonPath('data.aircraft.package_instantiation.component_count', 3)
        ->assertJsonPath('data.aircraft.package_instantiation.components.0.name', 'H20T payload');
});

it('does not duplicate package assets when aircraft package instantiation is repeated', function () {
    $admin = aircraftCatalogueUser([], ['role' => 'super_admin']);
    $manufacturer = UasManufacturer::query()->create(['name' => 'Package Maker', 'slug' => 'package-maker', 'status' => 'active']);
    $model = UasAircraftModel::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'model' => 'Repeatable Package UAS',
        'aircraft_type' => 'Multirotor',
        'catalogue_status' => UasAircraftModel::CATALOGUE_VERIFIED,
        'battery_package' => [['key' => 'standard-pack', 'model' => 'SP-1', 'quantity' => 1]],
        'component_package' => [['key' => 'payload', 'component_type' => 'payload', 'name' => 'Survey payload']],
        'package_status' => 'configured',
    ]);
    $aircraft = aircraftCataloguePhysicalAircraft([
        'aircraft_model_id' => $model->id,
        'registration' => 'ZT-PKG-002',
        'serial_number' => 'SN-PKG-002',
    ]);

    $first = app(InstantiateAircraftPackage::class)->execute($aircraft, $model, $admin);
    $second = app(InstantiateAircraftPackage::class)->execute($aircraft->fresh(), $model, $admin);

    expect($first['state'])->toBe('instantiated')
        ->and($second['state'])->toBe('instantiated')
        ->and(UasBattery::query()->where('compatible_uas_aircraft_id', $aircraft->id)->count())->toBe(1)
        ->and(UasAircraftComponent::query()->where('uas_aircraft_id', $aircraft->id)->count())->toBe(1)
        ->and(UasAuditEntry::query()->where('action', 'aircraft.package_instantiated')->where('auditable_id', $aircraft->id)->count())->toBe(1);
});

it('renders catalogue onboarding screens and creates physical aircraft from a catalogue model', function () {
    $this->withoutVite();

    app(ImportAircraftCatalogue::class)->execute(public_path('uas_drone_catalogue.json'));
    $admin = aircraftCatalogueUser([], ['role' => 'super_admin']);
    $operator = aircraftCatalogueOperator(['legal_entity' => 'Onboarding Operator']);
    $model = UasAircraftModel::query()->where('model', 'Matrice 4T')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('aircraft-catalogue.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('aircraft/catalogue/index'));

    $this->actingAs($admin)
        ->get(route('aircraft-catalogue.show', $model))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('aircraft/catalogue/show')
            ->where('model.model', 'Matrice 4T')
        );

    $this->actingAs($admin)
        ->get(route('aircraft.create', ['aircraft_model_id' => $model->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('aircraft/create'));

    $this->actingAs($admin)
        ->post(route('aircraft.store'), [
            'aircraft_model_id' => $model->id,
            'uas_operator_id' => $operator->id,
            'registration' => 'ZT-ONBOARD-01',
            'serial_number' => 'SN-ONBOARD-01',
            'aircraft_category' => 'uas',
            'operational_status' => 'pending_registration',
            'onboarding_status' => 'onboarding',
            'internal_asset_number' => 'YAW-UAS-001',
            'base_location' => 'Midrand',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('uas_aircraft', [
        'registration' => 'ZT-ONBOARD-01',
        'aircraft_model_id' => $model->id,
        'manufacturer' => 'DJI',
        'model' => 'Matrice 4T',
    ]);
    $this->assertDatabaseHas('uas_operator_aircraft', [
        'uas_operator_id' => $operator->id,
        'status' => 'active',
    ]);
});
