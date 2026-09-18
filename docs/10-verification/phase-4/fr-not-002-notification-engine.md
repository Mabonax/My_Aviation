# FR-NOT-002 - Notification Engine

## Status

`VERIFIED`

## Scope

Extends the Phase 1 expiry-notification foundation into the broader Phase 4 Notification Engine described in section 30 of the functional requirements.

## Repository Evidence

- `database/migrations/2026_09_11_190000_add_engine_fields_to_compliance_notifications_table.php`
- `app/Domains/Uas/Notifications/Domain/Services/NotificationEngine.php`
- `app/Domains/Uas/Notifications/Application/Actions/PlanComplianceNotification.php`
- `app/Domains/Uas/Notifications/Application/Actions/UpdateComplianceNotificationStatus.php`
- `app/Domains/Uas/Notifications/Application/Queries/ListComplianceNotifications.php`
- `app/Domains/Uas/Notifications/Application/Queries/ComplianceNotificationPresenter.php`
- `app/Domains/Uas/Notifications/Http/Controllers/ComplianceNotificationController.php`
- `resources/js/pages/notifications/*`
- `tests/Feature/Uas/Phase4NotificationEngineTest.php`

## Behaviour

- Supports in-application, email, mobile push, SMS and WhatsApp channel classification without assuming any external delivery API.
- Supports compliance notification types for expiry, revalidation, medical, security, training, aircraft registration, UASLA, UASOC, insurance, maintenance, manual acknowledgement, corrective action, application deadline and regulatory change events.
- Adds idempotency keys, priority, delivery attempts, failure reason, read timestamp and acknowledgement timestamp to compliance notifications.
- Plans idempotent notification records and writes FR-NOT-002 audit evidence when new records are created.
- Updates delivery/read/acknowledgement/failed/cancelled status with FR-NOT-002 audit evidence.
- Exposes authenticated Inertia pages for listing, creating, viewing and updating notification lifecycle state.

## Verification

- `php artisan test --filter=Phase4NotificationEngineTest --compact`: passed, 5 tests, 65 assertions.
- `php artisan route:list --path=compliance-notifications`: passed, 5 routes registered.
- `php artisan test --compact`: passed, 143 tests, 1506 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_190000_add_engine_fields_to_compliance_notifications_table`.

## Outstanding

- Real email/mobile/SMS/WhatsApp provider delivery adapters.
- User notification preferences.
- Queue worker dispatch and retry policies for external delivery attempts.
- Browser sign-off with authenticated production-like data.
