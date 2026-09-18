<?php

namespace App\Domains\Uas\Regulations\Http\Requests;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use Illuminate\Foundation\Http\FormRequest;

class SupersedeRegulatoryRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requirement = $this->route('regulatoryRequirement');

        return $requirement instanceof RegulatoryRequirement
            && ($this->user()?->can('update', $requirement) ?? false);
    }

    public function rules(): array
    {
        return [
            'requirement_id' => ['required', 'string', 'max:80', 'unique:regulatory_requirements,requirement_id'],
            'regulation_part' => ['required', 'string', 'max:80'],
            'clause_reference' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'requirement_text' => ['required', 'string', 'max:10000'],
            'responsible_party' => ['required', 'string', 'max:180'],
            'applicability' => ['required', 'string', 'max:5000'],
            'system_control' => ['required', 'string', 'max:5000'],
            'evidence_required' => ['nullable', 'string', 'max:5000'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'validity_period' => ['nullable', 'string', 'max:120'],
            'retention_period' => ['nullable', 'string', 'max:120'],
            'effective_date' => ['required', 'date', 'after_or_equal:'.$this->route('regulatoryRequirement')->effective_date?->toDateString()],
            'official_source' => ['required', 'string', 'max:240'],
            'source_version' => ['required', 'string', 'max:120'],
        ];
    }
}
