# UAS COMPLIANCE & OPERATIONS PLATFORM
## Functional Requirements Specification

**Version:** 1.0  
**Jurisdiction:** Republic of South Africa  
**Primary regulatory authority:** South African Civil Aviation Authority (SACAA)  
**Primary regulatory framework:** CAR Parts 71, 101, 47, 187 and associated SA-CATS/TGM requirements  
**Proposed technology:** Laravel + React/Inertia + MySQL/PostgreSQL + GIS/Mapping Services

---

# 1. PURPOSE

The purpose of the UAS Compliance & Operations Platform is to provide a digital environment through which remote pilots, UAS operators, training organisations and organisational compliance personnel can:

- maintain pilot licensing and competency records;
- maintain digital flight logbooks;
- maintain aircraft flight folios;
- register and manage organisational UAS fleets;
- monitor Certificates of Registration, UASLA and UASOC information;
- plan and authorise operations;
- maintain safety, security and operational records;
- manage defects and maintenance;
- prepare renewal/application evidence;
- maintain regulatory records for prescribed retention periods;
- manage training and competency;
- monitor regulatory fees;
- receive compliance alerts;
- conduct GIS and mapping missions;
- generate reports and regulator-ready submission packs.

The platform **does not issue SACAA certificates or replace SACAA approvals**.

It assists users to manage the information, evidence and workflows required to support regulatory compliance.

---

# 2. REGULATORY MODEL

The system shall separate regulatory responsibilities according to their source.

## 2.1 Part 71 — Remote Pilot Certification

Part 71 covers the Remote Pilot Certificate framework.

A person may not exercise RPC privileges unless that person holds the appropriate valid RPC/rating, has received the required instruction and has successfully completed the applicable skills test.

Initial RPC requirements include:

- minimum age of 18;
- prescribed medical requirement;
- restricted aeronautical radiotelephony certificate;
- prescribed flight training;
- theoretical examination;
- skills test;
- prescribed Part 187 fee.

The theoretical examination applicable to the RPC must be passed within 90 days preceding the skills test.

RPC revalidation checks must be conducted by an authorised examiner within 90 days before RPC expiry. Following successful revalidation, the prescribed documentation must be submitted within 30 days. A normal revalidation is valid until the last day of the 24th month from issue.

### System implication

The platform shall implement a **Pilot Certification & Currency Engine**.

---

# 3. USER ROLES

The initial system shall support at least:

| Role | Primary responsibility |
|---|---|
| Remote Pilot | Personal licensing, logbook, flights, documents |
| Pilot Instructor | Training and competency records |
| Examiner | Skills/revalidation records |
| Flight Operations Officer | Missions and flight approvals |
| Responsible Person: Flight Operations | Operational governance |
| Responsible Person: Aircraft | Fleet and airworthiness oversight |
| Maintenance Technician | Maintenance and defect rectification |
| Safety Manager | Hazard, occurrence and risk management |
| Security Coordinator | Personnel and physical security |
| Compliance Manager | Regulatory compliance |
| Accountable Manager | Organisational oversight |
| Training Administrator | Course/training administration |
| UASOC Administrator | Operator certificate administration |
| System Administrator | Technical/system governance |

Role names must be configurable to accommodate different approved organisational structures.

---

# 4. PILOT MANAGEMENT

## FR-PIL-001 — Pilot Profile

The system shall maintain a unique pilot profile containing:

- personal details;
- SACAA licence/certificate number;
- RPC category;
- ratings;
- licence issue date;
- expiry date;
- medical status;
- radiotelephony qualification;
- language proficiency where applicable;
- training history;
- examiner records;
- operator affiliations;
- uploaded certificates.

---

## FR-PIL-002 — RPC Status

The system shall calculate:

`Valid / Expiring / Revalidation Due / Expired / Suspended / Unknown`

The calculation must be based upon recorded regulatory evidence rather than a manually entered "compliant" checkbox.

---

## FR-PIL-003 — Revalidation Window

The system shall automatically determine the 90-day pre-expiry revalidation period required by regulation 71.01.4.

Example:

```text
RPC expiry:
30 September 2027

Revalidation window:
02 July 2027 – 30 September 2027
```

Alerts should be generated at configurable intervals such as:

- 120 days;
- 90 days;
- 60 days;
- 30 days;
- 14 days;
- expiry.

---

## FR-PIL-004 — Post-Revalidation Submission

Following a successful revalidation, the system shall create a 30-day submission deadline.

```text
Revalidation completed:
14 August 2027

Submission due:
13 September 2027
```

---

# 5. DIGITAL PILOT LOGBOOK

The pilot logbook shall be distinct from the aircraft flight folio.

## FR-LOG-001 — Flight Entry

Each pilot flight record shall support:

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
RPC category/rating utilised
VLOS / EVLOS / BVLOS
Day / night

Take-off
Landing
Total duration

PIC time
Dual/instruction time
Observer details

Maximum altitude
Maximum distance

Training purpose
Operational purpose

Remarks
Occurrence reference
```

---

## FR-LOG-002 — Experience Calculations

The system shall calculate:

- total career flight time;
- current year;
- previous 12 months;
- 90 days;
- 30 days;
- aircraft category;
- aircraft type;
- night;
- BVLOS/VLOS;
- training;
- operational flights.

---

## FR-LOG-003 — Logbook Summary

Users shall be able to generate:

**Pilot Logbook Summary**

for a chosen reporting period.

Output:

- PDF;
- CSV;
- operator report;
- renewal supporting record.

---

# 6. AIRCRAFT FLIGHT FOLIO

Part 101.05.22 requires an aircraft flight folio or equivalent compliant record. It must remain current and legible, be available at the relevant remote pilot station during flight, and entries must be made immediately following the relevant occurrence. Maintenance entries must be certified by the responsible maintenance person.

## FR-FOL-001 — Digital Flight Folio

Every registered aircraft shall maintain its own folio.

---

## FR-FOL-002 — Flight Record

A completed operational flight shall automatically create/update the aircraft's folio entry.

---

## FR-FOL-003 — Charging/Fuel/Oil

Where applicable, the folio shall record:

- battery charging;
- fuel;
- oil;
- applicable consumption/energy records.

---

## FR-FOL-004 — Maintenance Certification

A maintenance entry shall require certification by an authorised/responsible maintenance user before being treated as completed.

---

## FR-FOL-005 — Offline/Remote Availability

Because the folio must be accessible at the relevant RPS during flight, the system should ultimately support:

- mobile access;
- cached/offline latest aircraft folio;
- QR aircraft identification;
- downloadable flight folio.

---

# 7. AIRCRAFT REGISTRY

## FR-AIR-001 — Aircraft Record

Each aircraft shall contain:

```text
Registration
Manufacturer
Model
Serial number
Aircraft category
Owner
Operator
Date acquired
Status
Base/location

Certificate of Registration
UASLA
System Safety approval
Maintenance programme
Insurance
Radio licence
Operations Manual applicability
```

---

## FR-AIR-002 — Registration Lifecycle

Support:

- new registration;
- ownership transfer;
- amendment;
- cancellation;
- duplicate registration.

SACAA currently directs UAS owners to the relevant Part 47 registration process from its UAS portal.

---

## FR-AIR-003 — Aircraft State

Aircraft states:

```text
Pending Registration
Active
Serviceable
Grounded
Maintenance Due
Unserviceable
Suspended
Sold
De-registered
Archived
```

A grounded/unserviceable aircraft must not be selectable for an approved flight.

---

# 8. UASLA MANAGEMENT

The platform shall maintain each aircraft's UAS approval lifecycle separately from registration.

## FR-LA-001

Capture:

```text
UASLA number
Issue date
Expiry
Aircraft
Approval scope
Restrictions
Certificate/document
Status
```

---

## FR-LA-002 — Renewal Workflow

The application shall support configurable renewal reminders, including advance preparation windows.

A renewal pack may include:

- current UASLA;
- Certificate of Registration;
- flight-folio extracts;
- maintenance summary;
- insurance;
- radio records;
- mass and balance;
- maintenance evidence;
- applicable operator evidence.

---

# 9. UASOC OPERATOR MANAGEMENT

Part 101 Subpart 4 contains the operating-certificate requirements, including the Operations Manual, records, safety, security, surveillance and insurance.

## FR-OPS-001 — Operator Profile

```text
Legal entity
Trading name
Registration number

UASOC number
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
Operations specifications
```

---

## FR-OPS-002 — UASOC Renewal

The system shall maintain a renewal case containing:

```text
Renewal due
Required evidence
Outstanding documents
Fleet
Personnel
OpsSpec
Operations Manual revision
Fees
Submission status
Authority correspondence
Decision
```

---

# 10. OPERATIONS MANUAL CONTROL

Part 101.04.5 requires a UASOC holder to maintain an approved Operations Manual describing how the operator will meet regulatory requirements and safety standards. Amendments affecting operations must be submitted for approval, and approved changes must be communicated to affected persons with training where required.

## FR-OM-001 — Controlled Manual

The Operations Manual shall be version controlled.

```text
Manual
Revision
Effective date
Approval status
SACAA approval reference
Sections
Change summary
Superseded revision
```

---

## FR-OM-002 — Distribution

The system shall record who is required to receive an amendment.

---

## FR-OM-003 — Acknowledgement

Users shall digitally acknowledge that they have:

- received;
- read;
- understood

an applicable revision.

---

## FR-OM-004 — Training Trigger

An Operations Manual amendment may generate mandatory training.

Example:

```text
OM REVISION 5

Change:
Night Operations Procedure

Affected:
Remote Pilots
Flight Operations Officers

Training required:
YES

12 assigned
9 complete
3 outstanding
```

---

# 11. OPERATION/MISSION MANAGEMENT

## FR-MIS-001 — Mission Creation

A mission shall record:

```text
Purpose
Client/project
Location
Coordinates
Flight polygon
Operation category

Planned aircraft
Pilot
Observers
Crew

Date/time
Maximum altitude
Planned distance
VLOS/EVLOS/BVLOS
Day/night

Weather
Airspace
Approvals
Risk assessment
Emergency arrangements
```

---

# 12. PRE-FLIGHT COMPLIANCE GATE

This is one of the core features.

Part 101.05.10 specifies certain documents that an operator must have in possession for an applicable UAS operation, including the valid RPC, UASOC/OpSpec, aircraft Certificate of Registration, UASLA and applicable UA/RPS user manual.

The system shall evaluate:

```text
Pilot RPC                  ✓
Required rating            ✓
Medical requirement        ✓
Aircraft serviceability    ✓
Registration               ✓
UASLA                      ✓
UASOC                      ✓
OpsSpec                    ✓
Operations Manual          ✓
Aircraft/RPS manual        ✓
Maintenance state          ✓
Insurance                  ✓
Security status            ✓
Mission approvals          ✓
```

Result:

```text
GREEN
Ready for operational approval

AMBER
Attention required

RED
Do not release flight
```

A system block must distinguish between:

**regulatory prohibition**

and

**organisation policy control**.

---

# 13. AIRSPACE AND GEOSPATIAL RULES

Part 101 Subpart 5 addresses weather, controlled airspace, BVLOS, night operations, proximity to people/property/public roads, pre-flight preparation and other operational matters.

Regulation 101.05.10 includes restrictions such as operations above 400 ft, within 10 km of an aerodrome reference point, within prohibited/restricted airspace and near identified strategic facilities unless otherwise authorised by the Director.

## FR-GEO-001 — Mission Map

Provide:

- mission polygon;
- take-off point;
- landing point;
- planned route;
- maximum flight radius;
- location search.

---

## FR-GEO-002 — Compliance Overlays

Where reliable authoritative datasets are available:

- aerodromes;
- controlled airspace;
- restricted airspace;
- prohibited airspace;
- strategic areas;
- approved operating zones.

Google Maps may be used as the visual basemap, but shall **not be treated as the authoritative aviation-airspace source**.

---

## FR-GEO-003 — Rule Evaluation

Example:

```text
MISSION VALIDATION

Altitude
Planned: 300 ft
✓

Aerodrome proximity
6.3 km
⚠ Requires regulatory review/authorisation

Restricted airspace
None detected
✓
```

---

# 14. PRE-FLIGHT CHECKLIST

Configurable checklists shall support:

```text
Aircraft physical condition
Propellers
Motors
Battery
RPS
GNSS
C2 link
Firmware/configuration
Payload
Weather
Area security
Emergency landing zone
Crew briefing
Public/third-party exposure
Flight permissions
```

Checklists shall retain:

- performer;
- time;
- version;
- results;
- exceptions.

---

# 15. DEFECT MANAGEMENT

## FR-DEF-001

Defects may originate from:

- pre-flight;
- flight;
- post-flight;
- maintenance;
- inspection.

---

## FR-DEF-002

Defect severity:

```text
Observation
Minor
Maintenance Required
Flight Restricted
Ground Aircraft
```

---

## FR-DEF-003

An applicable unresolved grounding defect shall automatically change the aircraft state to:

**UNSERVICEABLE**

and prevent flight release.

---

# 16. MAINTENANCE

SACAA states that UAS should be maintained according to manufacturer requirements and that applicable maintenance programmes are submitted for approval. SACAA currently publishes Part 101 Maintenance Programme guidance and CA 101-33.

## FR-MNT-001 — Maintenance Programme

Record maintenance events based on:

- date;
- flight hours;
- flight cycles;
- battery cycles;
- manufacturer requirement;
- operator programme;
- defect.

---

## FR-MNT-002 — Due Calculations

Example:

```text
Inspection interval:
250 flight hours

Current:
238.4 hours

Remaining:
11.6 hours

STATUS
🟠 MAINTENANCE APPROACHING
```

---

## FR-MNT-003 — Release

Maintenance completion requires:

```text
Work performed
Technician
Parts
Date
Evidence
Certification
Return-to-service status
```

---

# 17. BATTERY MANAGEMENT

For electric UAS:

```text
Battery ID
Manufacturer
Serial
Aircraft compatibility

Cycles
Charge history
Health
Date acquired
Last used

Incidents
Swelling
Damage
Retired
```

Each flight shall reference the battery used.

---

# 18. SAFETY MANAGEMENT

## FR-SAF-001 — Hazard Register

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

---

## FR-SAF-002 — Mission Risk Assessment

Each mission may have an associated operational risk assessment.

---

## FR-SAF-003 — Corrective Actions

A hazard, audit, occurrence or incident may create corrective actions with:

- owner;
- due date;
- evidence;
- verification;
- closure.

---

# 19. OCCURRENCES AND INCIDENTS

SACAA provides organisational occurrence reporting mechanisms and accident/serious incident reporting channels.

## FR-OCC-001

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

The platform must not automatically assert that an event is legally reportable unless the regulatory rule has been verified and configured.

---

# 20. SECURITY MANAGEMENT

Part 101.04.8 requires UASOC holders to conduct applicable background checks, criminal-record checks every 24 months, maintain secure storage and protection against unlawful interference, appoint a security coordinator and provide security-awareness training.

## FR-SEC-001 — Personnel Security

```text
Background check
Criminal record check
Check date
Next due
Security awareness training
Authorised access
```

---

## FR-SEC-002

Next criminal-record review:

```text
last_check + 24 months
```

---

## FR-SEC-003 — Asset Storage

Aircraft can be assigned:

```text
Facility
Secure room
Storage cabinet
Custodian
Access list
```

---

# 21. DOCUMENTATION AND RECORD RETENTION

Part 101.04.6 requires operator recordkeeping that provides adequate storage and reliable traceability for responsibilities, safety, hazards, risk mitigation, competence/training, and quality/safety/security records. Those records must be stored for at least five years and protected against damage, alteration and theft.

## FR-REC-001

Applicable regulatory records shall have:

```text
Created date
Owner
Document/record category
Source
Version
Status
Retention start
Retention end
Locked status
Archive status
```

---

## FR-REC-002

Minimum Part 101 operator record retention default:

**5 years**

where regulation 101.04.6 applies.

---

## FR-REC-003 — Audit Trail

Every material action shall record:

```text
User
Action
Record
Previous value
New value
Timestamp
IP/device where appropriate
```

Deletion of regulatory records must use archive/retention controls rather than uncontrolled permanent deletion.

---

# 22. TRAINING & COMPETENCY

SACAA states that aviation training intended to lead to regulated aviation qualifications must be provided under an appropriately approved ATO framework.

The system shall therefore distinguish:

### Regulatory training

Training performed by/under the approved ATO structure.

### Organisational training

Internal operator competency.

### Educational learning

Non-certifying course material.

---

## FR-TRN-001 — Course Structure

```text
Course
Module
Lesson
Resource
Quiz
Practical
Assessment
Competency
Certificate
```

Potential syllabus:

```text
Aviation Law
Part 71
Part 101
Human Factors
Meteorology
Navigation
UAS Systems
Flight Planning
Radio
Emergency Procedures
Airspace
Safety
Security
GIS
Photogrammetry
```

---

# 23. REGULATORY KNOWLEDGE ENGINE

This is the system's principal compliance architecture.

## FR-REG-001

Every requirement shall exist as structured data:

```text
Regulation
Part
Subpart
Clause
Title

Requirement
Responsible party
Applies to

System control
Evidence required

Frequency
Validity
Retention

Effective date
Superseded date

Official source
Source version

Status
```

Example:

```text
Regulation:
101.04.6(3)

Requirement:
Store applicable operator records for ≥5 years.

Responsible:
UASOC holder

Control:
Record-retention engine

Retention:
5 years

Evidence:
Archived operator record
```

---

# 24. COMPLIANCE REGISTER

The system shall calculate compliance by:

```text
Organisation
Pilot
Aircraft
Operation
Maintenance
Security
Training
Safety
```

Example dashboard:

```text
ORGANISATION COMPLIANCE
92%

Pilots               96%
Aircraft              90%
Operations            94%
Maintenance           87%
Safety                100%
Security              83%

Critical findings       2
Expiring ≤30 days        5
Open corrective actions  8
```

---

# 25. SACAA FORM REGISTER

The application shall maintain a configurable form catalogue.

Initial examples include SACAA forms currently exposed on its site such as:

```text
CA 71-03.2
Application for RPC Licence

CA 71-03.3
Skills Test

CA 71-03.4
Differences/Familiarisation

CA 71-03.5
Initial Skills Test/Revalidation

CA 71-18
Renewal

CA 101-10
Prospective UASOC Pre-Assessment

CA 101-33
Aircraft Maintenance Programme Approval
```

SACAA currently lists the Part 71 RPC forms through Personnel Licensing and operator/maintenance material through its Flight Operations/UAS pages.

---

# 26. REGULATORY FEES

Fees shall be version controlled.

SACAA confirms that its 2026 fee changes took effect **1 April 2026**.

Database:

```text
regulatory_fees

id
regulation
transaction_code
description
amount
effective_from
effective_to
source
source_version
```

The platform must never overwrite historical fee records when a new tariff becomes effective.

Example:

```text
UASOC_INITIAL

2026-04-01 → 2027 tariff change
R5,667

Source:
Part 187 / 2026 fee amendment
```

---

# 27. APPLICATION / RENEWAL PACK BUILDER

This is a key commercial feature.

Example:

```text
UASLA RENEWAL

Aircraft:
ZT-ABC

Checklist

✓ Registration
✓ Previous UASLA
✓ Flight folio
✓ Maintenance summary
✓ Insurance
✓ Radio documentation
✓ Mass & balance
✓ Operator authority

Outstanding:
✗ Updated maintenance evidence

Readiness:
87%

[Generate Submission Pack]
```

Output should include:

- required forms;
- supporting documents;
- checklist;
- fees;
- evidence index;
- application cover sheet.

---

# 28. NOTIFICATION ENGINE

Alerts:

```text
RPC expiry
Revalidation window
Medical validity
Security check
Training expiry
UAS registration
UASLA expiry
UASOC expiry
Insurance
Maintenance
Operations Manual acknowledgement
Corrective action
Submission deadline
```

Channels may include:

- application;
- email;
- mobile push;
- SMS/WhatsApp where integrated.

---

# 29. SACAA INTEGRATION PRINCIPLE

SACAA currently has an e-Services environment and a Personnel Licensing Portal through which licensing details, medicals and ratings can be accessed. Some e-services are online while Personnel Licensing Certification is presently still displayed as "Coming Soon" on the central Online Applications page.

Therefore integrations must initially be classified as:

```text
Manual
Document-based
Verified External
API
```

No undocumented SACAA integration/API should be assumed.

---

# 30. GIS & MAPPING MODULE

The UAS operations platform can support the earlier GIS programme.

## GIS Project

```text
GIS Project
Mission
Flight
Dataset
Orthomosaic
Layer
Feature
Opportunity
Evidence
Report
```

Example:

```text
Project:
Township Economic Opportunity Mapping

Mission:
MAP-001

Aircraft:
ZT-ABC

Pilot:
RPC-XXXXX

Area:
12.6 km²

Flights:
8

Images:
1,840

Outputs:
✓ Orthomosaic
✓ Infrastructure layer
✓ Business layer
✓ Environmental layer

Opportunities identified:
47
```

---

# 31. MOBILE PILOT APP

The mobile application should provide:

### Home

```text
Good morning, John

RPC
● VALID

Aircraft assigned
ZT-ABC

Today's Mission
Community Mapping

Flight time
Last 30 days: 12h 44m

ALERTS
UASLA expires in 54 days
```

### Functions

```text
My Licence
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

---

# 32. OPERATOR PORTAL

```text
DASHBOARD

ACTIVE PILOTS       12
ACTIVE AIRCRAFT      8
FLIGHTS TODAY        7
MONTH HOURS         86

COMPLIANCE          94%

Aircraft unavailable
2

Licences expiring
3

Open occurrences
1

Maintenance due
2
```

---

# 33. LARAVEL DOMAIN ARCHITECTURE

Recommended structure:

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

---

# 34. CORE DATABASE MODEL

High-level:

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

# 35. MVP

The first production release should **not** attempt the entire platform.

### Phase 1 — Pilot & Fleet Compliance

Build:

1. pilots;
2. RPC/rating/medical records;
3. aircraft;
4. registration/UASLA;
5. pilot logbook;
6. aircraft flight folio;
7. expiry alerts;
8. document management;
9. regulatory knowledge register;
10. compliance dashboard.

This already creates a useful product.

---

# 36. PHASE 2 — OPERATIONS

Add:

- mission planning;
- flight approvals;
- crew;
- pre-flight;
- post-flight;
- risk assessment;
- operational maps;
- defects;
- battery management.

---

# 37. PHASE 3 — OPERATOR GOVERNANCE

Add:

- UASOC;
- OpsSpecs;
- Operations Manual;
- acknowledgements;
- security;
- safety;
- organisational post holders;
- audits;
- corrective actions;
- application packs.

---

# 38. PHASE 4 — TRAINING

Connect to the LMS:

```text
Regulatory requirement
        ↓
Required competency
        ↓
Course/module
        ↓
Assessment
        ↓
Competency record
        ↓
Pilot/operator compliance
```

---

# 39. PHASE 5 — GIS & OPPORTUNITY INTELLIGENCE

Add:

```text
Mission planning
Drone imagery
Photogrammetry
GIS dataset
Google Maps/GIS viewer
Field verification
Opportunity mapping
Development intelligence
Reporting
```

This connects the UAS platform directly to the proposed 4IR GIS programme.

---

# 40. PRODUCT MODEL

The architecture can support three products from one codebase.

### Pilot

**UAS Pilot**

Individual subscription.

Provides:

- licence wallet;
- digital logbook;
- flight history;
- renewal alerts;
- training;
- aircraft records.

### Operator

**UAS Operator**

Organisation subscription.

Provides:

- pilots;
- fleet;
- missions;
- flight folios;
- maintenance;
- safety;
- security;
- compliance;
- UASOC governance.

### Academy

**UAS Academy**

Training/education.

Provides:

- learning;
- theory;
- practical activities;
- assessments;
- competency;
- GIS curriculum.

---

# 41. CENTRAL DESIGN PRINCIPLE

The platform should model this chain:

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
APPLICATION / AUDIT
```

Example:

```text
101.04.8

Criminal record check
every 24 months
       ↓
Personnel Security
       ↓
Check uploaded
       ↓
Valid until
12 June 2028
       ↓
90-day alert
       ↓
Renew check
```

---

# 42. STRATEGIC END STATE

The final product should function as a:

# UAS Compliance Operating System

Rather than merely:

**"a drone logbook."**

Its differentiating proposition would be:

> **One platform connecting pilot competency, aircraft airworthiness, operational flight management, regulatory compliance, safety, maintenance, training and geospatial missions.**

The platform would accompany the UAS lifecycle:

```text
LEARN
  ↓
QUALIFY
  ↓
REGISTER
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

and for the 4IR programme:

```text
TRAIN
  ↓
FLY
  ↓
CAPTURE
  ↓
MAP
  ↓
ANALYSE
  ↓
IDENTIFY OPPORTUNITY
  ↓
REPORT
```

This creates a direct link between regulated drone operations and meaningful environmental, infrastructure and economic-development outcomes.

---

## Regulatory implementation rule

No compliance requirement should be hard-coded into Laravel without storing:

```text
source
regulation number
effective date
version
applicability
rule
evidence
```

This is essential because Civil Aviation Regulations, SA-CATS, SACAA forms, terminology and fee schedules change over time.

The software must therefore be **regulation-version aware**.