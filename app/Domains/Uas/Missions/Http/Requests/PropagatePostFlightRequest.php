<?php

namespace App\Domains\Uas\Missions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PropagatePostFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('mission')) ?? false;
    }

    public function rules(): array
    {
        return [
            'actual_takeoff_at' => ['required', 'date'],
            'actual_landing_at' => ['required', 'date', 'after:actual_takeoff_at'],
            'pilot_confirmed' => ['accepted'],
            'aircraft_confirmed' => ['accepted'],
            'defects_declared' => ['required', 'boolean'],
            'occurrence_declared' => ['required', 'boolean'],
            'closure_notes' => ['nullable', 'string', 'max:2000'],
            'actual_flight_duration_minutes' => ['prohibited'],
            'post_flight_propagation_state' => ['prohibited'],
            'aircraft_readiness_status' => ['prohibited'],
            'pilot_log_entry_id' => ['prohibited'],
            'aircraft_flight_folio_id' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mission = $this->route('mission');

            if (! $mission || ! $mission->post_flight_propagated_at) {
                return;
            }

            $validator->errors()->add('mission', 'This mission has already been completed and propagated.');
        });
    }

    public function closureData(): array
    {
        return $this->safe()->only([
            'actual_takeoff_at',
            'actual_landing_at',
            'pilot_confirmed',
            'aircraft_confirmed',
            'defects_declared',
            'occurrence_declared',
            'closure_notes',
        ]);
    }
}

