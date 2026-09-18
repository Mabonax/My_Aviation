# Pilot Product Journey Audit

Date: 2026-09-12

## Journey Status

| Step | Status | Evidence | Gap |
|---|---|---|---|
| Register | IMPLEMENTED WEB | Laravel auth routes in `routes/auth.php` | Registration creates a `User`, not a full remote pilot onboarding journey. |
| Role/persona | PARTIAL | `uas_role_user`, `UasRole`, `role` on users | Persona assignment is administrative/test-seeded; no pilot onboarding role flow found. |
| Pilot identity | IMPLEMENTED WEB/VERIFIED | `uas_pilots.user_id`, `User::pilotProfile()`, `CurrentPilotProfile`, `/my/pilot` | API/mobile identity endpoints are still missing. |
| Pilot compliance profile | PARTIAL | `PilotCertificate`, `PilotComplianceEvaluator`, expiry notification planner | RPC/certificate basics exist; ratings, medical, competencies and licence wallet are not fully integrated. |
| Aircraft onboarding | PARTIAL | `UasAircraft`, registration and approval models | No manufacturer/model/variant catalogue or package pre-population. |
| Battery inventory | PARTIAL | `UasBattery`, `UasMissionBatteryUsage` | Actual batteries exist, but package-driven battery creation and aircraft ownership are missing. |
| Mission planning | IMPLEMENTED WEB/PARTIAL PRODUCT | `UasMission`, `MissionOptions`, `MissionReleaseGate`, `uas_missions.uas_operator_id` | Mission now supports operator context and membership-user assignment guards, but no API/mobile and no full risk/training integration. |
| Compliance gate | PARTIAL | `MissionReleaseGate` checks pilot certificate, aircraft registration/approval and serviceability | Does not yet include operator, OpsSpec, battery health, checklist completion, risk and training. |
| Pre-flight | IMPLEMENTED WEB/PARTIAL PRODUCT | `UasMissionChecklist` and pre-flight controller | Checklist is captured, but final go/no-go aggregation is incomplete. |
| Flight | PARTIAL | Flight tracks and battery usage can be recorded | No mobile flight start/stop, telemetry ingestion or mission execution state flow found. |
| Post-flight | IMPLEMENTED WEB/PARTIAL PRODUCT | Post-flight checklist route/model | Does not automatically update pilot logbook, aircraft folio, batteries and maintenance counters. |
| Logbook/Folio | PARTIAL | `PilotLogEntry`, `AircraftFlightFolio` | Records exist but are not yet generated from completed mission flow. |
| Notifications | PARTIAL | Compliance notifications and certificate expiry planning | No mobile push, preferences or provider-backed delivery proof. |
| Regulations/Fees/Forms UX | PARTIAL | Requirements, forms and fees web registers | Not yet surfaced as pilot-action guidance for renewal/mission blocks. |

## Canonical Pilot Journey Answer

Can a new remote pilot register, create their pilot compliance identity, add a drone from a known aircraft catalogue, register compliance information and batteries, see upcoming expiry information and current regulatory requirements/fees, plan a mission using existing information, receive an explainable mission compliance decision, execute the flight from Flutter, and have post-flight update pilot/aircraft/battery/maintenance/compliance records?

Current answer: no.

Why:

- Registration creates a `User`; pilot onboarding is not integrated.
- `UasPilot.user_id` and `/my/pilot` now provide a self-service pilot identity, but API/mobile onboarding is still missing.
- Operator membership now defines which operators, assigned pilots, assigned aircraft and missions a normal user can access.
- Aircraft records do not derive from a model catalogue.
- Mission planning references operator, pilot and aircraft for new scoped workflows, but lacks full risk/training/battery/checklist aggregation.
- There is no YAW REST API or YAW Flutter app.
- Post-flight propagation to logbook, folio, batteries, maintenance and compliance is not implemented end to end.

## Benchmark Effect On Pilot Journey

The reference repositories clarify the target journey shape:

- AB4IRERP shows the web journey should become workspace-led rather than scattered admin registers.
- The clinic backend shows the mobile/API journey should start with current-user identity, stable response contracts and scoped authenticated routes.
- The Drhealth Flutter app shows the field app should use typed services/providers and route guards, not screen-local HTTP calls.

User-to-pilot ownership has now been implemented as Remediation Phase A. Operator membership and tenancy have now been implemented as Remediation Phase B. Together they provide the secure anchors for `/api/v1/me/pilot`, `/api/v1/me/operators`, `/api/v1/aircraft` and `/api/v1/missions`.

## Immediate Acceptance Gap

Create a single end-to-end integration test covering:

```text
User -> Pilot -> Certificate -> Aircraft -> Registration/Approval -> Battery -> Mission -> Release Gate -> Pre-flight -> Battery Use -> Track -> Post-flight -> Logbook/Folio
```

Expected result: still missing and should be P1 after operator membership/API foundations.

## Remediation Phase A Result

```text
Login
-> CurrentPilotProfile
-> Create/View/Edit own pilot profile
-> My Compliance
-> Missions/logbook readiness
```

The web/backend pilot identity journey is verified. Operator membership scoping is also verified for web/backend access. Remaining journey gaps are role assignment during registration, aircraft catalogue ownership, central compliance summary, API/mobile access and post-flight propagation.
