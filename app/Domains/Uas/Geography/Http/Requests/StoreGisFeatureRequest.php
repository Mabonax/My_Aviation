<?php

namespace App\Domains\Uas\Geography\Http\Requests;

use App\Domains\Uas\Geography\Domain\Models\UasGisSpatialLayer;
use App\Domains\Uas\Geography\Domain\Services\GisFeatureCatalogue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGisFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $spatialLayer = $this->route('spatialLayer');

        return $spatialLayer instanceof UasGisSpatialLayer && ($this->user()?->can('update', $spatialLayer->dataset->projectMission->project) ?? false);
    }

    public function rules(): array
    {
        return [
            'feature_code' => ['required', 'string', 'max:80', 'unique:uas_gis_features,feature_code'],
            'name' => ['required', 'string', 'max:255'],
            'feature_type' => ['required', 'string', Rule::in(array_keys(GisFeatureCatalogue::FEATURE_TYPES))],
            'geometry_reference' => ['required', 'string', 'max:5000'],
            'confidence_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'verification_status' => ['required', 'string', Rule::in(array_keys(GisFeatureCatalogue::VERIFICATION_STATUSES))],
            'interpretation_notes' => ['required', 'string', 'max:5000'],
            'evidence_notes' => ['nullable', 'string', 'max:5000'],
            'opportunities_findings' => ['nullable', 'array'],
            'opportunities_findings.*.record_type' => ['required_with:opportunities_findings', 'string', Rule::in(array_keys(GisFeatureCatalogue::RECORD_TYPES))],
            'opportunities_findings.*.category' => ['required_with:opportunities_findings', 'string', Rule::in(array_keys(GisFeatureCatalogue::CATEGORIES))],
            'opportunities_findings.*.title' => ['required_with:opportunities_findings', 'string', 'max:255'],
            'opportunities_findings.*.description' => ['required_with:opportunities_findings', 'string', 'max:5000'],
            'opportunities_findings.*.significance' => ['required_with:opportunities_findings', 'string', Rule::in(array_keys(GisFeatureCatalogue::SIGNIFICANCE))],
            'opportunities_findings.*.recommended_action' => ['nullable', 'string', 'max:5000'],
            'opportunities_findings.*.priority' => ['required_with:opportunities_findings', 'string', Rule::in(array_keys(GisFeatureCatalogue::PRIORITIES))],
            'opportunities_findings.*.status' => ['required_with:opportunities_findings', 'string', Rule::in(array_keys(GisFeatureCatalogue::STATUSES))],
            'opportunities_findings.*.evidence_reference' => ['nullable', 'string', 'max:2048'],
            'opportunities_findings.*.due_date' => ['nullable', 'date'],
            'opportunities_findings.*.responsible_role' => ['nullable', 'string', 'max:255'],
        ];
    }
}
