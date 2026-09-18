# TR-005 — Operator Membership Lifecycle

## Status
Implemented as the first complete membership workflow over the YAW multi-tenant foundation.

## Lifecycle
Two entry paths are supported:

1. Operator invitation: pending -> accepted/active OR declined/ended.
2. Pilot join request: pending -> approved/active OR rejected/ended.

An active membership can be suspended, reinstated, or ended. Ended memberships are terminal; a new membership record is required for a later relationship so history remains auditable.

## Data
Memberships now retain source (admin, invitation, join_request), optional message, response timestamp and response actor in addition to invitation, activation and termination timestamps.

## API
Authenticated V1 routes:
- GET /api/v1/me/operator-memberships
- POST /api/v1/operators/{operator}/membership-invitations
- POST /api/v1/operators/{operator}/join-requests
- POST /api/v1/operator-memberships/{membership}/transition

Self-service acceptance/decline is restricted to the invited user. Approval/rejection/suspension/reinstatement/end requires management authority in that membership's operator.

## Security
Pending membership never grants tenant access. Suspended and ended membership do not grant tenant access. CurrentOperatorContext continues to resolve only active memberships.

## Audit
Lifecycle events are recorded as membership.invited, membership.requested, membership.invitation_accepted, membership.invitation_declined, membership.request_approved, membership.request_rejected, membership.suspended, membership.reinstated and membership.ended.

## Next
TR-006 should connect active user membership to professional Pilot-to-Operator approval/assignment, including approval periods and role validity.
