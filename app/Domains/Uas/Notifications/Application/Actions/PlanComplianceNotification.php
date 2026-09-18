<?php

namespace App\Domains\Uas\Notifications\Application\Actions;

use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlanComplianceNotification
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): ComplianceNotification
    {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): ComplianceNotification {
            $attributes = [
                'user_id' => $data['user_id'] ?? null,
                'requirement_id' => $data['requirement_id'] ?? 'FR-NOT-002',
                'notification_type' => $data['notification_type'],
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'channel' => $data['channel'] ?? 'in_application',
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'pending',
                'subject' => $data['subject'],
                'message' => $data['message'],
                'due_at' => $data['due_at'] ?? null,
            ];

            $notification = $attributes['idempotency_key']
                ? ComplianceNotification::query()->firstOrCreate(['idempotency_key' => $attributes['idempotency_key']], $attributes)
                : ComplianceNotification::query()->create($attributes);

            if ($notification->wasRecentlyCreated) {
                $this->recordAuditEntry->execute(new AuditEntryData($actor, $notification, 'compliance.notification.engine_planned', 'FR-NOT-002', 'UAS Compliance & Operations Platform FRS FR-NOT-002', null, $notification->getAttributes(), $ipAddress, $userAgent));
            }

            return $notification;
        });
    }
}
