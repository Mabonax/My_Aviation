<?php

namespace App\Domains\Uas\Aircraft\Application\Actions;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftComponent;
use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Domains\Uas\Batteries\Domain\Models\UasBattery;
use App\Domains\Uas\Batteries\Domain\Services\BatteryHealthEvaluator;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class InstantiateAircraftPackage
{
    private const TRACEABILITY = [
        'regulatory_source' => 'UAS Compliance & Operations Platform FRS FR-AIR-001 and FR-BAT-001',
        'regulatory_source_version' => 'FRS v1.0, dated 2026-09-09',
        'regulatory_effective_date' => '2026-09-09',
        'regulatory_applicability' => 'Catalogue package instantiation for physical UAS batteries, components and maintenance baseline evidence.',
    ];

    public function __construct(
        private readonly BatteryHealthEvaluator $batteryHealth,
        private readonly RecordAuditEntry $recordAuditEntry,
    ) {}

    public function execute(UasAircraft $aircraft, ?UasAircraftModel $model, User $actor, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($aircraft, $model, $actor, $ipAddress, $userAgent): array {
            $aircraft->refresh();

            if ($aircraft->package_instantiated_at && filled($aircraft->package_instantiation_results)) {
                return $aircraft->package_instantiation_results;
            }

            if ($model === null) {
                return $this->markAircraft($aircraft, [
                    'state' => 'not_applicable',
                    'battery_count' => 0,
                    'component_count' => 0,
                    'maintenance_baseline_count' => 0,
                    'source_aircraft_model_id' => null,
                    'source' => 'custom_aircraft',
                ], $actor, $ipAddress, $userAgent);
            }

            $model->refresh();
            $batteryItems = collect($model->battery_package ?? []);
            $componentItems = collect($model->component_package ?? []);
            $maintenanceItems = collect($model->maintenance_package ?? []);

            if ($batteryItems->isEmpty() && $componentItems->isEmpty() && $maintenanceItems->isEmpty()) {
                return $this->markAircraft($aircraft, [
                    'state' => 'no_package_definition',
                    'battery_count' => 0,
                    'component_count' => 0,
                    'maintenance_baseline_count' => 0,
                    'source_aircraft_model_id' => $model->id,
                    'source' => 'catalogue_model',
                ], $actor, $ipAddress, $userAgent);
            }

            $createdBatteries = $this->instantiateBatteries($aircraft, $model, $batteryItems->all());
            $createdComponents = $this->instantiateComponents($aircraft, $model, $componentItems->all(), $maintenanceItems->all());

            return $this->markAircraft($aircraft, [
                'state' => 'instantiated',
                'battery_count' => count($createdBatteries),
                'component_count' => count($createdComponents),
                'maintenance_baseline_count' => $maintenanceItems->count(),
                'battery_ids' => collect($createdBatteries)->pluck('id')->values()->all(),
                'component_ids' => collect($createdComponents)->pluck('id')->values()->all(),
                'source_aircraft_model_id' => $model->id,
                'source_model' => trim($model->manufacturer?->name.' '.$model->model),
            ], $actor, $ipAddress, $userAgent);
        });
    }

    private function instantiateBatteries(UasAircraft $aircraft, UasAircraftModel $model, array $items): array
    {
        $created = [];
        $sequence = 1;

        foreach ($items as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            for ($index = 1; $index <= $quantity; $index++) {
                $key = $this->packageKey($item, 'battery', $sequence, $index);
                $battery = UasBattery::query()->firstOrCreate(
                    [
                        'compatible_uas_aircraft_id' => $aircraft->id,
                        'package_item_key' => $key,
                    ],
                    [
                        'battery_uid' => $this->assetUid($aircraft, 'BAT', $sequence),
                        'manufacturer' => $item['manufacturer'] ?? $model->manufacturer?->name ?? $aircraft->manufacturer,
                        'model' => $item['model'] ?? $item['name'] ?? 'Catalogue battery',
                        'serial_number' => $this->assetUid($aircraft, 'BAT-SN', $sequence),
                        'source_aircraft_model_id' => $model->id,
                        'package_instantiated_at' => now(),
                        'cycle_count' => (int) ($item['initial_cycle_count'] ?? 0),
                        'maximum_cycles' => isset($item['maximum_cycles']) ? (int) $item['maximum_cycles'] : null,
                        'health_status' => 'unknown',
                        'retirement_status' => 'active',
                        'charge_history' => [],
                        'evidence_references' => $this->evidence($model, $item),
                        ...self::TRACEABILITY,
                    ],
                );

                if ($battery->wasRecentlyCreated) {
                    $battery->forceFill(['health_status' => $this->batteryHealth->status($battery)])->save();
                    $created[] = $battery->fresh();
                }

                $sequence++;
            }
        }

        return $created;
    }

    private function instantiateComponents(UasAircraft $aircraft, UasAircraftModel $model, array $items, array $maintenanceItems): array
    {
        $created = [];
        $sequence = 1;

        foreach ($items as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            for ($index = 1; $index <= $quantity; $index++) {
                $key = $this->packageKey($item, 'component', $sequence, $index);
                $component = UasAircraftComponent::query()->firstOrCreate(
                    [
                        'uas_aircraft_id' => $aircraft->id,
                        'package_item_key' => $key,
                    ],
                    [
                        'source_aircraft_model_id' => $model->id,
                        'component_uid' => $this->assetUid($aircraft, 'CMP', $sequence),
                        'component_type' => $item['component_type'] ?? $item['type'] ?? 'component',
                        'name' => $item['name'] ?? $item['model'] ?? 'Catalogue component',
                        'manufacturer' => $item['manufacturer'] ?? $model->manufacturer?->name,
                        'model' => $item['model'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'installed_at' => $aircraft->acquisition_date ?? now(),
                        'life_limit_hours' => isset($item['life_limit_hours']) ? (float) $item['life_limit_hours'] : null,
                        'life_limit_cycles' => isset($item['life_limit_cycles']) ? (int) $item['life_limit_cycles'] : null,
                        'accumulated_hours' => (float) ($item['accumulated_hours'] ?? 0),
                        'accumulated_cycles' => (int) ($item['accumulated_cycles'] ?? 0),
                        'status' => 'active',
                        'maintenance_baseline' => $this->componentMaintenanceBaseline($item, $maintenanceItems),
                        'evidence_references' => $this->evidence($model, $item),
                    ],
                );

                if ($component->wasRecentlyCreated) {
                    $created[] = $component;
                }

                $sequence++;
            }
        }

        return $created;
    }

    private function markAircraft(UasAircraft $aircraft, array $results, User $actor, ?string $ipAddress, ?string $userAgent): array
    {
        $results = array_merge($results, [
            'instantiated_at' => now()->toISOString(),
            'instantiated_by' => $actor->id,
        ]);

        $previous = $aircraft->only(['package_instantiation_state', 'package_instantiated_at', 'package_instantiation_results']);
        $aircraft->forceFill([
            'package_instantiation_state' => $results['state'],
            'package_instantiated_at' => now(),
            'package_instantiation_results' => $results,
        ])->save();

        $this->recordAuditEntry->execute(new AuditEntryData(
            actor: $actor,
            auditable: $aircraft,
            action: 'aircraft.package_instantiated',
            requirementId: 'FR-AIR-001',
            regulatorySource: self::TRACEABILITY['regulatory_source'],
            previousValues: $previous,
            newValues: $results,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        ));

        return $results;
    }

    private function packageKey(array $item, string $prefix, int $sequence, int $index): string
    {
        $base = $item['key'] ?? $item['package_item_key'] ?? $item['name'] ?? "{$prefix}-{$sequence}";

        return str($base.'-'.$index)->slug()->limit(60, '')->toString();
    }

    private function assetUid(UasAircraft $aircraft, string $prefix, int $sequence): string
    {
        return str($aircraft->registration.'-'.$prefix.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT))
            ->replace(' ', '-')
            ->upper()
            ->toString();
    }

    private function evidence(UasAircraftModel $model, array $item): array
    {
        return [
            'source' => 'aircraft_model_package',
            'source_aircraft_model_id' => $model->id,
            'source_url' => $model->source_url,
            'package_item' => Arr::except($item, ['serial_number']),
        ];
    }

    private function componentMaintenanceBaseline(array $item, array $maintenanceItems): array
    {
        return [
            'package_item' => Arr::except($item, ['serial_number']),
            'maintenance_package' => $maintenanceItems,
            'note' => 'Baseline only; first-class maintenance scheduling is a future workflow.',
        ];
    }
}

