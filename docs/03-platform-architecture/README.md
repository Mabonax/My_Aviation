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
