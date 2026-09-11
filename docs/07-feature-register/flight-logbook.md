# Pilot Flight Logbook

## Requirements

| Requirement | Status | Verification | Latest commit |
|---|---|---|---|
| FR-LOG-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-LOG-002 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-LOG-003 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |

## Implementation

- Pilot log entries: `pilot_log_entries`, `PilotLogEntry`.
- Experience totals and period summaries: `PilotLogbookSummary`.

## Architectural Note

Pilot logbook records remain separate from aircraft flight folios per ADR-004.

## Outstanding

- Logbook CRUD UI.
- PDF/CSV exports and regulator/application-support formats.
- Authenticated browser verification.
