<?php

use App\Domains\Uas\Access\Domain\Models\UasRole;
use App\Domains\Uas\Notifications\Application\Actions\PlanComplianceNotification;
use App\Domains\Uas\Notifications\Application\Actions\UpdateComplianceNotificationStatus;
use App\Domains\Uas\Notifications\Domain\Models\ComplianceNotification;
use App\Domains\Uas\Records\Domain\Models\UasAuditEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function notificationEngineUser(array $permissions = ['notifications.view', 'notifications.plan', 'notifications.update']): User
{
    $user = User::factory()->create();

    $role = UasRole::query()->create([
        'name' => 'notification_engine_admin_'.str()->random(8),
        'label' => 'Notification Engine Admin',
        'permissions' => $permissions,
    ]);

    $role->users()->attach($user);

    return $user;
}

function notificationPayload(array $overrides = []): array
{
    return [
        'requirement_id' => 'FR-NOT-002',
        'notification_type' => 'application_deadline',
        'idempotency_key' => 'application-deadline-ops-001',
        'channel' => 'in_application',
        'priority' => 'high',
        'subject' => 'Renewal application deadline approaching',
        'message' => 'The renewal application pack requires completion before the authority deadline.',
        'due_at' => '2026-09-20 08:00:00',
        ...$overrides,
    ];
}

it('requires notification permissions for engine routes', function () {
    $this->withoutVite();

    $viewer = notificationEngineUser(['notifications.view']);
    $notification = ComplianceNotification::query()->create(notificationPayload(['idempotency_key' => 'route-permission-check']));

    $this->actingAs($viewer)->get('/compliance-notifications')->assertOk();
    $this->actingAs($viewer)->get('/compliance-notifications/create')->assertForbidden();
    $this->actingAs($viewer)->post('/compliance-notifications', notificationPayload(['idempotency_key' => 'route-forbidden']))->assertForbidden();
    $this->actingAs($viewer)->put("/compliance-notifications/{$notification->id}/status", ['status' => 'sent'])->assertForbidden();
});

it('plans idempotent multi-channel compliance notifications with audit evidence', function () {
    $user = notificationEngineUser();
    $recipient = User::factory()->create();
    $planner = app(PlanComplianceNotification::class);

    $first = $planner->execute(notificationPayload([
        'user_id' => $recipient->id,
        'channel' => 'email',
    ]), $user);

    $second = $planner->execute(notificationPayload([
        'user_id' => $recipient->id,
        'channel' => 'email',
    ]), $user);

    expect($first->id)->toBe($second->id)
        ->and(ComplianceNotification::query()->count())->toBe(1)
        ->and($first->channel)->toBe('email')
        ->and($first->priority)->toBe('high')
        ->and($first->status)->toBe('pending');

    $audit = UasAuditEntry::query()->where('action', 'compliance.notification.engine_planned')->firstOrFail();

    expect($audit->auditable_type)->toBe(ComplianceNotification::class)
        ->and($audit->auditable_id)->toBe($first->id)
        ->and($audit->requirement_id)->toBe('FR-NOT-002');
});

it('updates notification delivery lifecycle timestamps and records audit evidence', function () {
    $user = notificationEngineUser();
    $notification = ComplianceNotification::query()->create(notificationPayload(['idempotency_key' => 'status-transition-check']));
    $updater = app(UpdateComplianceNotificationStatus::class);

    $sent = $updater->execute($notification, ['status' => 'sent'], $user);
    $acknowledged = $updater->execute($sent, ['status' => 'acknowledged'], $user);

    expect($acknowledged->status)->toBe('acknowledged')
        ->and($acknowledged->delivery_attempts)->toBe(1)
        ->and($acknowledged->sent_at)->not->toBeNull()
        ->and($acknowledged->read_at)->not->toBeNull()
        ->and($acknowledged->acknowledged_at)->not->toBeNull();

    expect(UasAuditEntry::query()->where('action', 'compliance.notification.status_updated')->where('requirement_id', 'FR-NOT-002')->count())->toBe(2);
});

it('validates notification type channel priority and failure reason', function () {
    $user = notificationEngineUser();
    $notification = ComplianceNotification::query()->create(notificationPayload(['idempotency_key' => 'validation-status-check']));

    $this->actingAs($user)
        ->post('/compliance-notifications', notificationPayload([
            'notification_type' => 'unsupported_delivery',
            'channel' => 'fax',
            'priority' => 'panic',
            'subject' => '',
            'message' => '',
        ]))
        ->assertInvalid(['notification_type', 'channel', 'priority', 'subject', 'message']);

    $this->actingAs($user)
        ->put("/compliance-notifications/{$notification->id}/status", ['status' => 'failed'])
        ->assertInvalid(['failure_reason']);
});

it('exposes compliance notification register pages through Inertia', function () {
    $this->withoutVite();

    $user = notificationEngineUser();
    $recipient = User::factory()->create(['name' => 'Notification Recipient']);

    $this->actingAs($user)
        ->post('/compliance-notifications', notificationPayload([
            'user_id' => $recipient->id,
            'idempotency_key' => 'ui-notification-check',
        ]))
        ->assertRedirect();

    $notification = ComplianceNotification::query()->where('idempotency_key', 'ui-notification-check')->firstOrFail();

    $this->actingAs($user)
        ->get('/compliance-notifications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/index')
            ->where('notifications.0.subject', 'Renewal application deadline approaching')
        );

    $this->actingAs($user)
        ->get('/compliance-notifications/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/create')
            ->has('options.channels.email')
            ->has('options.types.application_deadline')
        );

    $this->actingAs($user)
        ->get("/compliance-notifications/{$notification->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/show')
            ->where('notification.idempotency_key', 'ui-notification-check')
            ->where('notification.recipient', 'Notification Recipient / '.$recipient->email)
        );
});
