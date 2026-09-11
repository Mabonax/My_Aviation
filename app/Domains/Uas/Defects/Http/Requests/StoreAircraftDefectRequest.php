<?php

namespace App\Domains\Uas\Defects\Http\Requests;

use App\Domains\Uas\Defects\Domain\Services\DefectServiceabilityImpact;
use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAircraftDefectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        if ($mission instanceof UasMission) {
            return $this->user()?->can('update', $mission) ?? false;
        }

        return $this->user()?->can('create', UasMission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'uas_aircraft_id' => ['nullable', 'integer', 'exists:uas_aircraft,id'],
            'uas_mission_id' => ['nullable', 'integer', 'exists:uas_missions,id'],
            'defect_number' => ['nullable', 'string', 'max:80', 'unique:uas_aircraft_defects,defect_number'],
            'source' => ['required', 'string', Rule::in(array_keys(DefectServiceabilityImpact::SOURCES))],
            'severity' => ['required', 'string', Rule::in(array_keys(DefectServiceabilityImpact::SEVERITIES))],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
            'immediate_action' => ['nullable', 'string', 'max:5000'],
            'reported_at' => ['nullable', 'date'],
            'evidence_references' => ['nullable', 'array'],
            'evidence_references.*' => ['string', 'max:500'],
        ];
    }
}