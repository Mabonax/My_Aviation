# Platform Architecture

# 35. Recommended Laravel Domain Architecture

```text
app/Domains/Uas/

Pilots/
Aircraft/
Operators/
Operations/
FlightLogs/
FlightFolios/
Maintenance/
Safety/
Security/
Compliance/
Regulations/
Documents/
Training/
GIS/
Reporting/
Notifications/
```

The architecture should use clear domain services rather than placing regulatory logic directly in controllers or UI components.

---

# 36. Core Data Model

Initial entities:

```text
users

uas_pilots
pilot_certificates
pilot_ratings
pilot_medicals
pilot_competencies
pilot_log_entries

uas_operators
operator_certificates
operator_ops_specs
operator_post_holders

uas_aircraft
aircraft_registrations
aircraft_approvals
aircraft_flight_folios
aircraft_batteries

uas_missions
uas_flights
flight_crew
flight_checklists
flight_permissions

maintenance_programmes
maintenance_events
aircraft_defects
maintenance_releases

safety_hazards
risk_assessments
occurrences
corrective_actions

security_checks
security_training

operations_manuals
operations_manual_revisions
manual_acknowledgements

regulations
regulatory_requirements
compliance_controls
compliance_evidence

regulatory_forms
regulatory_fees
regulatory_applications

training_courses
training_modules
training_lessons
training_assessments
competencies

gis_projects
gis_datasets
gis_layers
gis_features
```

---

## Aeronautical information extension (2026-09-15)

The bounded `AeronauticalInformation` module follows ADR-007 and adds provider/repository contracts, raw-source preservation, normalized revisions and immutable mission briefing snapshots. Web/API share application actions and queries; Flutter consumes server decisions. The existing MissionComplianceSummary, MissionReleaseGate, ReleaseMission, GIS utilities and audit infrastructure remain the integration points.

See [ADR-008](../09-decisions/ADR-008-aeronautical-source-and-briefing-evidence.md) and the [API, web route and import contract](aeronautical-information-api.md). Source authority, complete coverage, current source timestamps and the latest reviewed snapshot are required for release. Mission list compliance now evaluates live state rather than relying on stored release-gate summaries.

## Provider operational conformance (2026-09-16)

[ADR-009](../09-decisions/ADR-009-provider-operational-conformance.md) extends the existing provider boundary with capabilities, reviewed approval references, explicit snapshot/delta semantics, encrypted cursors, ordering protection and safe source-health reasons. No aviation decisions move into either client. [ATNS engagement requirements](../11-external-integrations/atns-aim-integration-requirements.md) define the external evidence needed before operational activation.
