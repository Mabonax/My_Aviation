<?php

namespace App\Domains\Uas\Notifications\Application\Actions;

use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Notifications\Domain\Services\ExpiryNotificationPlanner;
use App\Domains\Uas\Pilots\Domain\Models\PilotCertificate;
use App\Domains\Uas\Records\Application\Actions\RecordAuditEntry;
use App\Domains\Uas\Records\Application\DTOs\AuditEntryData;

class PlanCertificateExpiryNotifications
{
    public function __construct(
        private readonly ExpiryNotificationPlanner $planner,
        private readonly RecordAuditEntry $audit,
    ) {}

    public function execute(): int
    {
        $created = 0;

        PilotCertificate::query()
            ->with('pilot')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays(120)->toDateString())
            ->each(function (PilotCertificate $certificate) use (&$created): void {
                foreach ($this->planner->alertDates($certificate->expiry_date) as $days => $alertDate) {
                    if ($alertDate !== now()->toDateString()) {
                        continue;
                    }

                    $notification = ComplianceNotification::query()->firstOrCreate(
                        [
                            'notifiable_record_type' => PilotCertificate::class,
                            'notifiable_record_id' => $certificate->id,
                            'requirement_id' => 'FR-NOT-001',
                            'notification_type' => 'rpc_expiry_alert_'.$days.'_days',
                            'due_at' => now()->startOfDay(),
                        ],
                        [
                            'user_id' => $certificate->pilot?->user_id,
                            'channel' => 'in_application',
                            'status' => 'pending',
                            'subject' => 'RPC certificate expiry alert',
                            'message' => "RPC {$certificate->certificate_number} reaches its {$days}-day expiry alert point.",
                        ]
                    );

                    if (! $notification->wasRecentlyCreated) {
                        continue;
                    }

                    $created++;

                    $this->audit->execute(new AuditEntryData(
                        actor: null,
                        auditable: $notification,
                        action: 'compliance.notification.planned',
                        requirementId: 'FR-NOT-001',
                        regulatorySource: 'UAS Compliance & Operations Platform FRS FR-NOT-001',
                        previousValues: null,
                        newValues: $notification->getAttributes(),
                    ));
                }
            });

        return $created;
    }
}
