<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use App\Domains\Uas\Operators\Domain\Models\UasOperatorCertificateCase;
use App\Domains\Uas\Operators\Domain\Services\CertificateCaseLifecycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('operator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'case_number' => ['nullable', 'string', 'max:80', 'unique:uas_operator_certificate_cases,case_number'],
            'case_type' => ['required', 'string', Rule::in(array_keys(CertificateCaseLifecycle::TYPES))],
            'status' => ['required', 'string', Rule::in(array_keys(CertificateCaseLifecycle::STATUSES))],
            'deadline_at' => ['nullable', 'date'],
            'evidence_requirements' => ['nullable', 'array'],
            'evidence_requirements.*' => ['string', 'max:240'],
            'outstanding_documents' => ['nullable', 'array'],
            'outstanding_documents.*' => ['string', 'max:240'],
            'fleet_scope' => ['nullable', 'array'],
            'fleet_scope.*' => ['string', 'max:240'],
            'personnel_scope' => ['nullable', 'array'],
            'personnel_scope.*' => ['string', 'max:240'],
            'ops_spec_scope' => ['nullable', 'array'],
            'ops_spec_scope.*' => ['string', 'max:240'],
            'operations_manual_revision' => ['nullable', 'string', 'max:120'],
            'fees' => ['nullable', 'array'],
            'fees.*' => ['string', 'max:240'],
            'authority_correspondence' => ['nullable', 'array'],
            'authority_correspondence.*' => ['string', 'max:500'],
            'outcome' => ['nullable', 'string', 'max:5000'],
            'submitted_at' => ['nullable', 'date'],
            'decided_at' => ['nullable', 'date'],
        ];
    }
}