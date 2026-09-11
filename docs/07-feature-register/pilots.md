# Pilot Management

## Requirements

| Requirement | Status | Verification | Latest commit |
|---|---|---|---|
| FR-PIL-001 | VERIFIED | docs/10-verification/phase-1/fr-pil-001-pilot-profile.md | Pending commit |
| FR-PIL-002 | NOT STARTED | - | - |
| FR-PIL-003 | NOT STARTED | - | - |
| FR-PIL-004 | NOT STARTED | - | - |

## Founding Intent

FR-PIL-001 requires a unique pilot profile containing personal details, SACAA certificate/licence number, RPC category, ratings, issue and expiry dates, medical status, radiotelephony qualification, language proficiency where applicable, training history, examiner records, operator affiliations and supporting documents.

## Implementation

### Domain

Implemented under `app/Domains/Uas/Pilots/Domain`:

- `Models/UasPilot.php`
- `Enums/RpcCategory.php`
- `Enums/PilotMedicalStatus.php`
- `Enums/RadiotelephonyQualification.php`
- `Enums/PilotProfileStatus.php`
- `Contracts/PilotRepositoryInterface.php`
- `Policies/PilotProfilePolicy.php`

### Application

Implemented under `app/Domains/Uas/Pilots/Application`:

- `DTOs/PilotProfileData.php`
- `Actions/CreatePilotProfile.php`
- `Actions/UpdatePilotProfile.php`
- `Queries/ListPilotProfiles.php`
- `Queries/PilotProfileOptions.php`
- `Queries/PilotProfilePresenter.php`

### Infrastructure

Implemented under `app/Domains/Uas/Pilots/Infrastructure`:

- `Repositories/EloquentPilotRepository.php`

Repository binding is registered in `app/Providers/UasDomainServiceProvider.php`.

### Database

Implemented `uas_pilots` with unique profile identifiers, profile/contact fields, qualification placeholders, affiliation/document JSON fields and regulatory traceability metadata.

### Permissions

`PilotProfilePolicy` explicitly authorizes authenticated view/create/update behavior and denies delete. This is a starter policy boundary, not the final role model.

### Routes / Controllers

Authenticated resource routes are registered for pilot index, create, store, show, edit and update. Delete is intentionally omitted until audit and retention controls are implemented.

HTTP classes now live under `app/Domains/Uas/Pilots/Http`.

### UI

Inertia React pages added under `resources/js/pages/pilots` with sidebar and dashboard entry points. Shared UAS UI components under `resources/js/components/uas` provide page headers, metric cards, status badges and empty states using light/dark-aware semantic tokens.

### Tests

`tests/Feature/Uas/PilotProfileTest.php` covers authentication, create, update, duplicate SACAA certificate rejection, required identity validation and the server-side pilot policy contract.

## Regulatory References

| Requirement | Source | Version / Date | Applicability | Responsible role | Evidence | Validity / retention |
|---|---|---|---|---|---|---|
| FR-PIL-001 | Civil Aviation Regulations Part 71; founding FRS | FRS v1.0, 2026-09-09 | Remote pilot master record for South African UAS operations managed in the VMT platform | Compliance Manager | `uas_pilots` database record and supporting document reference fields | Retention control not yet implemented; delete route withheld |

## Verification

See `docs/10-verification/phase-1/fr-pil-001-pilot-profile.md` and `docs/10-verification/phase-1/architecture-alignment-pass.md`.

## Outstanding

- FR-PIL-002 RPC compliance-state calculation.
- FR-PIL-003 revalidation windows and alerts.
- FR-PIL-004 submission deadline monitoring.
- Fine-grained role/permission model beyond authenticated starter policy.
- Audit trail and retention controls.
- Dedicated certificate, rating, medical, training and document entities.

## Change History

| Date | Commit | Change |
|---|---|---|
| 2026-09-09 | Pending commit | Implemented FR-PIL-001 pilot profile master record slice. |
| 2026-09-09 | Pending commit | Refactored pilot profile slice into Domain/Application/Infrastructure/Http layers and added shared UAS UI components. |
