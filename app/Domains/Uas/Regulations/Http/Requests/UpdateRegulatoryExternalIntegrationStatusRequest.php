<?php

namespace App\Domains\Uas\Regulations\Http\Requests;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryExternalIntegration;
use App\Domains\Uas\Regulations\Domain\Services\ExternalRegulatoryIntegrationClassifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegulatoryExternalIntegrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $integration = $this->route('regulatoryExternalIntegration');

        return $integration instanceof RegulatoryExternalIntegration && ($this->user()?->can('update', $integration) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(ExternalRegulatoryIntegrationClassifier::STATUSES))],
        ];
    }
}
