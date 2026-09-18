<?php

namespace App\Domains\Uas\Aircraft\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraft;
use App\Domains\Uas\Documents\Application\Queries\EvidenceSummary;

class AircraftPresenter
{
    public static function toArray(UasAircraft $aircraft): array
    {
        $aircraft->loadMissing(['batteries', 'catalogueModel.manufacturer', 'components', 'evidenceLinks.document', 'operators']);
        $readiness = app(AircraftReadinessSummary::class)->execute($aircraft);
        $packageBatteries = $aircraft->batteries->filter(fn ($battery): bool => filled($battery->package_item_key));
        $components = $aircraft->components->sortBy('package_item_key')->values();

        return [
            'id' => $aircraft->id,
            'catalogue_model' => $aircraft->catalogueModel ? AircraftModelPresenter::toArray($aircraft->catalogueModel) : null,
            'registration' => $aircraft->registration,
            'manufacturer' => $aircraft->manufacturer,
            'model' => $aircraft->model,
            'serial_number' => $aircraft->serial_number,
            'internal_asset_number' => $aircraft->internal_asset_number,
            'aircraft_category' => $aircraft->aircraft_category,
            'owner' => $aircraft->owner,
            'operator' => $aircraft->operator,
            'supplier' => $aircraft->supplier,
            'firmware_version' => $aircraft->firmware_version,
            'flight_controller_serial' => $aircraft->flight_controller_serial,
            'remote_id_serial' => $aircraft->remote_id_serial,
            'acquisition_date' => $aircraft->acquisition_date?->toDateString(),
            'operational_status' => $aircraft->operational_status,
            'onboarding_status' => $aircraft->onboarding_status,
            'base_location' => $aircraft->base_location,
            'operator_names' => $aircraft->operators->pluck('legal_entity')->values()->all(),
            'readiness' => $readiness,
            'evidence' => app(EvidenceSummary::class)->for($aircraft),
            'package_instantiation' => [
                'state' => $aircraft->package_instantiation_state,
                'instantiated_at' => $aircraft->package_instantiated_at?->toISOString(),
                'results' => $aircraft->package_instantiation_results ?? [],
                'battery_count' => $packageBatteries->count(),
                'component_count' => $components->count(),
                'maintenance_baseline_count' => collect($components)->filter(fn ($component): bool => filled($component->maintenance_baseline))->count(),
                'batteries' => $packageBatteries->map(fn ($battery): array => [
                    'id' => $battery->id,
                    'battery_uid' => $battery->battery_uid,
                    'package_item_key' => $battery->package_item_key,
                    'model' => $battery->model,
                    'health_status' => $battery->health_status,
                    'retirement_status' => $battery->retirement_status,
                ])->values()->all(),
                'components' => $components->map(fn ($component): array => [
                    'id' => $component->id,
                    'component_uid' => $component->component_uid,
                    'package_item_key' => $component->package_item_key,
                    'component_type' => $component->component_type,
                    'name' => $component->name,
                    'status' => $component->status,
                    'life_limit_hours' => $component->life_limit_hours,
                    'life_limit_cycles' => $component->life_limit_cycles,
                ])->all(),
            ],
        ];
    }
}
