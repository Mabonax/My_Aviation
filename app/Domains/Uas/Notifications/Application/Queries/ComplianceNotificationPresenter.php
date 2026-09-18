<?php

namespace App\Domains\Uas\Notifications\Application\Queries;

use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;

class ComplianceNotificationPresenter
{
    public static function toArray(ComplianceNotification $notification): array
    {
        $notification->loadMissing(['user', 'regulatoryRequirement']);

        return [
            'id' => $notification->id,
            'user_id' => $notification->user_id,
            'recipient' => $notification->user ? trim($notification->user->name.' / '.$notification->user->email) : null,
            'requirement_id' => $notification->requirement_id,
            'regulatory_requirement' => $notification->regulatoryRequirement ? [
                'id' => $notification->regulatoryRequirement->id,
                'title' => $notification->regulatoryRequirement->title,
                'source_version' => $notification->regulatoryRequirement->source_version,
            ] : null,
            'notification_type' => $notification->notification_type,
            'idempotency_key' => $notification->idempotency_key,
            'channel' => $notification->channel,
            'priority' => $notification->priority ?? 'normal',
            'status' => $notification->status,
            'delivery_attempts' => $notification->delivery_attempts,
            'subject' => $notification->subject,
            'message' => $notification->message,
            'failure_reason' => $notification->failure_reason,
            'due_at' => $notification->due_at?->toISOString(),
            'sent_at' => $notification->sent_at?->toISOString(),
            'read_at' => $notification->read_at?->toISOString(),
            'acknowledged_at' => $notification->acknowledged_at?->toISOString(),
        ];
    }
}
