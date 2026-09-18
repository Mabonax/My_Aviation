<?php

namespace App\Domains\Uas\Notifications\Http\Requests;

use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Notifications\Domain\Services\NotificationEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplianceNotificationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $notification = $this->route('complianceNotification');

        return $notification instanceof ComplianceNotification
            && ($this->user()?->can('update', $notification) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(NotificationEngine::STATUSES))],
            'failure_reason' => ['nullable', 'required_if:status,failed', 'string', 'max:5000'],
        ];
    }
}
