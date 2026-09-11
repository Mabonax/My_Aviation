<?php

namespace App\Domains\Uas\Checklists\Http\Requests;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Foundation\Http\FormRequest;

class StorePreFlightChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof UasMission && ($this->user()?->can('update', $mission) ?? false);
    }

    public function rules(): array
    {
        return [
            'results' => ['required', 'array'],
            'results.*.result' => ['required', 'in:pass,fail,not_applicable'],
            'results.*.notes' => ['nullable', 'string', 'max:1000'],
            'exceptions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}