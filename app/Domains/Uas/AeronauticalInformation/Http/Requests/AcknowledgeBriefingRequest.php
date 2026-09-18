<?php

namespace App\Domains\Uas\AeronauticalInformation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeBriefingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('acknowledgeBriefing', $this->route('mission'));
    }

    public function rules(): array
    {
        return ['reviewed' => 'required|accepted'];
    }
}
