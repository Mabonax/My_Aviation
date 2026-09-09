# Product & Governance

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
