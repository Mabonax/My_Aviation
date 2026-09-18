# ADR-008 - Aeronautical source authority and immutable briefing evidence

**Status:** ACCEPTED  
**Date:** 2026-09-15

## Context

Mission release needs current aeronautical information without treating public summaries, missing data or a client's interpretation as operational clearance. Later review must recover exactly what the pilot/operator was shown. Existing UAS architecture already owns compliance, release, mission geometry, policies and audit.

## Decision

Add `AeronauticalInformation` as a bounded domain with Domain, Application, Infrastructure and Http layers. Provider contracts and canonical DTOs isolate transport-specific ingestion. Eloquent persistence is behind a repository contract. Shared application actions/queries serve both Inertia and API V1; Flutter never computes relevance or release status.

Keep three separate objects: immutable source record, versioned normalized interpretation, and immutable mission-specific assessment snapshot. Source checksum hashes the retained incoming record; identity additionally includes provider/identifier/revision/parser version. Briefing dataset hash is a SHA-256 digest of the serialized dataset revision, not a digest of downloaded publication bytes. Individual source checksums and full item snapshots retain content evidence.

Authority comes from trusted provider code and an allowed classification. Manual, publication-reference and fixture adapters are reference only. Operational providers must supply fresh source timestamps and complete NOTAM coverage spanning the mission geometry, buffer and planned interval. A successfully synchronized empty, complete dataset differs from unavailable data. Configuration cannot eliminate the requirement by using an empty provider list.

Use a locked dataset-state row to serialize sync completions, briefing generation, acknowledgement and release. These operations acquire dataset state before the mission lock when both are needed. Sync fetch occurs outside the transaction, while a completion revision orders committed outcomes. Failed syncs invalidate existing briefings. Older datasets cannot replace newer provider state. Re-importing identical items is idempotent; a new successful sync still advances the dataset revision and requires a fresh briefing.

Briefing headers/items and acknowledgements are append-only through application/model paths. Regeneration creates a revision; acknowledgement never edits the snapshot. Release stores the briefing ID/revision and acknowledgement actor/time in existing release/audit evidence. No post-release regeneration is permitted by this initial lifecycle integration. Privileged direct database writes are outside this model-level immutability guarantee.

MissionComplianceSummary gains one control, consumed by existing MissionReleaseGate and ReleaseMission. Mission list summaries now evaluate live controls so an earlier stored green result cannot hide source expiry; future performance optimization must retain freshness correctness.

## Consequences

- Default `atns_aim` is unavailable until an official integration is configured; reference imports alone cannot release a mission.
- Unknown NOTAM/SIGMET applicability from an operational source blocks release. Acknowledgement resolves review requirements only, never a hard blocker.
- The regional geometry evaluator reuses existing mission normalization/polygon utilities. It supports point, radius, route and simple polygon intersection. Holes, multipolygons, dateline/polar cases and combined extents beyond 500 km remain uncertain; no unsupported geometry is treated as clearance.
- Internal policy buffers and lifetime are versioned through a fingerprint, without claiming to implement a statutory separation minimum.
- Future ATNS/AIXM/SWIM/MET integrations must establish licensing, schema, authentication, coverage, cancellation, time/vertical semantics and failure behavior before claiming operational usability. Credentials/endpoints are not invented.
- Background jobs, polling and webhooks can call the same ingestion action; no external scheduler or queue worker is installed for a nonexistent feed.

## Verification

See [FR-AIM-001 through FR-AIM-008 evidence](../10-verification/remediation/aeronautical-information-integration.md). Operational acceptance remains open pending an official feed and authenticated browser/device review.
