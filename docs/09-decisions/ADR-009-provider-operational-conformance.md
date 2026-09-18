# ADR-009: Evidence-backed provider readiness

- Date: 2026-09-16
- Status: Accepted for repository implementation; external operational approval pending
- Requirement: FR-AIM-009
- Extends: ADR-007 and ADR-008

## Context

Source classification alone cannot authorize an arbitrary adapter to establish operational coverage. A successful download may be partial, incremental, stale or incorrectly ordered. Operational approval also depends on confirmed access, licensing and use rights beyond source code.

## Decision

Keep authority, normalization, relevance, source health, briefing and release decisions in Laravel. Provider adapters declare a typed capability contract. ProviderRegistry checks registered identity/class, enabled state, operational and approved server settings, required capabilities and ten nonempty approval evidence references. A capability/approval digest ties each successful sync to the reviewed configuration. Revoking or changing approval invalidates readiness and the briefing policy fingerprint.

ProviderDataset distinguishes unknown, full_snapshot and incremental. A full snapshot is a provider assertion of all records for its declared coverage. Unknown/delta inputs cannot establish completeness, even if they claim complete=true. This slice intentionally requires a later valid full snapshot after incremental synchronization; it does not certify a materialized-delta coverage algorithm. Omitting a previous active record from a later snapshot does not implicitly cancel it: explicit lifecycle evidence is required, conservatively retaining unresolved restrictions.

Sync validates timestamps, record types, metadata and credential-free evidence before committing atomically. Generic metadata supports dataset_sequence, provider_cursor, previous_cursor, source_transaction_id, snapshot_id and publication_revision. Sync metadata is encrypted and hidden; hashes provide idempotency without publishing cursors. Same-content repeats reuse the dataset revision; changed content under reused identity or regressing order is rejected. A global dataset row lock serializes commits. A delayed older sync cannot displace a later outcome.

Raw sources and briefing snapshots remain immutable. Current normalized candidates may be superseded; predecessor references must resolve. Cancellation removes the predecessor from current assessment without deleting historical evidence. Older numeric revisions or conflicting payloads cannot overwrite current state.

Preserve V1 freshness status (fresh/stale/unavailable) and add health_status and safe reason. Health distinguishes healthy, stale, unavailable, sync_failed, coverage_insufficient, authority_insufficient and unconfigured. Every release request uses current health and mission-specific coverage, not a historical green badge. Both clients render these results.

## Security and observability

Credentials remain server-side. Reject credential-like keys, bearer/private-key markers and URLs with userinfo/query/fragment before raw persistence. Transport adapters must keep credentials separate from record content; heuristic scanning cannot recognize every arbitrary secret embedded in prose. Provider exceptions are replaced with safe error codes/messages at persistence and API boundaries. Approval references and encrypted sync metadata are never serialized to clients/audit.

Existing UasAuditEntry records sync started/completed/failed, provider unavailable, dataset rejected/stale, coverage insufficient and briefing health blocking, with identifiers/counts/digests rather than credentials. Pilot/operator routes cannot edit provider configuration; imports retain existing permission gates.

## Consequences and acceptance limits

Fail-closed behavior may temporarily block after a valid delta until a complete snapshot is accepted. Approval references require human governance review; their existence is not proof of legal validity. Production activation needs the ATNS evidence checklist, not just flags. No fake ATNS adapter or production bypass is introduced.

Provider health uses two bounded latest-outcome/latest-success queries across all configured providers. Register source relations are eager loaded and results paginated. Briefing candidates eager load source; history uses summary columns. The justified provider/status/id index supports latest outcome lookup. Existing identity/checksum/type/status/effective-date indexes remain in use. No per-item source N+1 is introduced; aggregate mission-list compliance remains per mission and needs production-volume profiling before scaling claims.

See [verification](../10-verification/remediation/fr-aim-009-operational-acceptance.md) and [ATNS requirements](../11-external-integrations/atns-aim-integration-requirements.md).
