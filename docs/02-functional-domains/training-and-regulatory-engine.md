# Training, Regulation, Compliance & External Integration

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
