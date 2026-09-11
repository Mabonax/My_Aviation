<?php

namespace App\Domains\Uas\Crew\Http\Requests;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionCrewMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof UasMission && ($this->user()?->can('update', $mission) ?? false);
    }

    public function rules(): array
    {
        return [
            'crew_role' => ['required', Rule::in(['remote_pilot', 'observer', 'payload_operator', 'visual_observer', 'safety_officer', 'operations_supervisor'])],
            'display_name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:80'],
            'uas_pilot_id' => ['nullable', 'integer', 'exists:uas_pilots,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'briefing_status' => ['required', Rule::in(['pending', 'briefed'])],
            'competency_status' => ['required', Rule::in(['not_checked', 'verified', 'expired', 'not_required'])],
            'acceptance_status' => ['required', Rule::in(['pending', 'accepted', 'declined'])],
            'emergency_contact_name' => ['nullable', 'string', 'max:180'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}