# Flutter Readiness Audit

Date: 2026-09-12

## Flutter Status

```text
Flutter App: NOT IMPLEMENTED for YAW
```

Evidence:

- No `pubspec.yaml`, `lib/main.dart`, Android or iOS Flutter directories exist inside `C:\xampp\htdocs\myaviation`.
- Adjacent Flutter app found at `C:\xampp\htdocs\mobile\app`, but its `pubspec.yaml` describes a Drhealth patient/practitioner mobile application.
- Adjacent mobile code references patient/practitioner domains and `/api/mobile/v1`, not YAW UAS domains.

## Reference Benchmark

The adjacent Drhealth Flutter app is useful as an architecture reference only. Reusable patterns include:

- `shared/network/providers.dart` centralising Dio base URL, JSON headers, bearer token injection and structured error mapping.
- `shared/models/api_envelope.dart` decoding the backend envelope.
- `flutter_secure_storage` for protected token/session state.
- Riverpod service/provider layers such as `communicationServiceProvider` and `communicationWorkspaceProvider`.
- `go_router` route guards that react to authentication and role state.

YAW must not reuse patient/practitioner domain language or Drhealth endpoints.

## Mobile Dependencies

YAW Flutter V1 is blocked by:

- Missing YAW REST API.
- Missing stable API Resources/DTOs.
- Incomplete user-to-pilot self-service journey.
- Incomplete ownership/tenancy scoping.
- Missing online-first pilot compliance summary.

## Recommended Mobile V1 Scope

Pilot/field-first navigation:

```text
Home
My Compliance
Licence Wallet
My Aircraft
Missions
Pre-flight
Flight
Post-flight
Logbook
Documents
Regulations
Notifications
Learning
```

Do not clone the entire web administration interface. Mobile should focus on field actions and explainable readiness.

Recommended initial feature structure:

```text
features/auth
features/pilot_profile
features/compliance
features/certificates
features/aircraft
features/missions
features/checklists
features/logbook
features/notifications
features/learning
```

## Offline Status

```text
Offline capability: NOT IMPLEMENTED
```

No YAW local mobile DB, cache, sync queue, mutation queue, attachment queue, cursor tracking or conflict-resolution model was found.

Recommended order:

1. Mobile V1 online-first with stable API.
2. Local read cache and connectivity awareness.
3. Offline pre-flight and mission actions.
4. Queued evidence/attachments.
5. Controlled sync conflict resolution.

## Notification Needs

Mobile push is not implemented. Future mobile notification support needs:

- Device token registration API.
- Notification preferences.
- Push provider adapter, likely Firebase/APNs.
- Queue-backed delivery and retry evidence.
- User-scoped notification API.
