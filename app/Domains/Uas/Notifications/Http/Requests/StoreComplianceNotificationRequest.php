<?php

namespace App\Domains\Uas\Notifications\Http\Requests;

use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Notifications\Domain\Services\NotificationEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplianceNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ComplianceNotification::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'requirement_id' => ['nullable', 'string', 'max:120'],
            'notification_type' => ['required', 'string', Rule::in(array_keys(NotificationEngine::TYPES))],
            'idempotency_key' => ['nullable', 'string', 'max:255', 'unique:compliance_notifications,idempotency_key'],
            'channel' => ['required', 'string', Rule::in(array_keys(NotificationEngine::CHANNELS))],
            'priority' => ['required', 'string', Rule::in(array_keys(NotificationEngine::PRIORITIES))],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
