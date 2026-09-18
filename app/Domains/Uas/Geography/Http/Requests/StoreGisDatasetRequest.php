<?php

namespace App\Domains\Uas\Geography\Http\Requests;

use App\Domains\Uas\Geography\Domain\Models\UasGisProjectMission;
use App\Domains\Uas\Geography\Domain\Services\GisDatasetCatalogue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGisDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $projectMission = $this->route('projectMission');

        return $projectMission instanceof UasGisProjectMission && ($this->user()?->can('update', $projectMission->project) ?? false);
    }

    public function rules(): array
    {
        return [
            'dataset_code' => ['required', 'string', 'max:80', 'unique:uas_gis_datasets,dataset_code'],
            'title' => ['required', 'string', 'max:255'],
            'dataset_type' => ['required', 'string', Rule::in(array_keys(GisDatasetCatalogue::DATASET_TYPES))],
            'capture_source' => ['required', 'string', 'max:120'],
            'storage_uri' => ['required', 'string', 'max:2048'],
            'checksum' => ['nullable', 'string', 'max:255'],
            'coordinate_reference_system' => ['nullable', 'string', 'max:80'],
            'resolution_cm' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'captured_at' => ['nullable', 'date'],
            'processed_at' => ['nullable', 'date', 'after_or_equal:captured_at'],
            'processing_status' => ['required', 'string', Rule::in(array_keys(GisDatasetCatalogue::PROCESSING_STATUSES))],
            'quality_status' => ['required', 'string', Rule::in(array_keys(GisDatasetCatalogue::QUALITY_STATUSES))],
            'provenance_notes' => ['required', 'string', 'max:5000'],
            'evidence_notes' => ['nullable', 'string', 'max:5000'],
            'layers' => ['nullable', 'array'],
            'layers.*.layer_name' => ['required_with:layers', 'string', 'max:255'],
            'layers.*.layer_type' => ['required_with:layers', 'string', Rule::in(array_keys(GisDatasetCatalogue::LAYER_TYPES))],
            'layers.*.geometry_type' => ['required_with:layers', 'string', Rule::in(array_keys(GisDatasetCatalogue::GEOMETRY_TYPES))],
            'layers.*.source_uri' => ['nullable', 'string', 'max:2048'],
            'layers.*.style_metadata' => ['nullable', 'array'],
            'layers.*.analysis_notes' => ['nullable', 'string', 'max:5000'],
            'layers.*.status' => ['required_with:layers', 'string', Rule::in(array_keys(GisDatasetCatalogue::LAYER_STATUSES))],
        ];
    }
}
