# Remediation Phase B - Operator Membership, Tenancy and Access Scoping

Date: 2026-09-12

## Scope

Remediation Phase B implemented the first-class operator membership model required before API V1 and Flutter list endpoints expose operator, aircraft or mission data.

## Implemented

- Added `uas_operator_memberships` for user-to-operator membership with role, status and lifecycle timestamps.
- Added `uas_operator_pilots` and `uas_operator_aircraft` as first-class operator assignment pivots.
- Added nullable `uas_operator_id` to `uas_missions` so mission records can carry reliable operator context.
- Added `User::operatorMemberships()`, `User::activeOperatorMemberships()` and `User::operators()`.
- Added `UasOperator::memberships()`, `activeMemberships()`, `users()`, `pilots()`, `aircraft()`, `missions()` and `documents()`.
- Added `CurrentOperatorContext` for active-membership operator resolution, accessible operator IDs and manager checks.
- Added membership creation/status actions and operator pilot/aircraft assignment actions.
- Added membership and assignment routes/controllers/requests.
- Added operator-aware mission options, mission presenter output and mission list scoping.
- Added an aircraft index adapter scoped to operator membership.
- Added operator detail UI sections for members, assigned pilots, assigned aircraft and operator missions.

## Tenancy Behavior

Normal users can see an operator only through an active `uas_operator_memberships` row. Suspended or ended memberships do not grant access.

Global administrative access remains explicit through UAS permissions and the existing super-admin gate. Operator membership roles do not replace global application roles.

## Mission Guard

Membership users creating missions must submit an accessible `uas_operator_id`. When a pilot or aircraft is selected, that pilot or aircraft must be actively assigned to the same operator.

Users with global `missions.create` keep the existing administrative mission creation behavior for legacy records and back-office workflows.

## Audit Evidence

The following actions now create audit entries:

- `membership.created`
- `membership.activated`
- `membership.suspended`
- `membership.ended`
- `pilot.assigned_to_operator`
- `aircraft.assigned_to_operator`

## Verification

- `php artisan migrate --force`: passed; applied `2026_09_12_110000_create_uas_operator_membership_tables`.
- `php artisan route:list`: passed; 137 routes registered.
- `php artisan test tests\Feature\Uas\RemediationOperatorTenancyTest.php`: passed; 9 tests, 56 assertions.
- Adjacent regression suite across mission, operator profile, Phase A pilot ownership and Phase B tenancy: passed; 30 tests, 220 assertions.
- `php artisan test --compact`: passed; 187 tests, 1914 assertions.
- `npm.cmd run build`: passed.
- `git diff --check`: passed with line-ending warnings only.
- Conflict-marker scan across app/routes/resources/tests/docs/database: no matches.
- DB metadata inspection confirmed the membership, pilot assignment, aircraft assignment and mission operator indexes exist.
- `SHOW CREATE TABLE` confirmed operator membership keeps regulated operator history by not cascading operator deletes; nullable `user_id` and `created_by` use `ON DELETE SET NULL`.

## Remaining Gaps

- API V1 routes/resources are still intentionally not implemented.
- Flutter/mobile operator selection is still not implemented.
- Aircraft catalogue/package onboarding is still separate future work.
- Compliance findings and regulatory documents are only partially operator-derived until a unified evidence/control model exists.
- Existing operator JSON fields remain readable for legacy data, but first-class pivot relationships are now the preferred source for new operator membership, pilot and aircraft assignments.
