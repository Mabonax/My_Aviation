<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use App\Domains\Uas\Operators\Application\Queries\OperatorOptions;
use App\Domains\Uas\Operators\Domain\Models\UasOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperatorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('operator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'legal_entity' => ['required', 'string', 'max:180'],
            'trading_name' => ['nullable', 'string', 'max:180'],
            'registration_number' => ['nullable', 'string', 'max:120', Rule::unique('uas_operators', 'registration_number')->ignore($this->route('operator'))],
            'uasoc_number' => ['nullable', 'string', 'max:120', Rule::unique('uas_operators', 'uasoc_number')->ignore($this->route('operator'))],
            'certificate_issue_date' => ['nullable', 'date'],
            'certificate_expiry_date' => ['nullable', 'date', 'after_or_equal:certificate_issue_date'],
            'status' => ['required', 'string', Rule::in(array_keys(OperatorOptions::STATUSES))],
            'accountable_manager' => ['required', 'string', 'max:180'],
            'responsible_person_flight_operations' => ['required', 'string', 'max:180'],
            'responsible_person_aircraft' => ['required', 'string', 'max:180'],
            'safety_manager' => ['nullable', 'string', 'max:180'],
            'security_coordinator' => ['nullable', 'string', 'max:180'],
            'operating_bases' => ['nullable', 'array'],
            'operating_bases.*' => ['string', 'max:180'],
            'approved_aircraft' => ['nullable', 'array'],
            'approved_aircraft.*' => ['integer', 'exists:uas_aircraft,id'],
            'approved_pilots' => ['nullable', 'array'],
            'approved_pilots.*' => ['integer', 'exists:uas_pilots,id'],
            'operations_specifications' => ['nullable', 'array'],
            'operations_specifications.*' => ['string', 'max:240'],
            'evidence_references' => ['nullable', 'array'],
            'evidence_references.*' => ['string', 'max:500'],
        ];
    }
}