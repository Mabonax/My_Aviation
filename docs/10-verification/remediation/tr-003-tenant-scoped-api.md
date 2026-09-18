# TR-003 — Tenant-Scoped API

## Status

Implemented tenant-bound API foundation on top of TR-001 and TR-002.

## Security boundary

Operational API requests now resolve one canonical operator through `CurrentOperatorContext::requireFromRequest()`.

Clients use:

`X-YAW-Operator: {operator_id}`

A sole active membership may resolve automatically. Multiple memberships require explicit selection. Platform authority does not imply an unscoped operational query: an explicit operator is required when no single context can be inferred.

## Tenant-scoped resources

- Aircraft list/detail
- Mission list/detail
- Mission compliance
- Post-flight propagation read/write
- Mission aeronautical briefing read/generate/acknowledge
- Defects
- Batteries
- GIS projects linked to operator missions
- Compliance findings attached to the operator, its missions or its aircraft
- Evidence list/upload

## API additions

- `GET /api/v1/defects`
- `GET /api/v1/batteries`
- `GET /api/v1/gis-projects`
- `GET /api/v1/compliance/findings`

Existing operational endpoints now consume the active operator context.

## Isolation behavior

A resource belonging to another operator is returned as 404 from tenant-bound detail routes even when the same authenticated user has another membership that could access it. The client must intentionally switch `X-YAW-Operator`.

Evidence uploads are rejected when their declared operator or resolved target falls outside the active operator context.

GIS projects without a mission linked to the active operator are not exposed by the tenant API. This avoids inventing tenant ownership for legacy GIS rows that do not yet carry an operator key.

## Verification coverage

API regression coverage now checks:
- operational endpoints require context when multiple memberships exist;
- explicit operator selection scopes global/platform users;
- mission detail cannot cross the selected tenant;
- TR-002 operator-context behavior remains the source of tenant selection.

## Follow-up

TR-004 should harden object policies, route binding and action-level authorization across web and API so the same tenant invariant is enforced below controller/query adapters.
