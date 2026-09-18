# TR-004 — Cross-Tenant Policy Hardening

## Status
Implemented repository-level hardening pass after TR-003.

## Invariant
Tenant membership and explicit platform authority are the only cross-organisation authorization boundaries. Legacy global UAS permissions no longer grant cross-tenant access to aircraft, missions or evidence.

## Hardened policies
- AircraftPolicy: resource access requires an active operator-aircraft assignment in an accessible tenant; update requires a managed tenant.
- MissionPolicy: view/update/briefing authorization is bound to the mission's uas_operator_id.
- EvidenceDocumentPolicy: document access and creation resolve through tenant ownership instead of documents.view/operators.view global bypasses.
- GisProjectPolicy: legacy GIS projects are authorized through their linked operator missions; mutation requires a managed tenant.

## Action-layer defense
Critical actions now re-authorize inside the application layer:
- RecordMissionBatteryUsage
- ReportAircraftDefect
- AssignMissionToGisProject
- ReleaseMission already re-authorized the mission inside its transaction.

This protects callers that bypass HTTP controllers.

## GIS cross-tenant invariant
Once a GIS project has a mission association, another mission from a different operator cannot be attached to that same project. This prevents one legacy GIS project becoming a bridge between tenants.

## Verification
TenantIsolationPolicyTest covers:
- Alpha user denied Bravo aircraft.
- Alpha user denied Bravo mission.
- Alpha user denied Bravo mission update.
- suspended membership immediately loses aircraft access.
- a GIS project cannot be linked across two operator tenants.

TR-005 should implement the complete operator membership lifecycle: invite, request, accept/reject, activate, suspend and end/revoke.
