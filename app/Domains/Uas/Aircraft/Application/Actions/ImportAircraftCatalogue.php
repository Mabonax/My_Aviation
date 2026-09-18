<?php

namespace App\Domains\Uas\Aircraft\Application\Actions;

use App\Domains\Uas\Aircraft\Domain\Models\UasAircraftModel;
use App\Domains\Uas\Aircraft\Domain\Models\UasManufacturer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;

class ImportAircraftCatalogue
{
    public function __construct(private readonly ValidationFactory $validator) {}

    public function execute(?string $path = null): array
    {
        $path = $path ?: public_path('uas_drone_catalogue.json');
        $rows = $this->readRows($path);

        $summary = [
            'source_path' => $path,
            'manufacturers_created' => 0,
            'models_created' => 0,
            'models_updated' => 0,
            'models_unchanged' => 0,
            'rows_rejected' => 0,
            'validation_errors' => [],
        ];

        DB::transaction(function () use ($rows, &$summary): void {
            foreach ($rows as $index => $row) {
                $row = $this->normaliseRow($row);
                $validation = $this->validator->make($row, $this->rules());

                if ($validation->fails()) {
                    $summary['rows_rejected']++;
                    $summary['validation_errors'][] = [
                        'row' => $index + 1,
                        'errors' => $validation->errors()->toArray(),
                    ];

                    continue;
                }

                $manufacturer = UasManufacturer::query()->firstOrCreate(
                    ['slug' => Str::slug($row['manufacturer'])],
                    [
                        'name' => $row['manufacturer'],
                        'status' => 'active',
                    ],
                );

                if ($manufacturer->wasRecentlyCreated) {
                    $summary['manufacturers_created']++;
                }

                $attributes = Arr::except($row, ['manufacturer']);
                $attributes['manufacturer_id'] = $manufacturer->id;
                $attributes['catalogue_status'] = $this->catalogueStatus($row['status'] ?? null);

                $model = UasAircraftModel::query()
                    ->where('manufacturer_id', $manufacturer->id)
                    ->where('model', $row['model'])
                    ->first();

                if ($model === null) {
                    UasAircraftModel::query()->create($attributes);
                    $summary['models_created']++;

                    continue;
                }

                $model->fill($attributes);

                if ($model->isDirty()) {
                    $model->save();
                    $summary['models_updated']++;
                } else {
                    $summary['models_unchanged']++;
                }
            }
        });

        return $summary;
    }

    private function readRows(string $path): array
    {
        if (! is_file($path)) {
            throw ValidationException::withMessages(['path' => "Aircraft catalogue source was not found: {$path}"]);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'json') {
            $rows = json_decode((string) file_get_contents($path), true);

            if (! is_array($rows)) {
                throw ValidationException::withMessages(['path' => 'Aircraft catalogue JSON could not be parsed.']);
            }

            return $rows;
        }

        if ($extension === 'csv') {
            return $this->readCsv($path);
        }

        throw ValidationException::withMessages(['path' => 'Use a JSON or CSV aircraft catalogue export for import.']);
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages(['path' => 'Aircraft catalogue CSV could not be opened.']);
        }

        $header = fgetcsv($handle);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($header, $row);
        }

        fclose($handle);

        return $rows;
    }

    private function normaliseRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === '') {
                $row[$key] = null;
            }
        }

        foreach (['weight_kg', 'mtow_kg', 'max_payload_kg', 'max_speed_m_s', 'max_range_km', 'max_wind_m_s'] as $key) {
            $row[$key] = isset($row[$key]) && is_numeric($row[$key]) ? (float) $row[$key] : ($row[$key] ?? null);
        }

        foreach (['max_flight_time_min', 'service_ceiling_m', 'wingspan_mm'] as $key) {
            $row[$key] = isset($row[$key]) && is_numeric($row[$key]) ? (int) $row[$key] : ($row[$key] ?? null);
        }

        return [
            'manufacturer' => trim((string) ($row['manufacturer'] ?? '')),
            'model' => trim((string) ($row['model'] ?? '')),
            'family' => $row['family'] ?? null,
            'aircraft_type' => $row['aircraft_type'] ?? null,
            'primary_use' => $row['primary_use'] ?? null,
            'status' => $row['status'] ?? null,
            'weight_kg' => $row['weight_kg'] ?? null,
            'mtow_kg' => $row['mtow_kg'] ?? null,
            'max_payload_kg' => $row['max_payload_kg'] ?? null,
            'max_flight_time_min' => $row['max_flight_time_min'] ?? null,
            'max_speed_m_s' => $row['max_speed_m_s'] ?? null,
            'max_range_km' => $row['max_range_km'] ?? null,
            'service_ceiling_m' => $row['service_ceiling_m'] ?? null,
            'max_wind_m_s' => $row['max_wind_m_s'] ?? null,
            'ip_rating' => $row['ip_rating'] ?? null,
            'operating_temp_c' => $row['operating_temp_c'] ?? null,
            'dimensions' => $row['dimensions'] ?? null,
            'wingspan_mm' => $row['wingspan_mm'] ?? null,
            'gnss' => $row['gnss'] ?? null,
            'camera_payload_summary' => $row['camera_payload_summary'] ?? null,
            'remote_id' => $row['remote_id'] ?? null,
            'source_url' => $row['source_url'] ?? null,
            'image_source_url' => $row['image_source_url'] ?? null,
            'image_license_status' => $row['image_license_status'] ?? null,
            'notes' => $row['notes'] ?? null,
            'verified_at' => $row['verified_at'] ?? null,
            'media_status' => $row['media_status'] ?? null,
            'source_priority' => $row['source_priority'] ?? null,
            'battery_package' => $this->normalisePackage($row['battery_package'] ?? null),
            'component_package' => $this->normalisePackage($row['component_package'] ?? null),
            'maintenance_package' => $this->normalisePackage($row['maintenance_package'] ?? null),
            'package_status' => $row['package_status'] ?? $this->packageStatus($row),
        ];
    }

    private function rules(): array
    {
        return [
            'manufacturer' => ['required', 'string', 'max:180'],
            'model' => ['required', 'string', 'max:180'],
            'family' => ['nullable', 'string', 'max:180'],
            'aircraft_type' => ['required', 'string', 'max:80'],
            'primary_use' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'max:80'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'mtow_kg' => ['nullable', 'numeric', 'min:0'],
            'max_payload_kg' => ['nullable', 'numeric', 'min:0'],
            'max_flight_time_min' => ['nullable', 'integer', 'min:0'],
            'max_speed_m_s' => ['nullable', 'numeric', 'min:0'],
            'max_range_km' => ['nullable', 'numeric', 'min:0'],
            'service_ceiling_m' => ['nullable', 'integer', 'min:0'],
            'max_wind_m_s' => ['nullable', 'numeric', 'min:0'],
            'ip_rating' => ['nullable', 'string', 'max:80'],
            'operating_temp_c' => ['nullable', 'string', 'max:80'],
            'dimensions' => ['nullable', 'string'],
            'wingspan_mm' => ['nullable', 'integer', 'min:0'],
            'gnss' => ['nullable', 'string'],
            'camera_payload_summary' => ['nullable', 'string'],
            'remote_id' => ['nullable', 'string', 'max:180'],
            'source_url' => ['nullable', 'url'],
            'image_source_url' => ['nullable', 'url'],
            'image_license_status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'verified_at' => ['nullable', 'date'],
            'media_status' => ['nullable', 'string', 'max:180'],
            'source_priority' => ['nullable', 'string', 'max:180'],
            'battery_package' => ['nullable', 'array'],
            'component_package' => ['nullable', 'array'],
            'maintenance_package' => ['nullable', 'array'],
            'package_status' => ['nullable', 'string', 'max:40'],
        ];
    }

    private function catalogueStatus(?string $status): string
    {
        return str($status ?? '')->lower()->contains(['deprecated', 'retired', 'discontinued'])
            ? UasAircraftModel::CATALOGUE_DEPRECATED
            : UasAircraftModel::CATALOGUE_VERIFIED;
    }

    private function normalisePackage(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_values($decoded) : null;
        }

        return null;
    }

    private function packageStatus(array $row): string
    {
        return $this->normalisePackage($row['battery_package'] ?? null) !== null
            || $this->normalisePackage($row['component_package'] ?? null) !== null
            || $this->normalisePackage($row['maintenance_package'] ?? null) !== null
                ? 'configured'
                : 'not_configured';
    }
}
