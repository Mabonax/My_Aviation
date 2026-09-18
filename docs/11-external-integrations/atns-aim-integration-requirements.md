# ATNS aeronautical information integration requirements

**Prepared by:** Various Media Technologies / YAW
**Intended engagement:** Air Traffic and Navigation Services (ATNS)
**Version:** 1.0, 2026-09-16
**Status:** YAW requirements for provider confirmation; no interface, commercial agreement or operational approval is implied.
**Traceability:** FR-AIM-009; [capability questionnaire template](atns-provider-capability-template.json); [implementation evidence](../10-verification/remediation/fr-aim-009-operational-acceptance.md).

## 1. Purpose

YAW seeks authoritative aeronautical information for UAS mission planning, pre-flight briefing, situational awareness, compliance checks, release governance and retained audit evidence. This document requests the technical, commercial and operational conditions for an official integration. Missing official information means that operational safety cannot be established; an empty or inaccessible feed must never be interpreted as an absence of applicable NOTAMs.

## 2. YAW overview

YAW is an unmanned aviation operations and compliance platform covering pilots, aircraft, operators, mission planning, GIS, compliance, mission release, post-flight records and aeronautical briefing. Laravel domain/application services make aviation decisions. The Inertia React web application and Flutter mobile application display the same backend assessments, source provenance and release reasons.

YAW preserves official source content separately from decoded or derived interpretation. A mission briefing is a versioned evidence snapshot. Current release checks re-evaluate source health and currentness; a previously acceptable briefing does not authorize release after a source becomes unavailable or stale.

## 3. Requested ATNS information services

All services below are requested capabilities, subject to ATNS confirmation.

- **NOTAM:** machine-readable identifiers, series/number/year, FIR, Q-code where applicable, location/aerodrome, issue timestamp, effective start/end, permanent or estimated-end state, cancellation/replacement references, raw official text, lower/upper limits and datum, coordinates/radius/geometry, and traffic/purpose/scope where available.
- **PIB:** availability of machine generation or retrieval for route, area/zone and aerodrome briefings; whether UAS polygon/radius and altitude/time requests are supported; briefing identifiers, content completeness and audit references.
- **AIXM:** availability to external consumers, supported version/profile, schema extensions, timeslice interpretation and validation resources. YAW does not assume an AIXM service exists.
- **Static aeronautical data:** FIR boundaries, controlled airspace, restricted/prohibited/danger areas, aerodromes, navigation/airspace structures, coordinates and vertical limits, including effective cycles and updates.
- **Meteorological information:** whether METAR, TAF and SIGMET are included or linked, the authoritative provider arrangement, and separate access/licensing terms. ATNS ownership or redistribution rights over all MET data are not assumed.

Public context: the [ATNS website](https://www.atns.co.za/) and [File2Fly public service overview](https://file2fly.atns.co.za/AesRepository/html/en_GB/homepage_HB.html) were reviewed on 2026-09-15. Public descriptions of briefing services do not confirm a machine interface, redistribution licence or YAW operational-use authorization. YAW does not scrape File2Fly or authenticated ATNS services. SACAA web NOTAM summaries are not treated as operational flight-preparation data.

## 4. Interface options

Please identify the supported mechanism, specification/version, onboarding contact and delivery constraints for external consumers. Candidate integrations include REST, SOAP, XML, JSON, AIXM exchange, message queues, secure file transfer, SWIM services, streaming/event interfaces or another ATNS-supported mechanism. REST is a preference only where supported, not a mandatory assumption.

Please provide service discovery details, schemas, pagination, compression, character encoding, payload limits, transport security, version/deprecation policy, delivery acknowledgement and retry behavior. Endpoint values remain unknown until supplied through the approved onboarding process.

## 5. Authentication and security

Please confirm client credentials, certificate authentication, IP allowlisting, VPN/private connectivity, mTLS and OAuth where applicable. Specify credential scope, expiry, rotation/revocation, certificate lifecycle, clock requirements, sandbox separation and production onboarding.

YAW will keep transport credentials in server-side protected configuration, never in Flutter/web clients, source records, URLs or public API/audit output. Source URLs must be credential-free canonical references. Provider errors are converted into safe health reasons; cursor metadata is encrypted at rest and excluded from client serialization. Credential delivery must use an agreed secure channel, not this requirements document.

## 6. Dataset semantics

Please state whether each service supplies complete snapshots, incremental updates or event streams, and what evidence permits YAW to conclude: **“There are no additional applicable NOTAMs.”** A zero-record response alone is insufficient.

For snapshots, define authoritative population, geographic/vertical/temporal scope, pagination completion, truncation indicators, snapshot identifier, consistency boundary and dataset timestamp. For deltas/events, define sequence/cursor and previous-cursor relationships, transaction identifiers, ordering, duplicates, cancellation/replacement events, replay windows and checkpoint acknowledgement.

Specify initial bootstrap, missed-message detection, recovery/replay, cursor expiry, resynchronization and reconciliation against a complete snapshot. Distinguish “unchanged”, “empty complete”, “partial”, “permission limited” and “unavailable”. YAW currently ingests deltas as changes but does not let them establish complete operational coverage. Any future materialized snapshot protocol will require separate evidence and conformance review.

## 7. Coverage

Please document geographic boundaries, FIR coverage, South African territorial coverage, aerodrome coverage, vertical range and altitude references, publication types and international NOTAM availability where applicable. Explain boundary inclusion, cross-border restrictions, buffer/radius handling and how missing or uncertain coverage is represented. Confirm whether a response attests coverage for the whole requested mission interval, including future effectiveness and cancellations.

## 8. Update frequency

Please provide expected publication-to-machine availability latency, source timestamp semantics, polling frequency/rate limits, push/event options, maximum response size, retry/backoff rules, service availability targets and planned maintenance behavior. YAW will agree freshness thresholds with the provider and operational stakeholders; it will not equate download time with publication freshness. No SLA or latency value is asserted here.

## 9. Availability and failure

Please describe available, unavailable, stale, partial and degraded states and any documented health/status service. Clarify authentication failures, access-scope changes, throttling, timeouts, maintenance and empty-body behavior. State escalation contacts, incident notification, recovery communication and how consumers prove synchronization is complete after an outage. YAW blocks required-source readiness when health cannot be established.

## 10. Licensing and redistribution

Written confirmation is requested for display to authenticated pilots/operators; storage of raw official records; retention and archival evidence; caching; redistribution to web/mobile clients; derived/decoded display; attribution and copyright notices; commercial licensing; user-count/device restrictions; geographic restrictions; and audit obligations. Please distinguish production, sandbox and operational-pilot terms and provide termination/deletion requirements.

Public accessibility does not establish redistribution rights. YAW's immutable evidence design must be reconciled with the agreed retention/deletion requirements before operational approval. No assumed licence, price or commercial term is proposed as fact.

## 11. Operational use

**May machine-delivered information be used by YAW for flight preparation, operational UAS briefing, compliance checks and mission release decisions?** Please confirm each use explicitly and identify approved products/service tiers and any exclusions.

Please specify required disclaimers, pilot/operator responsibilities, parallel official briefing obligations, source interpretation restrictions, qualification requirements, accountability boundaries and incident procedures. YAW's technical assessment is not itself ATNS authorization or regulatory approval.

## 12. Test/sandbox environment

Please provide sandbox availability and onboarding, sample payloads, versioned schema documentation and validation tools. Requested cases include synthetic new NOTAMs, replacement chains, cancellations, permanent/estimated dates, uncertain geometry/altitude, complete empty snapshots, partial responses, malformed records, invalid/expired credentials, rate limits, missed sequences, cursor expiry and outage/recovery.

All synthetic records will be clearly labelled and kept outside operational provider registration. Sandbox success alone will not approve production use.

## 13. Technical volume

Expected behavior includes scheduled background synchronization, on-demand generation from locally ingested data, mission-specific geospatial/time filtering, normalized local storage and historical audit retention subject to licence. Clients do not call the official feed directly. Retry and rate-limit behavior will follow the agreed service contract.

Production volume, record size, update frequency, concurrent briefing demand and retention forecasts will be supplied during technical onboarding after service scope is confirmed. No customer, aircraft, user or request-count estimates are invented here.

## 14. YAW processing model

```text
ATNS / agreed authoritative service
  | Official aeronautical data and dataset evidence
YAW provider adapter
  | Validated provider identity, approval, completeness and ordering
Raw immutable source evidence
  | Source identifier, revision, checksum, timestamps and provenance
Normalization
  | Official content remains separate from YAW interpretation
Mission relevance (spatial, vertical, temporal)
  |
Immutable briefing snapshot
  |
Current mission compliance (source health rechecked)
  |
Mission release decision and audit evidence
```

Only the Laravel domain/application layer owns authority, freshness, relevance, severity, acknowledgement and release logic. Web and Flutter consume canonical results. Replacement/cancellation changes current assessment candidates while preserving raw records and earlier briefing snapshots.

## 15. Data required from ATNS

Types below describe YAW's expected semantics, **not an ATNS wire schema or field naming claim**. Required means needed for safe YAW ingestion/readiness; conditional fields depend on the publication/service.

| Data element | Required/optional | Purpose | Expected type | Notes |
|---|---|---|---|---|
| Official provider identity and authority | Required | Trust/provenance | String + documented authority | Confirm originating and distributing authority |
| NOTAM identifier, series/number/year | Required | Stable identity | String or structured identifier | Uniqueness and reuse scope documented |
| Source revision/version | Required | Ordering and immutability | String/integer with ordering rule | No lexical ordering assumption for opaque values |
| Publication/information type | Required | Correct normalization | Enumerated code | Unknown types rejected safely |
| Issue timestamp | Required | Revision chronology | Zoned datetime | UTC conversion preserves source evidence |
| Effective start | Required or explicit unknown | Temporal relevance | Zoned datetime | Unknown is never interpreted as clear |
| Effective end / estimated end | Required or explicit permanent/unknown | Temporal relevance | Datetime + qualifier | Explain end-of-validity and cancellation |
| Permanent state | Conditional | Open-ended validity | Boolean/code | Distinguish PERM from missing date |
| Official raw text/content | Required | Source evidence | Text or original structured record | Encoding and display rights confirmed |
| Lifecycle action | Required | New/replace/cancel handling | Enumerated code | Exact provider semantics to be supplied |
| Replaced/cancelled identifier(s) | Conditional | Predecessor chain | Identifier/list | Must resolve or enter safe recovery |
| FIR code(s) | Conditional | Regional applicability | Code/list | Coverage and NOTAM scope are distinct |
| Location/aerodrome code(s) | Conditional | Location applicability | Code/list | Define non-aerodrome locations |
| Q-code | Where applicable | Structured interpretation | Code | Raw Q line retained |
| Traffic, purpose, scope | Where available | Briefing filtering | Codes/lists | Code set/version requested |
| Coordinates, radius or geometry | Required or explicit unknown | Spatial relevance | Coordinate/radius/geometry | Coordinate system, order, units and accuracy required |
| Lower/upper limit | Required or explicit unknown | Vertical relevance | Numeric/code + unit + datum | FL, AMSL and AGL cannot be interchanged |
| Activity schedule | Conditional | Time-window relevance | Structured schedule/text | Timezone and exclusions documented |
| Canonical source reference | Requested | Traceable official reference | Credential-free URI/identifier | No signed/authenticated URL secrets retained |
| Dataset timestamp | Required | Freshness and order | Zoned datetime | Source publication/consistency time, not receipt time |
| Dataset mode | Required | Full vs incremental proof | Documented enum | Snapshot, delta or stream semantics |
| Completeness/partial indicator | Required | No additional applicable records | Boolean/status + scope | Must include pagination completion |
| Geographic and vertical coverage | Required | Requested area/altitude covered | Bounds/geometry + limits | FIR/aerodrome coverage also requested |
| Temporal coverage | Required | Entire mission interval covered | Start/end datetime | Validity of the completeness claim |
| Covered information types | Required | NOTAM population covered | List | Permissions may constrain scope |
| Snapshot / transaction ID | Requested | Idempotency/reconciliation | Opaque string | Identity scope and reuse rules requested |
| Sequence / cursor / previous cursor | Conditional for delta/stream | Gap detection and replay | Integer/opaque string | Expiry/reset and resync behavior required |
| Publication revision/cycle | Conditional | Static-data currency | Identifier | AIRAC/effective cycle where applicable |
| Pagination/continuation marker | Conditional | Complete response assembly | Opaque string + terminal indicator | Adapter must collect all pages before completeness |
| Service/degradation status | Required | Fail-closed health | Status/reason code | Missing/partial distinct from no records |
| Integrity/checksum/signature | Requested | Transport/source verification | Digest/signature + algorithm | YAW additionally hashes its raw evidence |
| Licence/attribution reference | Required before operational use | Usage compliance | Document/reference | Stored approval reference, not a secret |

## 16. Questions requiring ATNS confirmation

1. Which legal/service entity should approve YAW access, licensing and operational use?
2. Which NOTAM, PIB, static/AIXM and MET products are available to external machine consumers?
3. Which interface, version, schemas and deprecation policy apply?
4. What authentication, private networking, credential rotation and production onboarding are required?
5. Can ATNS provide a sandbox and lifecycle/failure sample datasets?
6. What evidence establishes a complete population with no additional applicable NOTAMs?
7. Are responses snapshots, deltas or streams, and how are pagination and truncation signalled?
8. How do sequence/cursor IDs, duplicate delivery, ordering, replay and cursor expiry work?
9. How is a full resynchronization proven after missed messages or an outage?
10. How are replacements, cancellations, corrections and unknown predecessor references represented?
11. Which source timestamps and revision-order rules must consumers use?
12. Which geographic/FIR/aerodrome/vertical/temporal areas and publication types are covered?
13. Can PIB requests use UAS polygons/radii, routes, altitude ranges and planned time windows?
14. Which AIXM profile, geometry, altitude datum and timeslice conventions apply?
15. What latency, polling limits, push options, maintenance and availability targets apply?
16. How are stale, partial, permission-limited and degraded datasets identified and escalated?
17. May YAW store raw data and historical snapshots, and for how long under caching/deletion rules?
18. May YAW redistribute authenticated web/mobile displays and decoded interpretation, with what attribution and commercial/user/device/geographic limits?
19. Is use permitted for flight preparation, operational UAS briefing, compliance checks and mission release, and what parallel briefing/pilot responsibilities apply?
20. Who owns/authorizes any MET data and are separate agreements required?
21. What conformance, operational-pilot and production-connectivity evidence must ATNS accept before activation?
22. Who are the technical, licensing, operations and incident contacts, and how are material service/terms changes communicated?

## 17. Proposed integration phases

| Phase | Work | Exit evidence |
|---|---|---|
| A — Discovery | Technical discovery, commercial/licensing and operational-use confirmation | Agreed scope, authoritative contract, rights and named owners |
| B — Sandbox | Implement actual provider adapter against supplied interfaces | Working sandbox credentials, validated samples and mappings |
| C — Conformance | Lifecycle, ordering, completeness, failure, security and release tests | Reusable contract suite and provider-specific tests pass; recovery demonstrated |
| D — Operational pilot | Controlled supervised evaluation under agreed terms | Pilot results, responsibilities, training and operational acceptance approved |
| E — Production authorization | Production connection, runbooks, licensing and activation review | Approved evidence register and successful production connectivity; monitored rollout |

## 18. Acceptance criteria

`AtnsAimProvider = operational` requires independently reviewable evidence for all of the following:

- Official access approval and working appropriately scoped credentials, including rotation/revocation arrangements.
- Confirmed originating/distributing source authority and official product identity.
- Documented full-population, pagination, geographic/vertical/temporal and cancellation/replacement semantics.
- Verified source timestamps, freshness thresholds and latency behavior.
- New/replacement/cancellation, ordering, duplicate and recovery tests, preserving historical evidence.
- Failure, stale/partial feed and gap detection that block release until readiness is re-established.
- Satisfied licence, redistribution, retention, attribution and commercial requirements.
- Explicit operational-use authorization and documented pilot/operator responsibilities.
- Passed sandbox conformance and security tests, approved operational-pilot evidence and runbooks.
- Passed production connectivity, credential scope and current complete-data checks.

YAW's registry requires a reviewed adapter/capability declaration, explicit enabled/operational/approved server settings, and ten approval evidence references: official_access, source_authority, coverage_semantics, freshness, cancellation_replacement, failure_detection, licence_redistribution, operational_use, sandbox_conformance and production_connectivity. References identify reviewed evidence; the application cannot authenticate the legal truth of a document merely from its reference. Governance review remains mandatory. An environment flag alone is insufficient.

Every mission still requires successful current synchronization, accepted source timestamps, full required spatial/time coverage and current briefing governance. An adapter approval is not blanket mission release permission. Current ATNS status is **awaiting provider confirmation**; no official adapter, endpoint, credentials, licence or production approval has been supplied.
