<?php

namespace App\Domains\Uas\Geography\Http\Requests;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Services\GisProjectLifecycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGisProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', UasGisProject::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'project_code' => ['required', 'string', 'max:80', 'unique:uas_gis_projects,project_code'],
            'name' => ['required', 'string', 'max:255'],
            'project_type' => ['required', 'string', Rule::in(array_keys(GisProjectLifecycle::PROJECT_TYPES))],
            'client_or_stakeholder' => ['nullable', 'string', 'max:255'],
            'area_name' => ['required', 'string', 'max:255'],
            'location_search_query' => ['nullable', 'string', 'max:255'],
            'centroid_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'centroid_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'area_boundary' => ['nullable', 'array'],
            'area_boundary.*.latitude' => ['required_with:area_boundary', 'numeric', 'between:-90,90'],
            'area_boundary.*.longitude' => ['required_with:area_boundary', 'numeric', 'between:-180,180'],
            'source_reference' => ['required', 'string', 'max:255'],
            'source_version' => ['nullable', 'string', 'max:120'],
            'data_governance_notes' => ['nullable', 'string', 'max:5000'],
            'evidence_required' => ['required', 'string', 'max:255'],
            'responsible_role' => ['required', 'string', 'max:120'],
        ];
    }
}
