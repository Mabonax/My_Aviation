# Pilot & Flight Records

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
