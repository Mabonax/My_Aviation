# TR-007 — Web Operator Workspace

## Status
Implemented.

## Web context
The API header model remains supported. Browser/Inertia sessions additionally persist the selected operator as yaw_operator_id. CurrentOperatorContext is the single resolver for both transports.

## Shared Inertia state
Every authenticated Inertia page receives operatorWorkspace with:
- active_operator
- accessible operators
- requires_selection
- can_manage

## UI
The application sidebar now includes an operator workspace switcher. The dashboard displays the active operator and warns users with multiple memberships when explicit selection is required.

## Security
POST /operator-workspace validates that the authenticated user can access the selected operator. A user cannot write an arbitrary tenant ID into their session. Suspended/ended memberships cease resolving through CurrentOperatorContext.

## Transport model
Web: session-backed yaw_operator_id.
API/mobile: X-YAW-Operator header.
Both resolve through CurrentOperatorContext.

## Next
TR-008 should implement the equivalent operator workspace UX in Flutter: no-operator state, invitations/join requests, operator selection/switching, and propagation of X-YAW-Operator to tenant-bound API calls.
