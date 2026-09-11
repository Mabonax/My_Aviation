# Aircraft & Approvals

## Requirements

| Requirement | Status | Verification | Latest commit |
|---|---|---|---|
| FR-AIR-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-AIR-002 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-AIR-003 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-LA-001 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |
| FR-LA-002 | VERIFIED | docs/10-verification/phase-1/phase-1-mvp-completion.md | Pending commit |

## Implementation

- Aircraft master records: `uas_aircraft`, `UasAircraft`.
- Registration lifecycle records: `aircraft_registrations`, `AircraftRegistration`.
- UASLA/RLA approval records: `aircraft_approvals`, `AircraftApproval`.
- Serviceability control: `AircraftServiceabilityEvaluator` blocks grounded, unserviceable, suspended, de-registered, sold and flight-restricted aircraft from released-flight assignment.

## Outstanding

- Full aircraft CRUD UI.
- Renewal case-pack workflow.
- Maintenance and defect integration.
- Authenticated browser verification.
