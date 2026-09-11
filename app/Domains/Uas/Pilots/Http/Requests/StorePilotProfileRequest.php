<?php

namespace App\Domains\Uas\Pilots\Http\Requests;

use App\Domains\Uas\Pilots\Domain\Enums\PilotMedicalStatus;
use App\Domains\Uas\Pilots\Domain\Enums\PilotProfileStatus;
use App\Domains\Uas\Pilots\Domain\Enums\RadiotelephonyQualification;
use App\Domains\Uas\Pilots\Domain\Enums\RpcCategory;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePilotProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', UasPilot::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:uas_pilots,user_id'],
            'employee_number' => ['nullable', 'string', 'max:50', 'unique:uas_pilots,employee_number'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', 'unique:uas_pilots,email'],
            'phone' => ['nullable', 'string', 'max:60'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'sacaa_certificate_number' => ['nullable', 'string', 'max:120', 'unique:uas_pilots,sacaa_certificate_number'],
            'rpc_category' => ['required', Rule::enum(RpcCategory::class)],
            'ratings' => ['nullable', 'array'],
            'ratings.*' => ['string', 'max:120'],
            'medical_status' => ['required', Rule::enum(PilotMedicalStatus::class)],
            'radiotelephony_qualification' => ['required', Rule::enum(RadiotelephonyQualification::class)],
            'language_proficiency' => ['nullable', 'string', 'max:120'],
            'training_history' => ['nullable', 'array'],
            'examiner_records' => ['nullable', 'array'],
            'operator_affiliations' => ['nullable', 'array'],
            'supporting_document_references' => ['nullable', 'array'],
            'profile_status' => ['required', Rule::enum(PilotProfileStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
