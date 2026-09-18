# FR-OPS-001 - Operator Profile

## Requirement

Store the UAS operator profile including legal entity, trading name, registration number, UASOC/ROC number, issue date, expiry, status, accountable manager, responsible persons, safety/security post holders, operating bases, approved aircraft, approved pilots and Operations Specifications.

## Implementation

- Added `uas_operators` with certificate identity, post holders, operating bases, approved aircraft, approved pilots, OpsSpecs, evidence references and regulatory traceability fields.
- Added `UasOperator` model, `OperatorPolicy`, create/update actions and audit trail records.
- Added operator list/options/presenter queries.
- Added `/operators` authenticated Inertia CRUD workflow for index, create, show, edit and update.
- Added sidebar navigation and React pages/forms for operator governance profiles.
- Added Phase 3 status matrix with remaining certificate lifecycle and manual-control requirements.

## Verification

- PHP syntax pass over app/Domains/Uas, app/Providers, routes, tests/Feature/Uas and database/migrations: passed, 145 files.
- `php artisan route:list --path=operators`: registered 6 operator routes.
- `php artisan test --filter=Phase3OperatorProfileTest`: passed, 5 tests, 85 assertions.
- `php artisan test`: passed, 80 tests, 674 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_080000_create_uas_operators_table`.

## Outstanding

- Certificate application, amendment and renewal cases (`FR-OPS-002`).
- Operations Manual revision control, distribution and acknowledgements (`FR-OM-001` through `FR-OM-003`).
- Manual amendment training trigger (`FR-OM-004`).