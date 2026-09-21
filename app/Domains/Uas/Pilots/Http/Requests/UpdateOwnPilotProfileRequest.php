<?php

namespace App\Domains\Uas\Pilots\Http\Requests;

use App\Domains\Uas\Pilots\Domain\Enums\RpcCategory;
use App\Domains\Uas\Pilots\Domain\Models\UasPilot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnPilotProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot') instanceof UasPilot ? $this->route('pilot') : $this->user()?->pilotProfile;

        return $pilot instanceof UasPilot && ($this->user()?->can('updateOwn', $pilot) ?? false);
    }

    public function rules(): array
    {
        /** @var UasPilot $pilot */
        $pilot = $this->user()->pilotProfile;

        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('uas_pilots', 'email')->ignore($pilot)],
            'phone' => ['nullable', 'string', 'max:60'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'sacaa_certificate_number' => ['nullable', 'string', 'max:120', Rule::unique('uas_pilots', 'sacaa_certificate_number')->ignore($pilot)],
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
