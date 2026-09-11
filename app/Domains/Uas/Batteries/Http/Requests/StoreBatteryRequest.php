<?php

namespace App\Domains\Uas\Batteries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Domains\Uas\Missions\Domain\Models\UasMission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'battery_uid' => ['required', 'string', 'max:80', 'unique:uas_batteries,battery_uid'],
            'manufacturer' => ['required', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['required', 'string', 'max:120', 'unique:uas_batteries,serial_number'],
            'compatible_uas_aircraft_id' => ['nullable', 'integer', 'exists:uas_aircraft,id'],
            'cycle_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'maximum_cycles' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'acquisition_date' => ['nullable', 'date'],
            'damage_incidents' => ['nullable', 'string', 'max:5000'],
            'retirement_status' => ['required', Rule::in(['active', 'retired', 'quarantined'])],
            'charge_history' => ['nullable', 'array'],
            'evidence_references' => ['nullable', 'array'],
        ];
    }
}