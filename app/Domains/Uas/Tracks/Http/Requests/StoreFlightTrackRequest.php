<?php

namespace App\Domains\Uas\Tracks\Http\Requests;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlightTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof UasMission && ($this->user()?->can('update', $mission) ?? false);
    }

    public function rules(): array
    {
        return [
            'source_type' => ['required', Rule::in(['manual', 'telemetry_import', 'rpa_controller', 'gcs_export'])],
            'track_reference' => ['nullable', 'string', 'max:180'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'points' => ['required', 'array', 'min:2'],
            'points.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'points.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'points.*.altitude_ft' => ['nullable', 'integer', 'min:0', 'max:40000'],
            'points.*.recorded_at' => ['nullable', 'date'],
            'anomalies' => ['nullable', 'array'],
            'anomalies.*' => ['string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}