<?php

namespace App\Domains\Uas\Aircraft\Application\Queries;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;

class AircraftModelPresenter
{
    public static function toArray(UasAircraftModel $model): array
    {
        $model->loadMissing('manufacturer');

        return [
            'id' => $model->id,
            'manufacturer' => [
                'id' => $model->manufacturer->id,
                'name' => $model->manufacturer->name,
                'slug' => $model->manufacturer->slug,
            ],
            'model' => $model->model,
            'family' => $model->family,
            'aircraft_type' => $model->aircraft_type,
            'primary_use' => $model->primary_use,
            'status' => $model->status,
            'weight_kg' => $model->weight_kg,
            'mtow_kg' => $model->mtow_kg,
            'max_payload_kg' => $model->max_payload_kg,
            'max_flight_time_min' => $model->max_flight_time_min,
            'max_speed_m_s' => $model->max_speed_m_s,
            'max_range_km' => $model->max_range_km,
            'service_ceiling_m' => $model->service_ceiling_m,
            'max_wind_m_s' => $model->max_wind_m_s,
            'ip_rating' => $model->ip_rating,
            'operating_temp_c' => $model->operating_temp_c,
            'dimensions' => $model->dimensions,
            'wingspan_mm' => $model->wingspan_mm,
            'gnss' => $model->gnss,
            'camera_payload_summary' => $model->camera_payload_summary,
            'remote_id' => $model->remote_id,
            'source_url' => $model->source_url,
            'image_source_url' => $model->image_source_url,
            'image_license_status' => $model->image_license_status,
            'notes' => $model->notes,
            'verified_at' => $model->verified_at?->toDateString(),
            'media_status' => $model->media_status,
            'source_priority' => $model->source_priority,
            'catalogue_status' => $model->catalogue_status,
            'package_status' => $model->package_status,
            'battery_package' => $model->battery_package ?? [],
            'component_package' => $model->component_package ?? [],
            'maintenance_package' => $model->maintenance_package ?? [],
            'package_counts' => [
                'batteries' => collect($model->battery_package ?? [])->sum(fn (array $item): int => max(1, (int) ($item['quantity'] ?? 1))),
                'components' => collect($model->component_package ?? [])->sum(fn (array $item): int => max(1, (int) ($item['quantity'] ?? 1))),
                'maintenance_baselines' => count($model->maintenance_package ?? []),
            ],
        ];
    }
}
