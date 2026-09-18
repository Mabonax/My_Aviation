<?php

namespace App\Domains\Uas\AeronauticalInformation\Http\Requests;

use App\Domains\Uas\AeronauticalInformation\Domain\Enums\InformationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['search' => 'nullable|string|max:200', 'type' => ['nullable', Rule::enum(InformationType::class)], 'provider' => ['nullable', Rule::in(array_keys(config('aeronautical.providers')))], 'status' => 'nullable|in:current,active,cancelled,superseded,all', 'validity' => 'nullable|in:active,expired,future', 'page' => 'nullable|integer|min:1'];
    }
}
