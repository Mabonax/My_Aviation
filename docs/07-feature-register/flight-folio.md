# Aircraft Flight Folio

## Requirements

| Requirement | Status | Verification | Latest commit |
|---|---|---|---|
| FR-FOL-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-FOL-002 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-FOL-003 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-FOL-004 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-FOL-005 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |

## Implementation

- Aircraft flight folio entries: `aircraft_flight_folios`, `AircraftFlightFolio`.
- Flight record updates, charging/fuel/oil JSON records, maintenance certification JSON records and offline availability flag are represented.

## Architectural Note

Aircraft folio records remain separate from pilot logbook records per ADR-004.

## Outstanding

- Folio CRUD UI.
- Mobile/offline synchronization proof.
- Maintenance release integration.
