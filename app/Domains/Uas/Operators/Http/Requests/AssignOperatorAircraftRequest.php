<?php

namespace App\Domains\Uas\Operators\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignOperatorAircraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMemberships', $this->route('operator')) ?? false;
    }

    public function rules(): array
    {
        return [
            'uas_aircraft_id' => ['required', 'integer', 'exists:uas_aircraft,id'],
            'assignment_role' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'in:active,suspended,ended'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
