# Phase 1 — Pilot & Fleet Compliance MVP Status

## Objective

Establish the regulatory master records and first usable VMT UAS compliance product.

## Traceability Matrix

| Requirement | Feature | Status | Verification | Notes |
|---|---|---|---|---|
| FR-PIL-001 | Pilot profile | NOT STARTED | — | |
| FR-PIL-002 | RPC status | NOT STARTED | — | |
| FR-PIL-003 | RPC revalidation window | NOT STARTED | — | |
| FR-PIL-004 | Post-revalidation submission deadline | NOT STARTED | — | |
| FR-LOG-001 | Pilot flight entry | NOT STARTED | — | |
| FR-LOG-002 | Pilot experience calculations | NOT STARTED | — | |
| FR-LOG-003 | Pilot logbook summary | NOT STARTED | — | |
| FR-FOL-001 | Aircraft flight folio | NOT STARTED | — | |
| FR-FOL-002 | Flight record folio update | NOT STARTED | — | |
| FR-FOL-003 | Charging/fuel/oil records | NOT STARTED | — | |
| FR-FOL-004 | Maintenance certification entries | NOT STARTED | — | |
| FR-FOL-005 | Remote/offline folio availability | NOT STARTED | — | |
| FR-AIR-001 | Aircraft record | NOT STARTED | — | |
| FR-AIR-002 | Aircraft registration lifecycle | NOT STARTED | — | |
| FR-AIR-003 | Aircraft serviceability states | NOT STARTED | — | |
| FR-LA-001 | UASLA record | NOT STARTED | — | |
| FR-LA-002 | UASLA renewal workflow | NOT STARTED | — | |
| FR-REC-001 | Regulatory record metadata | NOT STARTED | — | |
| FR-REC-002 | Regulatory retention controls | NOT STARTED | — | |
| FR-REC-003 | Audit trail | NOT STARTED | — | |
| FR-REG-001 | Regulatory knowledge engine | NOT STARTED | — | |
| FR-NOT-001 | Expiry/compliance notifications | NOT STARTED | — | Derived from FRS notification engine |
| FR-DOC-001 | Compliance document management | NOT STARTED | — | Supporting Phase 1 capability |
| FR-CMP-001 | Compliance dashboard | NOT STARTED | — | Supporting Phase 1 capability |

## Phase gate

Phase 1 is complete only when the foundational pilot, aircraft, certificate, approval, document, recordkeeping, notification and compliance functions required by the MVP are `VERIFIED`.

## Current baseline

Repository initialised on `main`.

Baseline commit:
- `ca29ccb` — `Initial Laravel UAS platform baseline`

Baseline verification:
- `php artisan test`: 27 passed, 64 assertions
- `npm.cmd run build`: passed
