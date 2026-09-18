# Phase 2 - Flight Operations Status

## Overall status

`IN PROGRESS`

## Requirements

| Requirement | Feature | Status | Verification | Notes |
|---|---|---|---|---|
| FR-MIS-001 | Mission record | VERIFIED | docs/10-verification/remediation/mission-compliance-release-readiness.md | Mission planning now includes operator-scoped web/API payloads and compliance summary output. |
| FR-MIS-002 | Mission lifecycle/release readiness and post-flight propagation | VERIFIED | docs/10-verification/remediation/post-flight-propagation.md | Mission release consumes shared compliance summary; completed missions now propagate idempotently into pilot logbook and aircraft folio records with battery/checklist/track/defect evidence. |
| FR-OPS-001 | Pre-flight compliance gate | IMPLEMENTED | docs/10-verification/phase-2/fr-mis-001-002-mission-planning.md | Initial release gate evaluates pilot, RPC, medical, aircraft serviceability, registration, UASLA and risk evidence. |
| FR-GEO-001 | Mission map | IMPLEMENTED | docs/10-verification/phase-2/fr-geo-001-mission-map.md | Dependency-free mission geometry editor/preview captures location search, coordinates, take-off, landing, polygon, route and radius. |
| FR-GEO-002 | Aviation overlays | IMPLEMENTED | docs/10-verification/phase-2/fr-geo-002-aviation-overlays.md | Source-aware overlay registry, provenance-aware UI and placeholder geometries implemented pending authoritative dataset import. |
| FR-GEO-003 | Geometry rule evaluation | IMPLEMENTED | docs/10-verification/phase-2/fr-geo-003-geometry-rule-evaluation.md | Mission geometry is evaluated against configured aviation overlays and returns review/authorisation flags with source provenance. |
| FR-CHK-001 | Pre-flight checklist | IMPLEMENTED | docs/10-verification/phase-2/fr-chk-001-pre-flight-checklist.md | Versioned pre-flight template, mission checklist execution records, performer/timestamp/results/exceptions and audit evidence implemented. |
| FR-CHK-002 | Post-flight checklist | VERIFIED | docs/10-verification/remediation/post-flight-propagation.md | Versioned post-flight checklist now gates mission close-out propagation and carries exceptions into follow-up evidence. |
| FR-CREW-001 | Crew management | IMPLEMENTED | docs/10-verification/phase-2/fr-crew-001-crew-management.md | Dedicated mission crew assignments with role, briefing, competency, acceptance, emergency contact and audit evidence implemented. |
| FR-TRK-001 | Flight tracks | IMPLEMENTED | docs/10-verification/phase-2/fr-trk-001-flight-tracks.md | Mission-bound flight track capture with source, points, distance, max altitude, anomalies and audit evidence implemented. |
| FR-BAT-001 | Battery management | VERIFIED | docs/10-verification/remediation/post-flight-propagation.md | Battery inventory, cycle, health, charge-history, retirement and mission usage workflow implemented; post-flight propagation summarises mission usage into aircraft folios without double-counting cycles. |
| FR-DEF-001 | Defects | IMPLEMENTED | docs/10-verification/phase-2/fr-def-001-defect-management.md | Defect source, severity, serviceability impact, audit evidence and mission/top-level reporting workflow implemented. |

## Phase gate

This phase may only be marked complete when all in-scope requirements are either `VERIFIED` or explicitly accepted as deferred with a documented decision.

## Aeronautical release-control extension - 2026-09-15

FR-AIM-006 adds the eighth shared mission compliance control. Release requires a current briefing and fresh, authoritative, complete required-source coverage. Acknowledgement cannot override a hard blocker. The release audit stores the briefing revision and acknowledgement evidence. Earlier verification records remain historical; the [current extension report](../10-verification/remediation/aeronautical-information-integration.md) documents conservative default blocking while the official source is unavailable.

## FR-AIM-009 — 2026-09-16

**IMPLEMENTED.** Baseline remediation and provider readiness hardening are recorded in the [operational acceptance evidence](../10-verification/remediation/fr-aim-009-operational-acceptance.md). Source health continues to block release for the unconfigured required `atns_aim` provider. Automated, HTTP, visual browser, emulator and external-provider evidence are separate; no production or official-source verification is claimed. ATNS engagement can proceed using the specification and capability template.
