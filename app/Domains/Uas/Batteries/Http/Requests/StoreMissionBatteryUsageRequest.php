<?php

namespace App\Domains\Uas\Batteries\Http\Requests;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Foundation\Http\FormRequest;

class StoreMissionBatteryUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof UasMission && ($this->user()?->can('update', $mission) ?? false);
    }

    public function rules(): array
    {
        return [
            'uas_battery_id' => ['required', 'integer', 'exists:uas_batteries,id'],
            'cycles_added' => ['required', 'integer', 'min:1', 'max:100'],
            'state_of_charge_start' => ['nullable', 'integer', 'min:0', 'max:100'],
            'state_of_charge_end' => ['nullable', 'integer', 'min:0', 'max:100'],
            'used_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}