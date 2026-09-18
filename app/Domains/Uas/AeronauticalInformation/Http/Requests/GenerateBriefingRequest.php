<?php

namespace App\Domains\Uas\AeronauticalInformation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBriefingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generateBriefing', $this->route('mission'));
    }

    public function rules(): array
    {
        return ['aeronautical_context' => 'sometimes|array:altitude_reference,minimum_altitude_ft,fir_codes,aerodrome_codes',
            'aeronautical_context.altitude_reference' => 'nullable|in:AGL,AMSL', 'aeronautical_context.minimum_altitude_ft' => 'nullable|numeric|min:-2000|max:100000',
            'aeronautical_context.fir_codes' => 'sometimes|array|max:20', 'aeronautical_context.fir_codes.*' => 'string|regex:/^[A-Z]{4}$/',
            'aeronautical_context.aerodrome_codes' => 'sometimes|array|max:20', 'aeronautical_context.aerodrome_codes.*' => 'string|regex:/^[A-Z]{4}$/'];
    }
}
