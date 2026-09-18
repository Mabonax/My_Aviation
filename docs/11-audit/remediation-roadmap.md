# YAW Remediation Roadmap

Date: 2026-09-12

This roadmap is dependency-driven. It pauses isolated FR expansion until the product integration baseline is strengthened.

Cross-project benchmark note: this roadmap now incorporates local reference patterns from AB4IRERP for workspace/document architecture, `gperp-clinic` for versioned mobile APIs and response envelopes, and `C:\xampp\htdocs\mobile\app` for Flutter service/provider/navigation structure.

## P0 - Architectural Blockers

| Item | Objective | Dependency | Affected Domains | Migration | API | Web | Flutter | Tests | Acceptance Criteria |
|---|---|---|---|---|---|---|---|---|---|
| Corrective actions | Add first-class corrective action lifecycle. | Compliance controls | Compliance, Safety, Defects | Yes | Yes | Yes | Later | Lifecycle tests | Findings can create assigned, due, closed corrective actions. |

## Completed Remediation

| Slice | Status | Evidence | Remaining limits |
|---|---|---|---|
| User/pilot ownership | IMPLEMENTED/VERIFIED WEB | `User::pilotProfile()`, `CurrentPilotProfile`, `/my/pilot`, `/my/compliance`, `RemediationUserPilotOwnershipTest` | API/mobile endpoints are intentionally not implemented; registration-time pilot role assignment is still future work. |
| Operator membership and tenancy | IMPLEMENTED/VERIFIED WEB | `uas_operator_memberships`, `uas_operator_pilots`, `uas_operator_aircraft`, `CurrentOperatorContext`, `RemediationOperatorTenancyTest` | API/mobile endpoints are intentionally not implemented; GIS/document/finding scoping still needs follow-on work. |
| API V1 foundation | IMPLEMENTED/VERIFIED FOUNDATION | `routes/api.php`, Sanctum, `ApiResponse`, `ApiV1FoundationTest` | Only current-user/read-list foundation endpoints exist; mobile app, write APIs and broader resources remain future work. |
| Aircraft catalogue and onboarding | IMPLEMENTED/VERIFIED FOUNDATION | `uas_manufacturers`, `uas_aircraft_models`, `aircraft_model_id`, `uas:import-aircraft-catalogue`, `/aircraft-catalogue`, `/api/v1/aircraft-catalogue`, `AircraftCatalogueOnboardingTest` | Local media approval workflow, OpenAPI docs and Flutter aircraft onboarding remain future work. |
| Aircraft compliance readiness summary | IMPLEMENTED/VERIFIED FOUNDATION | `AircraftReadinessSummary`, aircraft list/detail readiness payloads, API aircraft readiness payloads, `AircraftCatalogueOnboardingTest` | Historical readiness snapshots, maintenance programme limits and Flutter readiness UI remain future work. |
| Mission compliance and release readiness | IMPLEMENTED/VERIFIED FOUNDATION | `MissionComplianceSummary`, `ReleaseMission`, `MissionReleaseGate` adapter, `/api/v1/missions/{mission}/compliance`, `MissionComplianceReleaseReadinessTest` | Release API write endpoint, dedicated snapshot table, document/training controls and Flutter readiness UI remain future work. |
| Post-flight propagation | IMPLEMENTED/VERIFIED FOUNDATION | `PropagatePostFlightRecords`, `PropagatePostFlightRequest`, actual mission fields, `PostFlightPropagationSummary`, `/missions/{mission}/post-flight-propagation`, `/api/v1/missions/{mission}/post-flight-propagation`, `MissionPostFlightPropagationTest` | Separate `in_flight`/`landed` state transitions, mobile/offline close-out, first-class maintenance programme counters and corrective-action workflow remain future work. |
| Aircraft package instantiation | IMPLEMENTED/VERIFIED FOUNDATION | `InstantiateAircraftPackage`, `uas_aircraft_components`, package fields on aircraft models/aircraft/batteries, aircraft web/API package summaries, `AircraftCatalogueOnboardingTest` | Maintenance remains baseline guidance only; Flutter/mobile onboarding and governed vendor package data are future work. |
| Evidence/document architecture | IMPLEMENTED/VERIFIED FOUNDATION | `uas_evidence_documents`, `uas_evidence_links`, `EvidenceDocumentPolicy`, `/evidence-documents`, `/api/v1/evidence-documents`, `EvidenceDocumentVaultTest` | Legacy evidence-reference backfill, preview/download, immutable version chains and Flutter/offline upload remain future work. |

## P1 - Core Product Integration

| Item | Objective | Dependency | Affected Domains | Migration | API | Web | Flutter | Tests | Acceptance Criteria |
|---|---|---|---|---|---|---|---|---|---|
| Pilot compliance summary | Centralise RPC, medical, rating, competency and expiry summary. | User/pilot ownership | Pilots, Training, Notifications | Maybe | Yes | Yes | Yes | Expiry boundary tests | Pilot sees explainable status, expiry and revalidation windows. |
| Post-flight propagation | Update logbook, folio, battery usage and maintenance counters from completed flight. | Mission execution model | Missions, Logs, Folios, Batteries, Maintenance | Done | Done | Done | Later | `MissionPostFlightPropagationTest` | Completed mission closure captures actuals, calculates duration server-side, idempotently creates/updates pilot logbook and aircraft folio records, summarises battery usage and carries checklist/defect/track evidence. |

## P2 - Required Workflow Gaps

| Item | Objective | Dependency | Affected Domains | Migration | API | Web | Flutter | Tests | Acceptance Criteria |
|---|---|---|---|---|---|---|---|---|---|
| Training execution | Add learner enrolment, assessment attempts and certificate issuance. | Pilot ownership | Training, Pilots | Yes | Yes | Yes | Yes | Workflow tests | Competency records can feed pilot compliance summary. |
| Application pack snapshots | Persist immutable prepared/submitted pack versions. | Forms/fees source data | Operators, Regulations, Documents | Yes | Maybe | Yes | N/A | Snapshot/export tests | Pack can be exported and later audited as submitted. |

## P3 - Important Enhancements

- Regulatory UX: explain current forms, fees, rule versions and mission block reasons to pilots.
- Notification delivery: provider-backed mail/push/SMS where required, with preferences and retry evidence.
- Reporting: audit exports, logbook exports, flight folio exports, compliance reports and GIS deliverables.
- GIS reporting: feature evidence, report builder and rendered map verification.
- Maintenance: maintenance programme, due calculations, maintenance actions and release-to-service.

## P4 - Future Functionality

- Offline mission execution and queued evidence upload.
- Device telemetry ingestion and richer flight-event capture.
- Advanced GIS processing integrations.
- External authority API integrations only where documented and authorised.

## Recommended Next Development Slice

Implement corrective actions now that compliance findings and the governed evidence vault both exist.

Rationale:

- Compliance findings already identify issues, due dates and recommended actions.
- Defects, post-flight exceptions and register findings need assigned owners, status transitions and closure evidence.
- The evidence vault can now attach proof to corrective-action closure instead of adding another JSON-only evidence field.

Proposed slice:

- Add corrective action records linked to compliance findings and optionally defects/missions/operators.
- Implement assigned/due/closed lifecycle transitions with audit entries.
- Require closure notes and evidence links for closure.
- Expose web/API summaries and tests for overdue, open and closed states.

## Benchmark-Informed Follow-On Order

1. Add first-class corrective action lifecycle for compliance findings, defects and post-flight follow-up.
2. Scaffold the YAW Flutter app using the Drhealth Dio/Riverpod/go_router pattern.
3. Backfill legacy evidence references into the governed evidence vault.
4. Add first-class maintenance programmes, due calculations and release-to-service workflows.
