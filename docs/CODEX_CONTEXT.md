# Codex Project Context

## Product

**VMT UAS Compliance & Operations Platform**  
Product owner: **Various Media Technologies (Pty) Ltd (VMT)**

This is a standalone VMT product. It has no affiliation with or dependency on the AB4IR ERP or LMS.

## Mandatory reading before implementation

Before making a material code change, read:

1. `docs/01-foundation/README.md`
2. `docs/03-platform-architecture/README.md`
3. `docs/04-implementation-phases/README.md`
4. `docs/06-implementation-status/README.md`
5. The relevant file under `docs/07-feature-register/`
6. Any applicable ADRs under `docs/09-decisions/`
7. The latest entries under `docs/08-development-log/`

## Source of truth hierarchy

When documents disagree, use this order:

1. Current verified law/regulation and authoritative regulatory source
2. Founding Functional Requirements Specification
3. Approved Architecture Decision Records
4. Current implementation phase specification
5. Feature register
6. Implementation status files
7. Development log
8. Existing code behaviour

Code behaviour is not automatically correct merely because it exists.

## Development traceability rule

Every implementation task must:

1. Identify the founding requirement(s) being implemented.
2. Reference stable requirement IDs such as `FR-PIL-001`.
3. Identify the applicable implementation phase.
4. Preserve regulatory source, version, effective date and applicability for compliance-critical rules.
5. Implement business rules in domain/application services rather than controllers or UI components.
6. Add or update automated tests.
7. Update the relevant feature register.
8. Update the relevant phase implementation-status file.
9. Add a chronological development-log entry.
10. Add or update an ADR when a material architectural decision is made.
11. Record verification evidence.
12. Never mark a requirement `VERIFIED` merely because code exists.

## Allowed implementation states

- `NOT STARTED`
- `IN PROGRESS`
- `IMPLEMENTED`
- `VERIFIED`
- `BLOCKED`

### State definitions

**NOT STARTED**  
No material implementation exists.

**IN PROGRESS**  
Some implementation exists, but the requirement is incomplete.

**IMPLEMENTED**  
The intended feature exists in code, but full verification has not yet been completed.

**VERIFIED**  
The feature:
- exists in code;
- satisfies the founding requirement;
- passes automated tests;
- passes required authorisation/permission checks;
- passes the expected user workflow;
- records compliance-critical regulatory assumptions and sources;
- has documentation updated.

**BLOCKED**  
Implementation cannot proceed because a required dependency, regulatory clarification, external approval or technical prerequisite is unresolved.

## Regulatory implementation rule

No compliance-critical rule may be introduced without recording:

- regulation/source;
- clause or requirement reference;
- source version;
- effective date;
- applicability;
- responsible party;
- system control;
- evidence;
- validity/expiry rule;
- retention rule where applicable.

Do not silently hard-code regulatory assumptions.

## Documentation update rule

Documentation updates are part of the implementation task and should be committed with the code change they describe.

A task is not documentation-complete until:
- feature register is updated;
- phase status is updated;
- development log is updated;
- verification evidence is recorded;
- applicable ADRs are updated or created.

## Reconciliation rule

Periodically audit the repository against the founding documents.

The audit must compare:
- migrations;
- models;
- domain/application services;
- policies/permissions;
- routes/controllers;
- React/Inertia UI;
- tests;
- stored regulatory data;
- documentation status.

Where the documentation and code disagree, record the discrepancy and correct the inaccurate side rather than assuming the code is authoritative.
