# Regulatory Records, Retention & Audit

## Requirements

| Requirement | Status | Verification | Latest commit |
|---|---|---|---|
| FR-REC-001 | NOT STARTED | - | - |
| FR-REC-002 | NOT STARTED | - | - |
| FR-REC-003 | VERIFIED | docs/10-verification/phase-1/fr-rec-003-audit-trail.md | Pending commit |

## Founding Intent

FR-REC-003 requires material actions to record user, action, record, previous value, new value, timestamp and relevant device/IP metadata where appropriate. Regulatory records must use controlled archival rather than unrestricted permanent deletion.

## Implementation

### Domain

Implemented under `app/Domains/Uas/Records/Domain`:

- `Models/UasAuditEntry.php`
- `Contracts/AuditEntryRepositoryInterface.php`

### Application

Implemented under `app/Domains/Uas/Records/Application`:

- `DTOs/AuditEntryData.php`
- `Actions/RecordAuditEntry.php`

### Infrastructure

Implemented under `app/Domains/Uas/Records/Infrastructure`:

- `Repositories/EloquentAuditEntryRepository.php`

Repository binding is registered in `app/Providers/UasDomainServiceProvider.php`.

### Database

Implemented `uas_audit_entries` with actor, action, auditable record, previous/new values, requirement ID, regulatory source, IP address, user agent and occurrence timestamp.

### Current Coverage

Pilot profile create/update actions now record audit entries transactionally.

## Regulatory References

| Requirement | Source | Version / Date | Applicability | Responsible role | Evidence | Retention |
|---|---|---|---|---|---|---|
| FR-REC-003 | Founding FRS section 23 | FRS v1.0, 2026-09-09 | Material UAS regulatory record actions | Compliance Manager / System Administrator | `uas_audit_entries` rows | Audit retention period remains deferred to FR-REC-002 |

## Verification

See `docs/10-verification/phase-1/fr-rec-003-audit-trail.md`.

## Outstanding

- FR-REC-001 regulatory record metadata model beyond audit entries.
- FR-REC-002 configurable retention rules.
- Audit viewer/export UI.
- Wider audit coverage across aircraft, documents, approvals and compliance-state workflows.

## Change History

| Date | Commit | Change |
|---|---|---|
| 2026-09-09 | Pending commit | Implemented audit trail foundation and pilot profile create/update audit entries. |
