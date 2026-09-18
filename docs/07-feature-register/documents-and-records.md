# Documents & Records

## Requirements

| Requirement | Status | Verification | Latest commit |
|---|---|---|---|
| FR-DOC-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-DOC-001-EVD | VERIFIED FOUNDATION | docs/10-verification/remediation/evidence-document-architecture.md | Governed evidence vault with private uploads, metadata, operator scope and polymorphic links. |
| FR-REC-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-REC-002 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-REC-003 | VERIFIED | docs/10-verification/phase-1/fr-rec-003-audit-trail.md | Pending commit |

## Implementation

- Regulatory document metadata/control: `regulatory_documents`, `RegulatoryDocument`.
- Governed evidence vault: `uas_evidence_documents`, `uas_evidence_links`, `EvidenceDocument`, `EvidenceLink`.
- Retention rules: `record_retention_rules`, `RecordRetentionRule`.
- Audit trail foundation: `uas_audit_entries`, `UasAuditEntry`.

## Outstanding

- Backfill legacy JSON `evidence_references` into governed evidence links.
- Download/preview endpoints and immutable superseded-document version chains.
- Flutter/offline evidence upload.
- Audit review/export UI.
- Retention lock/archive workflows.
