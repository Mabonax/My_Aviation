<?php

namespace App\Domains\Uas\Pilots\Http\Requests;

use App\Domains\Uas\Pilots\Domain\Enums\RpcCategory;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOwnPilotProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createOwn', UasPilot::class) ?? false;
    }

    public function rules(): array
    {
        return [
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
            'medical_status' => ['prohibited'],
            'radiotelephony_qualification' => ['prohibited'],
            'language_proficiency' => ['nullable', 'string', 'max:120'],
            'training_history' => ['nullable', 'array'],
            'examiner_records' => ['nullable', 'array'],
            'operator_affiliations' => ['nullable', 'array'],
            'supporting_document_references' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
