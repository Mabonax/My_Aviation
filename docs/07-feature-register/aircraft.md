# Aircraft & Approvals

## Requirements

| Requirement | Status | Verification | Latest commit |
|---|---|---|---|
| FR-AIR-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-AIR-001-PKG | VERIFIED FOUNDATION | docs/10-verification/remediation/aircraft-package-instantiation.md | Catalogue-linked aircraft now instantiate package batteries/components and baseline maintenance guidance idempotently. |
| FR-AIR-002 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-AIR-003 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-LA-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-LA-002 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |

## Implementation

- Aircraft master records: `uas_aircraft`, `UasAircraft`.
- Catalogue model records: `uas_manufacturers`, `uas_aircraft_models`.
- Registration lifecycle records: `aircraft_registrations`, `AircraftRegistration`.
- UASLA/RLA approval records: `aircraft_approvals`, `AircraftApproval`.
- Serviceability control: `AircraftServiceabilityEvaluator` blocks grounded, unserviceable, suspended, de-registered, sold and flight-restricted aircraft from released-flight assignment.
- Compliance readiness summary: `AircraftReadinessSummary` derives green/amber/red readiness from catalogue linkage, serviceability, registration, approval, defects and battery controls.

## Outstanding

- Aircraft update/edit workflow.
- Renewal case-pack workflow.
- Maintenance programme and component life-limit integration.
- Package instantiation from catalogue-linked aircraft.
- Historical readiness snapshots.
- Authenticated browser verification.
