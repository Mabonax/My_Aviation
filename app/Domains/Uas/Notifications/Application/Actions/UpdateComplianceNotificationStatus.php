<?php

namespace App\Domains\Uas\Notifications\Application\Actions;

use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateComplianceNotificationStatus
{
    public function __construct(private readonly RecordAuditEntry $recordAuditEntry) {}

    public function execute(ComplianceNotification $notification, array $data, User $actor, ?string $ipAddress = null, ?string $userAgent = null): ComplianceNotification
    {
        return DB::transaction(function () use ($notification, $data, $actor, $ipAddress, $userAgent): ComplianceNotification {
            $previous = $notification->getAttributes();
            $status = $data['status'];

            $notification->fill([
                'status' => $status,
                'failure_reason' => $status === 'failed' ? ($data['failure_reason'] ?? $notification->failure_reason) : null,
            ]);

            if ($status === 'sent') {
                $notification->sent_at ??= now();
                $notification->delivery_attempts = $notification->delivery_attempts + 1;
            }

            if ($status === 'read') {
                $notification->read_at ??= now();
            }

            if ($status === 'acknowledged') {
                $notification->read_at ??= now();
                $notification->acknowledged_at ??= now();
            }

            $notification->save();

            $this->recordAuditEntry->execute(new AuditEntryData($actor, $notification, 'compliance.notification.status_updated', 'FR-NOT-002', 'UAS Compliance & Operations Platform FRS FR-NOT-002', $previous, $notification->getAttributes(), $ipAddress, $userAgent));

            return $notification->refresh();
        });
    }
}
