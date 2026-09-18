<?php

namespace App\Domains\Uas\Notifications\Application\Queries;

use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;

class ListComplianceNotifications
{
    public function execute(): array
    {
        return ComplianceNotification::query()
            ->with('user')
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderBy('due_at')
            ->latest('id')
            ->get()
            ->map(fn (ComplianceNotification $notification): array => [
                'id' => $notification->id,
                'requirement_id' => $notification->requirement_id,
                'notification_type' => $notification->notification_type,
                'channel' => $notification->channel,
                'priority' => $notification->priority ?? 'normal',
                'status' => $notification->status,
                'subject' => $notification->subject,
                'due_at' => $notification->due_at?->toISOString(),
                'sent_at' => $notification->sent_at?->toISOString(),
                'recipient' => $notification->user ? trim($notification->user->name.' / '.$notification->user->email) : null,
            ])
            ->all();
    }
}
