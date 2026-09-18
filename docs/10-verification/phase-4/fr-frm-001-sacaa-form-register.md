# FR-FRM-001 - SACAA Form Register

## Status

`VERIFIED`

## Scope

Implements the Phase 4 SACAA Form Register capability described in section 27 of the functional requirements. The implementation uses `FR-FRM-001` as the traceability identifier for the version-controlled form catalogue.

## Repository Evidence

- `database/migrations/2026_09_11_170000_create_regulatory_forms_table.php`
- `app/Domains/Uas/Regulations/Domain/Models/RegulatoryForm.php`
- `app/Domains/Uas/Regulations/Application/Actions/CreateRegulatoryForm.php`
- `app/Domains/Uas/Regulations/Application/Actions/SupersedeRegulatoryForm.php`
- `app/Domains/Uas/Regulations/Application/Queries/ListRegulatoryForms.php`
- `app/Domains/Uas/Regulations/Application/Queries/RegulatoryFormPresenter.php`
- `app/Domains/Uas/Regulations/Http/Controllers/RegulatoryFormController.php`
- `resources/js/pages/regulations/forms/*`
- `tests/Feature/Uas/Phase4SacaaFormRegisterTest.php`

## Behaviour

- Captures form code, title, regulatory area, revision, effective date, source reference, source URL, required transaction, status and verification timestamp.
- Enforces version uniqueness by form code and revision.
- Creates new revisions as new rows and marks the previous form as superseded without mutating historical catalogue details.
- Records audit entries for create, supersede and version-created events with `FR-FRM-001`.
- Exposes authenticated Inertia pages for listing, creating, viewing and superseding regulatory forms.

## Verification

- `php artisan test --filter=Phase4SacaaFormRegisterTest`: passed, 5 tests, 68 assertions.
- `php artisan test`: passed, 129 tests, 1330 assertions.
- `npm.cmd run build`: passed and included `resources/js/pages/regulations/forms/*` in the Vite manifest.
- `php artisan migrate --force`: passed; applied `2026_09_11_150000_create_uas_training_compliance_links_table`, `2026_09_11_160000_add_version_linkage_to_regulatory_requirements_table` and `2026_09_11_170000_create_regulatory_forms_table`.

## Outstanding

- Controlled initial SACAA Part 71, Part 101 and Part 47 catalogue import from official sources.
- Direct linking between application pack builder transactions, required forms and current form revisions.
- Browser sign-off with authenticated production-like data.
