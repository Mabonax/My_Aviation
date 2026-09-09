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
