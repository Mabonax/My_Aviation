# FR-FEE-001 - Regulatory Fee Engine

## Status

`VERIFIED`

## Scope

Implements the Phase 4 regulatory fee engine described in section 28 of the functional requirements, extending the existing Phase 1 `regulatory_fees` foundation with version control, policy-gated workflows and UI access.

## Repository Evidence

- `database/migrations/2026_09_11_180000_add_version_control_to_regulatory_fees_table.php`
- `app/Domains/Uas/Regulations/Domain/Models/RegulatoryFee.php`
- `app/Domains/Uas/Regulations/Application/Actions/CreateRegulatoryFee.php`
- `app/Domains/Uas/Regulations/Application/Actions/SupersedeRegulatoryFee.php`
- `app/Domains/Uas/Regulations/Application/Queries/ListRegulatoryFees.php`
- `app/Domains/Uas/Regulations/Application/Queries/RegulatoryFeePresenter.php`
- `app/Domains/Uas/Regulations/Http/Controllers/RegulatoryFeeController.php`
- `resources/js/pages/regulations/fees/*`
- `tests/Feature/Uas/Phase4RegulatoryFeeEngineTest.php`

## Behaviour

- Captures regulation part, transaction code, description, amount, currency, effective window, source, source version, status and verification timestamp.
- Enforces one fee source version per transaction code.
- Creates new tariff versions as new rows and links them to the prior fee record.
- Marks the previous tariff superseded and closes its effective window without overwriting its historical amount, description, source or source version.
- Records create, supersede and version-created audit entries with `FR-FEE-001`.
- Exposes authenticated Inertia pages for listing, creating, viewing and superseding fee records.

## Verification

- `php artisan test --filter=Phase4RegulatoryFeeEngineTest`: passed, 5 tests, 69 assertions.
- `php artisan test`: passed, 135 tests, 1405 assertions.
- `npm.cmd run build`: passed and included `resources/js/pages/regulations/fees/*` in the Vite manifest.
- `php artisan migrate --force`: passed; applied `2026_09_11_180000_add_version_control_to_regulatory_fees_table`.

## Outstanding

- Controlled import of the current SACAA 2026 fee schedule from official source material.
- Direct fee lookup from application and renewal pack builder transactions.
- Browser sign-off with authenticated production-like data.
