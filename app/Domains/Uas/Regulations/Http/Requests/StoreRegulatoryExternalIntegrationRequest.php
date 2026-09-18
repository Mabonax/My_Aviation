<?php

namespace App\Domains\Uas\Regulations\Http\Requests;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;
use App\Domains\Uas\Regulations\Domain\Services\ExternalRegulatoryIntegrationClassifier;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegulatoryExternalIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RegulatoryExternalIntegration::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'authority' => ['required', 'string', 'max:120'],
            'classification' => ['required', 'string', Rule::in(array_keys(ExternalRegulatoryIntegrationClassifier::CLASSIFICATIONS))],
            'regulatory_area' => ['required', 'string', 'max:120'],
            'supported_process' => ['required', 'string', 'max:255', Rule::unique('regulatory_external_integrations', 'supported_process')->where(fn (Builder $query) => $query->where('authority', $this->input('authority')))],
            'authoritative_url' => ['nullable', 'url', 'max:2048'],
            'evidence_required' => ['required', 'string', 'max:255'],
            'workflow_notes' => ['required', 'string', 'max:5000'],
            'api_assumption_blocked' => ['sometimes', 'boolean'],
            'status' => ['required', 'string', Rule::in(array_keys(ExternalRegulatoryIntegrationClassifier::STATUSES))],
            'verified_at' => ['nullable', 'date'],
        ];
    }
}
