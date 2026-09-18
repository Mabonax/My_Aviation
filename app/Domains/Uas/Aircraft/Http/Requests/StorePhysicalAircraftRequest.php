<?php

namespace App\Domains\Uas\Aircraft\Http\Requests;

use App\Domains\Uas\Operators\Application\Queries\CurrentOperatorContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhysicalAircraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user->hasAnyUasPermission(['operators.update', 'missions.create'])) {
            return true;
        }

        $operatorId = $this->integer('uas_operator_id') ?: null;

        return $operatorId !== null && app(CurrentOperatorContext::class)->canManageOperator($user, $operatorId);
    }

    public function rules(): array
    {
        return [
            'aircraft_model_id' => ['nullable', 'integer', 'exists:uas_aircraft_models,id'],
            'uas_operator_id' => ['nullable', 'integer', 'exists:uas_operators,id'],
            'manufacturer' => ['required_without:aircraft_model_id', 'nullable', 'string', 'max:180'],
            'model' => ['required_without:aircraft_model_id', 'nullable', 'string', 'max:180'],
            'registration' => ['required', 'string', 'max:80', 'unique:uas_aircraft,registration'],
            'serial_number' => ['required', 'string', 'max:120', 'unique:uas_aircraft,serial_number'],
            'internal_asset_number' => ['nullable', 'string', 'max:120'],
            'aircraft_category' => ['nullable', 'string', 'max:80'],
            'owner' => ['nullable', 'string', 'max:180'],
            'operator' => ['nullable', 'string', 'max:180'],
            'supplier' => ['nullable', 'string', 'max:180'],
            'firmware_version' => ['nullable', 'string', 'max:120'],
            'flight_controller_serial' => ['nullable', 'string', 'max:120'],
            'remote_id_serial' => ['nullable', 'string', 'max:120'],
            'acquisition_date' => ['nullable', 'date'],
            'operational_status' => ['nullable', 'string', 'max:80'],
            'onboarding_status' => ['nullable', Rule::in(['draft', 'onboarding', 'active', 'grounded', 'retired'])],
            'base_location' => ['nullable', 'string', 'max:180'],
        ];
    }
}
