# TR-002 — Active Operator Context

## Status

Implemented foundation.

## Canonical contract

YAW resolves operator context server-side through `CurrentOperatorContext`.

API clients select an operator by sending:

`X-YAW-Operator: {operator_id}`

The server never trusts the header by itself. The requested operator is resolved against the authenticated user's active operator memberships unless the user has explicit platform authority from TR-001.

## Resolution rules

1. One active operator membership and no header: resolve that operator automatically.
2. Multiple active memberships and no header: no operator is selected and `selection_required` is true.
3. Header references an active accessible membership: resolve that operator.
4. Header references an inaccessible operator: reject with HTTP 403.
5. Platform authority may resolve an explicitly requested operator across tenants.
6. Operations that require a tenant can use `requireFromRequest()`, which returns HTTP 409 when no active context can be resolved.

## API

`GET /api/v1/me/operator-context`

Response includes the resolved operator, current membership role/status, whether the user has platform authority, whether explicit selection is required, and the canonical header name.

This endpoint is intentionally read-only. The selected operator remains explicit request context rather than mutable global user state, preventing one device/session from silently changing another device's workspace.

## Next integration

TR-003 should apply the canonical request context to tenant-scoped operational endpoints such as aircraft, missions, defects, compliance, GIS and evidence. Web/Inertia workspace selection can reuse the same resolver with session-backed selection while keeping authorization server-side.
