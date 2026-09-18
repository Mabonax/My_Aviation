# Aeronautical information and mission briefing

Date: 2026-09-15. Internal policy version: `YAW-AIM-1.0`.

This addendum implements the supplied YAW Aeronautical Information Integration brief within the existing UAS architecture. The backend is `C:\xampp\htdocs\myaviation`; the verified mobile checkout is `C:\xampp\htdocs\yaw_app`. The earlier proposed `C:\xampp\htdocs\yaw\_app` path does not exist.

## Requirements and acceptance

| ID | Requirement | Acceptance evidence |
|---|---|---|
| FR-AIM-001 | Aeronautical Information Register | Authenticated search, type/provider/status/validity filters, pagination, raw source, interpretation, geometry and revision detail. |
| FR-AIM-002 | Provider Ingestion Framework | Canonical DTO and repository contracts; manual/publication/fixture adapters; atomic, idempotent ingestion; raw provenance retained; revisions and cancellations supersede previous interpretation. |
| FR-AIM-003 | Mission Aeronautical Relevance | Shared backend spatial, vertical and temporal assessment using existing mission geometry; configurable buffers; uncertainty remains visible and conservative. |
| FR-AIM-004 | Mission Pre-flight Briefing | Persistent revisioned mission, source, policy and item-assessment snapshots with generation time, validity, counts and source health. |
| FR-AIM-005 | Briefing Acknowledgement | Scoped pilot/operator review recorded separately with actor/time; idempotent per actor; cannot override blockers or acknowledge a stale/superseded snapshot. |
| FR-AIM-006 | Mission Release Integration | Existing compliance summary/gate/release action consumes the briefing; source failure, missing coverage, stale evidence and hard blockers prevent release; released evidence references the presented revision. |
| FR-AIM-007 | API V1 Aeronautical Information | Existing envelope/auth/policies; web and API call the same actions/queries; Flutter displays backend decisions. |
| FR-AIM-008 | Provider Freshness / Source Health | Completed sync status, source dataset timestamp, coverage and authority distinguish fresh, stale, unavailable and a verified empty result. |

## Policy and regulatory basis

Responsible roles are the operator's operations manager and assigned pilot, within existing mission authorization. The implementation is an internal release-control policy, effective 2026-09-15; the 500 m horizontal buffer, 100 ft vertical buffer and 60 minute briefing lifetime are configurable internal defaults, not statutory minima. Changes to those settings invalidate earlier briefings through the policy fingerprint.

SACAA remains the regulatory authority under ADR-005. The [SACAA NOTAM summary page](https://www.caa.co.za/industry-information/aeronautical-information-notam-summaries/) states that website summaries are valid only at creation and should not be used for flight preparation. [ATNS File2Fly](https://file2fly.atns.co.za/AesRepository/html/en_GB/homepage_HB.html) describes a briefing service, but does not establish a machine-to-machine API contract. [SACAA publication references](https://www.caa.co.za/industry-information/aip-sup/) can be retained as reference metadata. These pages were checked on 2026-09-15; no live operational dataset was ingested.

The provider adapter, not incoming JSON, assigns classification and operational usability. Default release requires `atns_aim`; that adapter is intentionally unconfigured, so release fails closed until approved operational access and coverage exist. A public reference, manual import or fixture never establishes operational clearance.

## Boundaries

All eleven requested information types are represented. Normalization retains incomplete fields and raw content; Q-line extraction is partial metadata parsing. Free-text NOTAM/MET interpretation, full AIXM/SWIM parsing and authoritative altitude conversion require validated future adapters. Unsupported geometry, missing dates, incompatible vertical datums and scheduled notices remain uncertain. A definitive non-overlap on one dimension excludes an item; unknown applicability does not.

See the [feature register](../07-feature-register/aeronautical-information.md), [ADR-008](../09-decisions/ADR-008-aeronautical-source-and-briefing-evidence.md), [API/import contract](../03-platform-architecture/aeronautical-information-api.md), and [verification report](../10-verification/remediation/aeronautical-information-integration.md).

## FR-AIM-009 — Operational acceptance and provider readiness

Remediate the TypeScript/authentication baseline; exercise authenticated web/API and Android flows; formalize provider capabilities, approval evidence, full/delta semantics, ordered lifecycle updates, source health, security and reusable conformance tests; prepare formal ATNS requirements. Official-source unavailability continues to block release. Acceptance categories are independently reported in the [FR-AIM-009 evidence record](../10-verification/remediation/fr-aim-009-operational-acceptance.md).
