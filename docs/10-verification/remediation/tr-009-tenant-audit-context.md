# TR-009 — Tenant Audit Context

## Status
Implemented.

## Objective
Make operator identity immutable audit evidence rather than reconstructing tenancy later from mutable resource relationships.

## Schema
uas_audit_entries now stores:
- uas_operator_id
- operator_context_source

The operator reference is nullable for genuinely platform-level or personal-pilot events.

## Resolution precedence
1. explicit operator ID supplied by the action
2. active request operator resolved through CurrentOperatorContext
3. direct operator auditable
4. tenant-bearing auditable relationship when unambiguous
5. null when tenant identity cannot be established safely

The resolver deliberately does not guess when a resource can belong to multiple operators.

## Context sources
Examples:
- explicit
- api_header
- web_session
- auditable
- auditable_relation

## Result
Existing actions that already use RecordAuditEntry automatically gain tenant context without each action duplicating tenancy logic.

## Regulatory value
Audit exports can now establish actor + action + subject + operator + time directly from the immutable audit record, even if resource assignments change later.

## Next
TR-010 should implement the adversarial isolation verification suite across web/API/domain actions and validate suspension, workspace switching and platform authority boundaries.
