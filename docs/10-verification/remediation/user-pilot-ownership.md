# Remediation Phase A - User-to-Pilot Ownership

Date: 2026-09-12

## Problem

The product audit found that `uas_pilots.user_id` existed, but the application did not yet provide a governed self-service identity chain for a logged-in remote pilot. Admin pilot CRUD existed at `/pilots`, but there was no canonical current-pilot resolver, no `/my/pilot` workspace, no self-service allowed-field boundary and no test proof that a user cannot spoof or duplicate pilot ownership.

## Architecture

The implemented relationship is:

```text
Authenticated User
  -> User::pilotProfile()
  -> uas_pilots.user_id
  -> pilot-owned compliance, missions, logbook and documents
```

The clinic backend pattern was adapted at the architecture level: authenticated persona resolution is centralised in a service/query before controllers return profile/workspace data. YAW implements this with `CurrentPilotProfile` and `MyPilotWorkspace`; no healthcare domain naming or route semantics were copied.

## Database Change

No new migration was required. The existing `2026_09_09_201200_create_uas_pilots_table.php` migration already defines:

```php
$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
```

This supports legacy unlinked pilot records and retains regulated pilot records if a user account is deleted. The unique relationship is enforced by the existing unique validation and the self-service duplicate-ownership checks.

## Ownership Rule

- Normal remote-pilot case: one authenticated user owns one pilot profile.
- Legacy/admin-created case: `uas_pilots.user_id` may remain `null`.
- Self-service create always binds `user_id` from the authenticated user.
- Client-submitted `user_id`, `employee_number` and `profile_status` are ignored in self-service flows.

## Authorization

Pilot authorization now distinguishes:

- `manageAnyPilots` / admin register access.
- `managePilot` / admin pilot profile update.
- `createOwn` / self-service pilot onboarding.
- `viewOwn` / current-user pilot workspace.
- `updateOwn` / current-user permitted profile update.
- `linkUser` / privileged admin linking of an existing pilot to a user.

The smallest role addition is the `pilots.self-service` permission for pilot-persona self-service access. Admin routes use `pilots.view`, `pilots.create` and `pilots.update`, while `super_admin` remains globally authorised by the existing provider gate.

## Self-Service Flow

```text
Login
  -> /my/pilot
  -> CurrentPilotProfile resolves by auth user
  -> no profile: onboarding empty state and /my/pilot/create
  -> create profile once with server-resolved user_id
  -> /my/pilot workspace
  -> /my/compliance pilot-owned summary
```

## Legacy Handling

Unlinked pilot records remain valid. No user accounts are auto-created for historical pilots. A privileged admin may link an existing unowned pilot record to a user through `PUT /pilots/{pilot}/user-link`; the action rejects pilots already owned by another user and users who already own a pilot profile.

Unlinking is intentionally omitted in this slice. A pilot identity is a regulated master record, and unlinking needs a separate governance decision to avoid accidental loss of accountability. User deletion uses `nullOnDelete()` and does not delete the pilot record.

## Audit Events

- `pilot.profile.created` records self-service/admin profile creation through the existing action.
- `pilot.profile.updated` records self-service/admin profile updates through the existing action.
- `pilot.user.linked` records self-service creation link establishment and privileged admin-managed linking.

Audit payloads only include ownership/link metadata needed for traceability.

## API Readiness

No API V1 endpoints were implemented in this slice. The current web self-service architecture prepares the next API slice because the future endpoints can consume the same queries:

```text
GET /api/v1/me
GET /api/v1/me/pilot
GET /api/v1/me/compliance
GET /api/v1/me/missions
GET /api/v1/me/logbook
```

## Verification

- `php artisan migrate --force`: passed, nothing to migrate.
- `php artisan route:list`: passed, 132 routes registered including `/my/pilot`, `/my/pilot/create`, `/my/pilot/edit`, `/my/compliance` and `pilots/{pilot}/user-link`.
- `php artisan test tests\Feature\Uas\RemediationUserPilotOwnershipTest.php --compact`: passed, 10 tests, 44 assertions.
- `php artisan test --filter=Pilot --compact`: passed, 16 tests, 82 assertions.
- `php artisan test --compact`: passed, 178 tests, 1858 assertions.
- `npm.cmd run build`: passed.
- `git diff --check`: passed with line-ending warnings only.
- Conflict marker scan across `app`, `routes`, `resources`, `tests` and `docs`: no matches.

Full-suite verification is tracked in the development log for this remediation slice.
