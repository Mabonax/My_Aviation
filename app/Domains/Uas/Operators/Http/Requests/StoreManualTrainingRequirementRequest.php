<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use App\Domains\Uas\Operators\Domain\Services\ManualTrainingControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualTrainingRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('manualRevision')->operator) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'requirement_type' => ['required', 'string', Rule::in(array_keys(ManualTrainingControl::TYPES))],
            'training_status' => ['required', 'string', Rule::in(array_keys(ManualTrainingControl::STATUSES))],
            'affected_roles' => ['nullable', 'array'],
            'affected_roles.*' => ['string', 'max:160'],
            'due_date' => ['nullable', 'date'],
            'competency_standard' => ['nullable', 'string', 'max:240'],
            'trigger_reason' => ['nullable', 'string', 'max:5000'],
            'evidence_references' => ['nullable', 'array'],
            'evidence_references.*' => ['string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
