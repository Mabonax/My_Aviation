# UAS Compliance & Operations Platform
## Functional Requirements Specification

**Product Owner:** Various Media Technologies (Pty) Ltd (VMT)  
**Version:** 1.0  
**Date:** 9 September 2026  
**Jurisdiction:** Republic of South Africa  
**Primary Regulatory Authority:** South African Civil Aviation Authority (SACAA)  
**Primary Regulatory Framework:** Civil Aviation Regulations Parts 47, 71, 101 and 187, together with applicable SA-CATS, technical guidance material, forms and directives  
**Proposed Technology:** Laravel + React/Inertia + TypeScript + relational database + GIS/mapping services

---

## 1. Purpose

The UAS Compliance & Operations Platform is an independent VMT product intended to provide a digital operating environment for remote pilots, UAS operators, training organisations and aviation compliance personnel.

The platform shall support:

- remote pilot certification and competency records;
- digital pilot logbooks;
- aircraft flight folios;
- UAS fleet and registration management;
- UAS Letter of Approval/Authorisation lifecycle management;
- UAS Operator Certificate governance;
- mission planning and operational approvals;
- pre-flight and post-flight processes;
- maintenance, defects and aircraft serviceability;
- safety, occurrence and corrective-action management;
- security compliance;
- regulatory document control and retention;
- regulatory fee management;
- application and renewal preparation;
- training and competency management;
- GIS and mapping missions;
- compliance dashboards, alerts and reporting.

The platform does **not** issue SACAA licences, certificates or regulatory approvals and must not represent itself as SACAA. SACAA remains the regulatory authority and official system of record for approvals issued by the Authority.

---

## 2. Product Boundary

This is a standalone **Various Media Technologies (VMT)** product and solution.

It has no affiliation with, dependency on, or architectural relationship to the AB4IR ERP or AB4IR LMS.

Any training functionality required by this product shall be developed as a native VMT capability or integrated with appropriately authorised external training providers where required.

---

## 3. Regulatory Model

The platform shall model regulatory obligations according to their authoritative source rather than treating all UAS requirements as Part 101 requirements.

### 3.1 Part 71 — Remote Pilot Certification

The Part 71 domain shall manage requirements associated with remote pilot certification, including:

- RPC records;
- applicable ratings;
- medical requirements;
- radiotelephony qualifications;
- theoretical examinations;
- skills tests;
- revalidation;
- pilot competency;
- pilot flight-time records.

### 3.2 Part 101 — UAS Operations

The Part 101 domain shall support requirements including:

- UAS operational approval;
- UASLA/RLA lifecycle;
- system safety;
- UASOC/ROC governance;
- Operations Manuals;
- operational specifications;
- organisational records;
- security;
- flight operations;
- aircraft flight folios;
- maintenance;
- defects;
- operational safety.

### 3.3 Part 47 — Aircraft Registration

The platform shall support the applicable UAS registration lifecycle, including registration, ownership changes, amendments, cancellation and supporting evidence.

### 3.4 Part 187 — Regulatory Fees

The platform shall maintain version-controlled regulatory fees with effective dates and source references.

---

## 4. Core Design Principle

The regulatory compliance architecture shall follow:

```text
REGULATION
    ↓
REQUIREMENT
    ↓
RESPONSIBLE PARTY
    ↓
SYSTEM CONTROL
    ↓
EVIDENCE
    ↓
COMPLIANCE STATUS
    ↓
ALERT / ACTION
    ↓
APPLICATION / AUDIT / RENEWAL
```

No regulatory requirement shall be hard-coded without storing its regulatory source, applicability, effective date and version.

---

## 5. User Roles

The platform shall initially support:

| Role | Primary Responsibility |
|---|---|
| Remote Pilot | Licence, logbook, missions and personal compliance |
| Pilot Instructor | Training and competency |
| Examiner | Skills and revalidation records |
| Flight Operations Officer | Mission preparation and operational control |
| Responsible Person: Flight Operations | Flight operations governance |
| Responsible Person: Aircraft | Fleet and aircraft oversight |
| Maintenance Technician | Maintenance and defect rectification |
| Safety Manager | Hazards, occurrences and corrective actions |
| Security Coordinator | Security compliance |
| Compliance Manager | Regulatory compliance |
| Accountable Manager | Organisational oversight |
| Training Administrator | Training administration |
| UASOC Administrator | Operator certification administration |
| System Administrator | Platform administration |

Roles and responsibilities shall be configurable.

---

# 6. Pilot Management

## FR-PIL-001 — Pilot Profile

Maintain a unique pilot record containing:

- personal details;
- SACAA certificate/licence number;
- RPC category;
- ratings;
- issue and expiry dates;
- medical status;
- radiotelephony qualification;
- language proficiency where applicable;
- training history;
- examiner records;
- operator affiliations;
- supporting documents.

## FR-PIL-002 — RPC Compliance State

The system shall calculate states including:

- Valid;
- Expiring;
- Revalidation Due;
- Expired;
- Suspended;
- Unknown/Unverified.

Compliance shall be evidence-driven rather than controlled by a simple manual compliant/non-compliant checkbox.

## FR-PIL-003 — Revalidation Window

The system shall calculate applicable revalidation windows and create configurable alerts before expiry.

Suggested alert points:

- 120 days;
- 90 days;
- 60 days;
- 30 days;
- 14 days;
- expiry date.

## FR-PIL-004 — Submission Deadlines

Where regulations create a post-test or post-revalidation submission period, the system shall calculate and monitor the applicable deadline.

---

# 7. Digital Pilot Logbook

The pilot logbook shall be a separate regulatory/experience record from the aircraft flight folio.

## FR-LOG-001 — Flight Entry

Each flight entry should support:

```text
Date
Pilot
Aircraft
Registration
Operator
Mission
Location
Coordinates
Operation type
Rating/privilege utilised
VLOS / EVLOS / BVLOS
Day / Night
Take-off time
Landing time
Total duration
PIC time
Instruction/dual time
Observer/crew
Maximum altitude
Maximum distance
Training or operational purpose
Remarks
Occurrence reference
```

## FR-LOG-002 — Flight Experience Calculations

Calculate flight time by:

- career;
- calendar year;
- previous 12 months;
- previous 90 days;
- previous 30 days;
- aircraft category/type;
- day/night;
- VLOS/EVLOS/BVLOS;
- training;
- commercial/operational category where applicable.

## FR-LOG-003 — Logbook Reports

Generate pilot logbook summaries for configurable periods in PDF/CSV and regulator/application-support formats.

---

# 8. Aircraft Flight Folio

## FR-FOL-001 — Aircraft Folio

Every applicable aircraft shall maintain its own flight folio or compliant equivalent record.

## FR-FOL-002 — Automatic Flight Entries

Completion of a flight shall create/update the corresponding aircraft folio entry.

## FR-FOL-003 — Energy/Fuel Records

Where applicable, record:

- battery usage/charging;
- fuel;
- oil;
- relevant consumption information.

## FR-FOL-004 — Maintenance Certification

Maintenance entries shall require certification by an authorised maintenance user before completion.

## FR-FOL-005 — Operational Accessibility

The platform should support mobile and ultimately offline/cached access to current aircraft folio information when required operationally.

---

# 9. Aircraft Registry

## FR-AIR-001 — Aircraft Master Record

Store:

```text
Registration
Manufacturer
Model
Serial number
Aircraft category
Owner
Operator
Acquisition date
Operational status
Base/location
Certificate of Registration
UASLA/RLA
System Safety approval
Maintenance programme
Insurance
Radio licence
Applicable Operations Manual
```

## FR-AIR-002 — Registration Lifecycle

Support:

- initial registration;
- change of ownership;
- amendment;
- cancellation/de-registration;
- duplicate certificate records.

## FR-AIR-003 — Aircraft Operational State

Supported states should include:

```text
Pending Registration
Active / Serviceable
Maintenance Due
Flight Restricted
Grounded
Unserviceable
Suspended
Sold
De-registered
Archived
```

Grounded or unserviceable aircraft shall not be assignable to an operationally released flight.

---

# 10. UASLA / RLA Management

## FR-LA-001 — Approval Record

Capture:

- approval number;
- aircraft;
- issue date;
- expiry date;
- scope;
- restrictions;
- approval document;
- status.

## FR-LA-002 — Renewal Workflow

The system shall initiate renewal preparation according to configurable regulatory timelines.

A renewal case may include:

- current approval;
- Certificate of Registration;
- flight-folio extracts;
- maintenance summary;
- insurance;
- radio documentation;
- mass and balance;
- maintenance evidence;
- applicable operator evidence;
- regulatory fee.

---

# 11. UAS Operator Management

## FR-OPS-001 — Operator Profile

Store:

```text
Legal entity
Trading name
Registration number
UASOC/ROC number
Issue date
Expiry
Status
Accountable Manager
Responsible Person: Flight Operations
Responsible Person: Aircraft
Safety Manager
Security Coordinator
Operating bases
Approved aircraft
Approved pilots
Operations Specifications
```

## FR-OPS-002 — Certificate Lifecycle

Maintain application, amendment and renewal cases containing:

- deadlines;
- evidence requirements;
- outstanding documents;
- fleet;
- personnel;
- OpsSpec;
- Operations Manual revision;
- fees;
- submission status;
- authority correspondence;
- outcome.

---

# 12. Operations Manual Control

## FR-OM-001 — Controlled Manual

Operations Manuals shall be version controlled.

Store:

```text
Manual
Revision
Effective date
Approval status
Authority approval reference
Sections
Change summary
Superseded revision
```

## FR-OM-002 — Distribution

Record all personnel required to receive each revision.

## FR-OM-003 — Acknowledgement

Affected personnel shall be able to acknowledge receipt/readership of relevant revisions.

## FR-OM-004 — Training Trigger

An amendment may generate mandatory training and competency requirements.

---

# 13. Mission & Flight Operations

## FR-MIS-001 — Mission Record

Store:

```text
Mission purpose
Client/project
Location
Coordinates
Mission polygon
Operation category
Aircraft
Pilot
Observers/crew
Date/time
Maximum altitude
Planned distance
VLOS/EVLOS/BVLOS
Day/night
Weather
Airspace assessment
Approvals
Risk assessment
Emergency arrangements
```

## FR-MIS-002 — Mission Lifecycle

Suggested lifecycle:

```text
Draft
Planning
Compliance Review
Awaiting Approval
Approved
Ready for Flight
In Progress
Completed
Post-flight Review
Closed
Cancelled
```

---

# 14. Pre-Flight Compliance Gate

Before operational release, evaluate applicable requirements such as:

```text
Pilot RPC
Required ratings/privileges
Medical requirement
Aircraft serviceability
Certificate of Registration
UASLA
UASOC
OpsSpec
Operations Manual
Aircraft/RPS manuals
Maintenance status
Insurance
Security status
Mission approvals
Risk assessment
Airspace requirements
```

Results:

```text
GREEN — Ready for operational approval
AMBER — Attention/review required
RED — Flight must not be released under configured rules
```

The system must distinguish a **regulatory prohibition** from an **internal organisational policy control**.

---

# 15. GIS & Airspace Mapping

## FR-GEO-001 — Mission Map

Support:

- mission polygon;
- take-off point;
- landing point;
- flight route;
- flight radius;
- location search;
- coordinates.

## FR-GEO-002 — Aviation Overlays

Where authoritative data is available, support:

- aerodromes;
- controlled airspace;
- restricted airspace;
- prohibited airspace;
- strategic areas;
- approved operating zones.

Google Maps or similar services may provide basemap and geolocation functionality but shall not automatically be treated as an authoritative aviation airspace source.

## FR-GEO-003 — Rule Evaluation

The compliance engine shall evaluate mission geometry against configured regulatory spatial rules and flag conditions requiring review or authorisation.

---

# 16. Pre-Flight and Post-Flight Checklists

Checklists shall be configurable and versioned.

Possible items:

```text
Aircraft condition
Propellers
Motors
Battery
RPS/controller
GNSS
C2 link
Firmware/configuration
Payload
Weather
Site security
Emergency landing area
Crew briefing
Third-party/public exposure
Permissions
```

Store performer, timestamp, checklist version, results and exceptions.

---

# 17. Defect Management

## FR-DEF-001 — Defect Sources

Defects may originate from:

- pre-flight;
- in-flight;
- post-flight;
- maintenance;
- inspection.

## FR-DEF-002 — Severity

Suggested levels:

```text
Observation
Minor
Maintenance Required
Flight Restricted
Ground Aircraft
```

## FR-DEF-003 — Serviceability Control

A grounding defect shall automatically make the aircraft unavailable until an authorised return-to-service action is completed.

---

# 18. Maintenance

## FR-MNT-001 — Maintenance Programme

Schedule maintenance based on:

- date;
- flight hours;
- cycles;
- battery cycles;
- manufacturer requirements;
- approved operator programme;
- defects;
- inspections.

## FR-MNT-002 — Due Calculations

Automatically calculate maintenance remaining/due/overdue status.

## FR-MNT-003 — Maintenance Release

Completion shall record:

```text
Work performed
Technician
Parts/components
Date
Evidence
Certification
Return-to-service state
```

---

# 19. Battery Management

For electric UAS maintain:

```text
Battery ID
Manufacturer
Serial number
Compatible aircraft
Cycles
Charge history
Health
Acquisition date
Last used
Damage/incidents
Retirement status
```

Each applicable flight shall reference the battery/batteries used.

---

# 20. Safety Management

## FR-SAF-001 — Hazard Register

Store:

```text
Hazard
Source
Operation
Likelihood
Severity
Initial risk
Mitigation
Residual risk
Owner
Review date
```

## FR-SAF-002 — Operational Risk Assessment

Missions may require a structured operational risk assessment.

## FR-SAF-003 — Corrective Actions

Hazards, audits, incidents and findings may create corrective actions containing:

- owner;
- priority;
- due date;
- evidence;
- verification;
- closure.

---

# 21. Occurrences & Incidents

## FR-OCC-001 — Occurrence Record

Capture:

```text
Flight
Aircraft
Pilot
Date/time
Location
Classification
Description
Injury
Property damage
Aircraft damage
Loss of control
Immediate action
Evidence
Reportability assessment
External report reference
Investigation
Corrective action
Closure
```

The platform shall assist with reportability assessment but must not make an unsupported assertion that an occurrence has been formally reported to SACAA.

---

# 22. Security Management

## FR-SEC-001 — Personnel Security

Maintain applicable:

- background checks;
- criminal-record checks;
- check dates;
- next due dates;
- security-awareness training;
- authorised access.

## FR-SEC-002 — Recurring Reviews

Automatically calculate recurring security review deadlines based on the applicable regulatory requirement/version.

## FR-SEC-003 — Secure Asset Storage

Aircraft may be assigned to:

```text
Facility
Secure room
Storage location
Custodian
Authorised access list
```

---

# 23. Document & Record Retention

## FR-REC-001 — Regulatory Records

Store:

```text
Created date
Record owner
Category
Regulatory source
Version
Status
Retention start
Retention end
Locked status
Archive status
```

## FR-REC-002 — Retention Rules

Regulatory retention periods shall be configurable and tied to the applicable regulation/version.

The Part 101 operator recordkeeping requirement currently identified during regulatory analysis shall default to at least five years where applicable.

## FR-REC-003 — Audit Trail

Material actions shall record:

```text
User
Action
Record
Previous value
New value
Timestamp
Relevant device/IP metadata where appropriate
```

Regulatory records shall use controlled archival rather than unrestricted permanent deletion.

---

# 24. Training & Competency

The platform shall distinguish between:

1. regulatory/ATO training;
2. operator/internal competency training;
3. general educational learning.

The system must not represent ordinary educational content as SACAA-approved training unless the relevant authorisation exists.

## FR-TRN-001 — Learning Structure

```text
Course
Module
Lesson
Resource
Quiz
Practical
Assessment
Competency
Certificate/Record
```

Possible learning areas:

- Aviation Law;
- Part 71;
- Part 101;
- Human Factors;
- Meteorology;
- Navigation;
- UAS Systems;
- Flight Planning;
- Radio;
- Emergency Procedures;
- Airspace;
- Safety;
- Security;
- GIS;
- Photogrammetry.

## FR-TRN-002 — Compliance Link

A regulatory or organisational requirement may reference a required competency and training record.

---

# 25. Regulatory Knowledge Engine

## FR-REG-001 — Structured Regulation

Each regulatory requirement shall support:

```text
Regulation
Part
Subpart
Clause
Title
Requirement
Responsible party
Applicability
System control
Evidence required
Frequency
Validity period
Retention period
Effective date
Superseded date
Official source
Source version
Status
```

## FR-REG-002 — Version Awareness

Changes to regulations shall create new versions rather than silently modifying historical regulatory rules.

## FR-REG-003 — Traceability

Every automated compliance control should be traceable to its regulatory or organisational source.

---

# 26. Compliance Register

Calculate compliance at:

- organisation level;
- pilot level;
- aircraft level;
- operation level;
- maintenance level;
- security level;
- training level;
- safety level.

Example dashboard:

```text
ORGANISATION COMPLIANCE       92%

Pilots                         96%
Aircraft                       90%
Operations                     94%
Maintenance                    87%
Safety                        100%
Security                       83%

Critical findings               2
Expiring within 30 days          5
Open corrective actions          8
```

Compliance scores must never obscure critical regulatory failures. A critical red control should remain visible regardless of aggregate score.

---

# 27. SACAA Form Register

Maintain a version-controlled form catalogue containing fields such as:

```text
Form code
Form title
Regulatory area
Revision
Effective date
Source URL/reference
Required transaction
Status
```

Initial catalogue should include relevant Part 71, Part 101 and Part 47 forms identified during the regulatory audit.

---

# 28. Regulatory Fee Engine

Fees shall be version controlled.

Suggested schema:

```text
regulatory_fees

id
regulation
transaction_code
description
amount
currency
effective_from
effective_to
source
source_version
verified_at
```

Historical fee records shall never be overwritten when a new tariff takes effect.

The current 2026 SACAA fee schedule identified during research should be loaded as an initial regulatory dataset, subject to source verification during implementation.

---

# 29. Application & Renewal Pack Builder

The platform shall prepare application/renewal cases and evidence packages.

Example:

```text
UASLA RENEWAL

Aircraft: ZT-ABC

✓ Certificate of Registration
✓ Previous UASLA
✓ Flight-folio evidence
✓ Maintenance summary
✓ Insurance
✓ Radio documentation
✓ Mass & balance
✓ Operator evidence

✗ Updated maintenance evidence

READINESS: 87%
```

Outputs may include:

- required forms;
- supporting documents;
- checklist;
- applicable fees;
- evidence index;
- application cover sheet;
- PDF submission pack.

Submission to SACAA shall remain a separate explicit process unless an authorised integration becomes available.

---

# 30. Notification Engine

Notifications may cover:

```text
RPC expiry
Revalidation window
Medical expiry
Security check
Training/competency expiry
Aircraft registration
UASLA expiry
UASOC expiry
Insurance
Maintenance
Operations Manual acknowledgement
Corrective action
Application deadline
Regulatory change
```

Supported channels may include:

- in-application;
- email;
- mobile push;
- SMS/WhatsApp where integrated.

---

# 31. External Regulatory Integration

Integrations shall be classified as:

```text
Manual
Document-based
Verified External
API
```

The platform shall not assume the existence of undocumented SACAA APIs.

Where SACAA e-Services or other official digital services are used, the platform should link users to the authoritative process and retain only appropriate operational/evidence records.

---

# 32. GIS & Mapping Projects

The platform shall support geospatial UAS missions independently of general flight operations.

Suggested hierarchy:

```text
GIS Project
    ↓
Mission
    ↓
Flight
    ↓
Dataset
    ↓
Orthomosaic / Spatial Output
    ↓
Layer
    ↓
Feature
    ↓
Opportunity / Finding
    ↓
Evidence
    ↓
Report
```

Applications may include:

- environmental mapping;
- infrastructure mapping;
- economic opportunity mapping;
- land-use analysis;
- asset inspections;
- construction monitoring;
- disaster assessment;
- community development intelligence.

---

# 33. Pilot Mobile Experience

Suggested functions:

```text
Home
My RPC / Licence Wallet
Ratings & Competency
My Logbook
Missions
Pre-flight
Flight
Post-flight
Aircraft
Documents
Learning
Notifications
```

Example dashboard:

```text
RPC                 VALID
Assigned Aircraft   ZT-ABC
Today's Mission     Community Mapping
30-Day Flight Time  12h 44m

ALERT
UASLA expires in 54 days
```

---

# 34. Operator Portal

Suggested dashboard:

```text
ACTIVE PILOTS          12
ACTIVE AIRCRAFT         8
FLIGHTS TODAY           7
MONTH FLIGHT HOURS     86

COMPLIANCE             94%

Aircraft unavailable   2
Licences expiring      3
Open occurrences       1
Maintenance due        2
```

---

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

# 37. Phase 1 — Pilot & Fleet Compliance MVP

The first production release should establish the regulatory master records.

Build:

1. users and access control;
2. pilots;
3. RPC/rating/medical records;
4. aircraft;
5. registration records;
6. UASLA/RLA records;
7. pilot digital logbook;
8. aircraft flight folio;
9. regulatory document management;
10. expiry/renewal alerts;
11. regulatory knowledge register;
12. regulatory fee register;
13. compliance dashboard;
14. audit trail and retention controls.

This phase creates a useful standalone product before advanced operational management is introduced.

---

# 38. Phase 2 — Flight Operations

Add:

- mission planning;
- flight approvals;
- crew management;
- pre-flight checks;
- post-flight checks;
- operational risk assessments;
- maps;
- flight tracks;
- battery management;
- defects;
- serviceability controls.

---

# 39. Phase 3 — Operator Governance

Add:

- UASOC management;
- OpsSpecs;
- Operations Manual control;
- acknowledgements;
- organisational post holders;
- security management;
- safety management;
- audits;
- corrective actions;
- regulatory application/renewal packs.

---

# 40. Phase 4 — Training & Competency

Add a native VMT training environment or optional integration layer.

Model:

```text
Regulatory / Organisational Requirement
                ↓
Required Competency
                ↓
Course / Module
                ↓
Assessment / Practical
                ↓
Competency Record
                ↓
Pilot / Operator Compliance
```

Training that constitutes regulated aviation training must be clearly distinguished from general educational content and handled under the applicable approval/ATO framework.

---

# 41. Phase 5 — GIS & Opportunity Intelligence

Add:

- GIS projects;
- drone mapping missions;
- geospatial datasets;
- imagery/orthomosaic references;
- spatial layers;
- field verification;
- environmental mapping;
- infrastructure mapping;
- economic opportunity mapping;
- analytical dashboards;
- reporting.

Workflow:

```text
PLAN
  ↓
AUTHORISE
  ↓
FLY
  ↓
CAPTURE
  ↓
PROCESS
  ↓
MAP
  ↓
ANALYSE
  ↓
REPORT
```

---

# 42. Product Editions

One platform/codebase may support multiple commercial editions.

## UAS Pilot

For individual remote pilots:

- licence wallet;
- ratings;
- digital logbook;
- flight history;
- renewal alerts;
- personal compliance;
- training/learning.

## UAS Operator

For organisations:

- pilots;
- fleet;
- UASOC;
- UASLA;
- missions;
- flight folios;
- maintenance;
- safety;
- security;
- compliance;
- regulatory evidence.

## UAS Academy

For training organisations/learning environments:

- courses;
- modules;
- assessments;
- practical activities;
- competency records;
- regulatory knowledge;
- GIS curriculum.

The Academy product must clearly identify whether training is general educational content or training delivered under an applicable approved aviation training framework.

---

# 43. Regulatory Data Governance

No compliance rule shall be implemented without retaining at minimum:

```text
Source authority
Regulation number
Clause
Requirement
Applicability
Effective date
Version/revision
Evidence requirement
Control type
Retention/validity rule
Source reference
Verification date
```

The platform must therefore be **regulation-version aware**.

A regulatory change must not retrospectively corrupt historical compliance assessments.

---

# 44. Non-Functional Requirements

## Security

- role/permission-based access;
- encryption of sensitive data in transit and at rest where appropriate;
- controlled document access;
- MFA capability;
- session/device controls;
- comprehensive audit logs.

## Reliability

- database backups;
- document backup strategy;
- recovery procedures;
- integrity monitoring.

## Traceability

Every regulatory decision should be traceable to:

```text
Requirement → Control → Evidence → Decision → User → Time
```

## Mobile Responsiveness

Pilot operational functions must be usable from mobile devices.

## Offline Capability

Future mobile releases should support offline access to operationally essential information and synchronisation after connectivity is restored.

## Multi-Organisation Support

The architecture should be capable of supporting multiple independent UAS operators with strict tenant separation if VMT commercialises the platform as SaaS.

## Regulatory Updating

The system must support new regulatory versions, fees, forms and requirements without application-wide code changes for every regulatory amendment.

---

# 45. Strategic Product Position

The platform should be positioned as a:

## UAS Compliance Operating System

rather than simply a drone logbook.

Its intended value proposition is:

> One platform connecting remote pilot competency, aircraft records, operational flight management, regulatory compliance, safety, maintenance, training and geospatial missions.

The platform supports the lifecycle:

```text
LEARN
  ↓
QUALIFY
  ↓
REGISTER
  ↓
PREPARE
  ↓
OPERATE
  ↓
LOG
  ↓
MAINTAIN
  ↓
COMPLY
  ↓
RENEW
  ↓
ANALYSE
```

---

# 46. Implementation Priority

The recommended starting point is **Phase 1 — Pilot & Fleet Compliance**.

The foundational relationship is:

```text
Operator
   │
   ├── Pilots
   │      └── RPC / Ratings / Medical / Logbook
   │
   └── Aircraft
          └── Registration / UASLA / Flight Folio / Maintenance

                 ↓
          Compliance Engine
                 ↓
          Alerts & Evidence
```

Once this foundation is stable, missions, operational approvals, maintenance workflows, safety, security, training and GIS can be layered onto the same regulatory model.

---

# 47. Regulatory Disclaimer

This specification is a software/product requirements document and does not constitute legal or regulatory advice.

All regulatory controls, forms, fees, validity periods, application requirements and automated blocking rules must be verified against the current official SACAA regulations, SA-CATS, technical guidance material, AICs, forms, notices and applicable approvals before production activation.

The platform should display the regulatory source and last verification date for compliance-critical rules.

---

**End of Functional Requirements Specification**
