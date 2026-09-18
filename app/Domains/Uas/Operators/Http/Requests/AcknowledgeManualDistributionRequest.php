<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeManualDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $distribution = $this->route('distribution');
        $user = $this->user();

        if (! $user || ! $distribution) {
            return false;
        }

        if ($distribution->recipient_email && strcasecmp($distribution->recipient_email, $user->email) === 0) {
            return true;
        }

        return $user->can('update', $distribution->manualRevision->operator);
    }

    public function rules(): array
    {
        return [
            'readership_confirmed' => ['accepted'],
            'acknowledgement_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
