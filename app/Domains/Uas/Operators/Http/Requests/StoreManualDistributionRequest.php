<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use App\Domains\Uas\Operators\Domain\Services\ManualDistributionControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('manualRevision')->operator) ?? false;
    }

    public function rules(): array
    {
        $manualRevision = $this->route('manualRevision');

        return [
            'recipient_name' => [
                'required',
                'string',
                'max:180',
                Rule::unique('uas_operations_manual_distributions', 'recipient_name')
                    ->where(fn ($query) => $query
                        ->where('manual_revision_id', $manualRevision->id)
                        ->where('recipient_role', $this->input('recipient_role'))),
            ],
            'recipient_role' => ['required', 'string', 'max:160'],
            'recipient_email' => ['nullable', 'email', 'max:180'],
            'distribution_channel' => ['required', 'string', Rule::in(array_keys(ManualDistributionControl::CHANNELS))],
            'distribution_status' => ['required', 'string', Rule::in(array_keys(ManualDistributionControl::STATUSES))],
            'required_by' => ['nullable', 'date'],
            'distributed_at' => ['nullable', 'date'],
            'evidence_references' => ['nullable', 'array'],
            'evidence_references.*' => ['string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
