<?php

namespace App\Domains\Uas\Geography\Http\Requests;

use App\Domains\Uas\Geography\Domain\Models\UasGisProject;
use App\Domains\Uas\Geography\Domain\Services\GisProjectMissionAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGisProjectMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('gisProject');

        return $project instanceof UasGisProject && ($this->user()?->can('update', $project) ?? false);
    }

    public function rules(): array
    {
        return [
            'uas_mission_id' => ['required', 'integer', 'exists:uas_missions,id', 'unique:uas_gis_project_missions,uas_mission_id'],
            'mapping_objective' => ['required', 'string', 'max:255'],
            'capture_plan' => ['required', 'string', 'max:5000'],
            'expected_outputs' => ['required', 'array', 'min:1'],
            'expected_outputs.*' => ['required', 'string', Rule::in(array_keys(GisProjectMissionAssignment::OUTPUTS))],
            'field_verification_required' => ['required', 'string', Rule::in(array_keys(GisProjectMissionAssignment::FIELD_VERIFICATION))],
            'evidence_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(array_keys(GisProjectMissionAssignment::STATUSES))],
        ];
    }
}
