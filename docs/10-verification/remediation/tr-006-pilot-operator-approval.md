# TR-006 — Pilot ↔ Operator Approval

## Status
Implemented.

TR-006 separates organisational membership from operational pilot approval. A user may be an active operator member without automatically being authorised to act as a remote pilot.

## Chain
User -> active Operator Membership -> user-owned Pilot Profile -> Operator Pilot Approval -> Mission assignment.

## Approval requirements
- pilot profile must belong to a user;
- that user must have an active membership in the same operator;
- approval has an operational role and validity period;
- approval can be active, suspended or ended;
- current validity requires active assignment, date validity and active linked membership.

## Mission protection
Mission creation rejects a pilot/operator combination unless PilotOperatorApproval resolves a currently valid approval. Mission pilot options are filtered to current approved pilots.

## Evidence
uas_operator_pilots now stores the linked membership, approver, approval timestamp, suspension timestamp and termination timestamp in addition to role and approval dates.

## Audit
Approval, suspension, reinstatement and termination produce TR-006 audit events.

## Next
TR-007 should make operator workspace context first-class in the web UI: operator switcher, tenant-aware navigation, dashboard and membership/approval management surfaces.
