# YAW Product Coverage Matrix

Date: 2026-09-12

This matrix separates repository-level backend/web verification from full product completion. Evidence is taken from current code, migrations, routes, tests and adjacent workspace inspection. `VERIFIED` in this document means repository evidence exists for that layer only; it does not imply API, Flutter, production data or deployed-browser completion.

Cross-project benchmark note: AB4IRERP confirms stronger web workspace/document patterns are available locally, while `gperp-clinic` and `C:\xampp\htdocs\mobile\app` confirm a proven Laravel mobile API + Flutter client pattern. These raise the expected product baseline for YAW API, mobile, evidence and workspace completion.

| FR / Area | Capability | DB | Domain | App | Web | API | Mobile | Tests | Integration | Production Data | Overall | Gap |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| FR-PIL-001 | Pilot profile and user ownership | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | User-linked self-service is implemented; mobile/API profile endpoints and full licence wallet are still missing. |
| FR-PIL-002 | RPC and certificate compliance | VERIFIED | PARTIAL | PARTIAL | PARTIAL | NOT STARTED | NOT STARTED | PARTIAL | PARTIAL | PARTIAL | PRODUCT INTEGRATION PARTIAL | Certificate records exist, but licence wallet, API, mobile and full regulatory UX are missing. |
| FR-REC-003 | Audit trail | VERIFIED | VERIFIED | VERIFIED | PARTIAL | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | N/A | BACKEND VERIFIED | Many actions audit, including evidence upload; audit viewer/export and API access are incomplete. |
| FR-AIR-001/002 | Aircraft inventory, catalogue, registration, approvals and readiness | VERIFIED | VERIFIED | VERIFIED | VERIFIED | PARTIAL | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB/API FOUNDATION VERIFIED | Aircraft can be selected from a governed catalogue, expose derived readiness and instantiate package batteries/components; mobile onboarding, readiness snapshots and maintenance programme limits are still missing. |
| FR-MIS-001/002 | Mission planning, compliance summary, release gate and post-flight propagation | VERIFIED | VERIFIED | VERIFIED | VERIFIED | PARTIAL | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB/API FOUNDATION VERIFIED | Mission now carries operator context, consumes aircraft readiness, exposes compliance summary, blocks red release server-side, captures close-out actuals and propagates completed mission evidence into logbook/folio records; release API write, mobile/offline execution and dedicated snapshots remain incomplete. |
| FR-GEO-001/002/003 | Mission geometry, overlays and spatial rules | VERIFIED | VERIFIED | VERIFIED | PARTIAL | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | BLOCKED | BACKEND VERIFIED | Official airspace dataset import and rendered map verification are missing. |
| FR-CHK-001/002 | Pre-flight and post-flight checklists | VERIFIED | VERIFIED | VERIFIED | VERIFIED | PARTIAL | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB/API FOUNDATION VERIFIED | Pre-flight feeds release readiness and post-flight now gates propagation into logbook/folio records; mobile/offline checklist execution remains incomplete. |
| FR-CREW-001 | Mission crew management | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | N/A | BACKEND/WEB VERIFIED | Crew self-service, mobile acceptance and competency integration are missing. |
| FR-TRK-001 | Flight tracks | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Telemetry/device ingestion and mobile flight execution are missing. |
| FR-BAT-001 | Battery inventory and mission usage | VERIFIED | VERIFIED | VERIFIED | VERIFIED | PARTIAL | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB/API FOUNDATION VERIFIED | Battery usage is summarised into post-flight folios without double-counting cycles; catalogue package instantiation now seeds compatible batteries while deeper maintenance-programme integration remains incomplete. |
| FR-DEF-001 | Aircraft defects | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Defect close/rectification workflow and stronger release-gate integration are missing. |
| FR-OPS-001/002 | Operator profile, membership and certificate lifecycle | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Operator membership/pilot/aircraft assignments are implemented; submission tracking and API/mobile access remain incomplete. |
| FR-OM-001..004 | Operations manual control, distribution, acknowledgement and training triggers | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Mobile acknowledgement and deeper training execution linkage are missing. |
| FR-TRN-001/002 | Training course structure and compliance links | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Learner journey, assessment attempts, certificate issuance and mobile learning are missing. |
| FR-REG-002/003 | Regulatory versioning and traceability | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | BLOCKED | BACKEND/WEB VERIFIED | Controlled SACAA import/reconciliation and pilot-facing reason display are incomplete. |
| FR-FRM-001 | SACAA form register | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | BLOCKED | BACKEND/WEB VERIFIED | Official catalogue import and user action integration are incomplete. |
| FR-FEE-001 | Regulatory fee engine | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | BLOCKED | BACKEND/WEB VERIFIED | Current SACAA fee dataset and action-driven fee resolution are incomplete. |
| FR-PACK-001 | Application and renewal pack builder | N/A | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Pack snapshots, PDF export and SACAA correspondence tracking are missing. |
| FR-NOT-002 | Compliance notification engine | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Delivery providers, queue delivery proof, preferences and mobile push are missing. |
| FR-EXT-001 | External regulatory integration register | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | BLOCKED | BACKEND/WEB VERIFIED | Real authority API integration is blocked until documented/authorised sources exist. |
| FR-CMP-001 | Compliance register | N/A | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Scores need explainable control/evidence model and historical snapshots. |
| FR-GIS-001..004 | GIS projects, mission links, datasets, layers, features, opportunities/findings | VERIFIED | VERIFIED | VERIFIED | VERIFIED | NOT STARTED | NOT STARTED | VERIFIED | PARTIAL | PARTIAL | BACKEND/WEB VERIFIED | Evidence attachments, report builder and rendered GIS verification are missing. |
| API V1 | Mobile/web REST contract | VERIFIED | N/A | VERIFIED | N/A | PARTIAL | BLOCKED | VERIFIED | PARTIAL | N/A | FOUNDATION IMPLEMENTED | Sanctum auth, V1 envelope, current-user pilot/operator/aircraft/mission endpoints, aircraft catalogue list/detail and evidence document list/upload exist; broad write APIs remain unimplemented. |
| Flutter V1 | Pilot field app | N/A | N/A | N/A | N/A | BLOCKED | NOT STARTED | NOT STARTED | NOT STARTED | N/A | NOT IMPLEMENTED | Adjacent Flutter app is Drhealth, not YAW. |
| Offline | Field offline/sync | N/A | NOT STARTED | NOT STARTED | N/A | BLOCKED | NOT STARTED | NOT STARTED | NOT STARTED | N/A | NOT IMPLEMENTED | No YAW mobile/offline cache, sync queue or conflict model found. |

## Completion Estimate

| Area | Estimate | Basis |
|---|---:|---|
| Domain/business layer | 65% | Broad domain models/actions/services exist, but ownership, catalogue, compliance aggregation and execution integrations remain partial. |
| Web application | 60% | Most current FR slices have authenticated Inertia screens; several journeys are still admin-like and not fully integrated. |
| Pilot compliance | 50% | Pilot/profile/certificate foundations and user-owned self-service profile now exist; licence wallet, API/mobile and complete expiry UX are missing. |
| Aircraft/fleet | 62% | Aircraft records, catalogue onboarding, registrations, approvals, batteries, defects, derived readiness and package-instantiated batteries/components exist; readiness snapshots, mobile onboarding and maintenance programme integration are still missing. |
| Mission operations | 70% | Mission, geometry, shared release readiness, checklist, crew, tracks, batteries, defects, actual close-out capture and post-flight propagation exist; mobile/offline execution and richer maintenance/corrective-action close-out remain incomplete. |
| Operator governance | 60% | Operator/case/manual workflows are strong web/backend slices; membership and submission completion are partial. |
| Regulatory engine | 60% | Requirements/forms/fees/versioning exist; official source imports and user-facing applicability are incomplete. |
| Compliance | 54% | Dashboard/register/traceability and governed evidence links exist; control/result snapshots and corrective action lifecycle are missing. |
| Maintenance | 25% | Defects and aircraft approvals exist; maintenance programme/counters/release-to-service are not yet modelled end to end. |
| Safety | 30% | Defect and compliance finding foundations exist; hazards/incidents/corrective action lifecycle is incomplete. |
| Training | 45% | Course structure and compliance links exist; learner/assessment/certification flow is incomplete. |
| GIS | 55% | Project-to-feature hierarchy exists; evidence attachments, report builder and map rendering are missing. |
| Reporting | 38% | Several reports/packs and evidence register exist; exports/PDFs/snapshots are limited. |
| API | 28% | API V1 foundation exists with Sanctum, stable envelopes, current-user endpoints, scoped aircraft/mission lists, aircraft catalogue detail, mission compliance detail and evidence document list/upload; broad write/mobile execution APIs remain missing. |
| Flutter | 0% | No YAW Flutter app found. |
| Offline | 0% | No YAW offline architecture found. |
| Notifications | 45% | Planning/status engine exists; delivery providers and push are missing. |
| Production hardening | 25% | Tests/migrations are strong, but production datasets, browser sign-off, security isolation and deployment proof are incomplete. |

Backend/web completion estimate: approximately 55-65%.

Complete YAW product completion estimate: approximately 42-52%, because API foundation, aircraft catalogue onboarding, aircraft readiness, package instantiation, evidence vault, mission release readiness and post-flight propagation now exist, while Flutter, offline field workflows, corrective actions and richer maintenance workflows are not implemented.

## Benchmark Effect

| Area | Effect |
|---|---|
| API | Foundation is now implemented for authentication, current-user context, scoped aircraft/mission reads, catalogue reads and evidence document list/upload; broad write/mobile execution APIs remain incomplete. |
| Flutter | Completion remains 0%, but the implementation pattern is now concrete: Dio provider, secure token storage, `ApiEnvelope<T>`, Riverpod feature services and `go_router`. |
| Web UI | Completion estimate does not change, but AB4IRERP shows YAW should consolidate admin-like registers into workspace journeys. |
| Evidence/documents | Foundation improved: governed uploads and polymorphic links now exist, while backfill, preview/download and immutable version chains still trail AB4IRERP's document/version/access pattern. |

## Remediation Phase A Update

User-to-pilot self-service ownership is now repository-verified at the web/backend layer:

- `User::pilotProfile()` resolves the one-user-to-one-pilot relationship.
- `/my/pilot` provides onboarding, view and edit for the authenticated pilot user.
- `/my/compliance` provides the first pilot-owned compliance entry point.
- Self-service create/update ignore client-submitted ownership and privileged status fields.
- Admin pilot management remains separate at `/pilots`.

API, Flutter and offline completion remain unchanged.

## Remediation Phase B Update

Operator membership and tenancy are now repository-verified at the web/backend layer:

- `User -> Operator Membership -> Operator` is first-class through `uas_operator_memberships`.
- Operator-to-pilot and operator-to-aircraft assignments are first-class pivots.
- New mission records can carry `uas_operator_id`, and membership users are blocked from unauthorized operator/pilot/aircraft combinations.
- Operator, aircraft and mission list access is now scoped for active operator members.

API, Flutter and offline completion remain unchanged, but `/api/v1/me/operators`, `/api/v1/aircraft` and `/api/v1/missions` now have a tenancy model to build against.

## API V1 Foundation Update

API V1 is now foundation-level implemented with Sanctum token auth, a stable response envelope, `/api/v1/me`, `/api/v1/me/pilot`, `/api/v1/me/operators`, `/api/v1/aircraft`, `/api/v1/aircraft/{aircraft}`, `/api/v1/aircraft-catalogue`, `/api/v1/aircraft-catalogue/{aircraftModel}` and `/api/v1/missions`. This does not complete mobile, offline execution, write endpoints or broader domain APIs.

## Aircraft Catalogue Update

Aircraft catalogue and physical onboarding are now repository-verified at backend/web/API foundation level:

- `uas_manufacturers` and `uas_aircraft_models` separate catalogue technical data from physical aircraft assets.
- `uas_aircraft.aircraft_model_id` links physical aircraft to catalogue models while preserving legacy rows without catalogue links.
- `uas:import-aircraft-catalogue` imports JSON/CSV source files idempotently and preserves provenance/media reference metadata without downloading images.
- `/aircraft-catalogue` and `/aircraft/create` support catalogue browsing and physical onboarding from selected models.
- `/api/v1/aircraft-catalogue` and physical aircraft API detail expose catalogue summaries through the V1 envelope.

Flutter, readiness snapshots, corrective actions and maintenance programme completion remain future work.

## Aircraft Readiness Update

Aircraft compliance readiness is now repository-verified at backend/web/API foundation level:

- `AircraftReadinessSummary` derives readiness at request time from catalogue linkage, serviceability, registrations, UASLA/RLA approvals, defects and compatible batteries.
- Aircraft list/detail web payloads now include readiness status and explainable per-control checks.
- API V1 aircraft list/detail payloads expose the same readiness contract.
- Green, amber and red outcomes are covered by `AircraftCatalogueOnboardingTest`.

Historical readiness snapshots, maintenance programme limits, package-instantiated components and Flutter readiness UI remain future work.

## Mission Compliance Update

Mission compliance and release readiness are now repository-verified at backend/web/API foundation level:

- `MissionComplianceSummary` derives mission release readiness from aircraft readiness, pilot readiness, operator status, spatial rules, pre-flight checklist, mission approvals and risk assessment.
- `MissionReleaseGate` now adapts the shared summary into the legacy release-gate result shape.
- `ReleaseMission` blocks red missions server-side and records audit evidence for green/amber releases.
- Mission detail and API V1 mission compliance endpoints expose structured controls.
- Mission list exposes lightweight stored compliance status, blocking count and warning count.

Release API writes, dedicated compliance snapshot storage, training/document controls and Flutter readiness UI remain future work.
