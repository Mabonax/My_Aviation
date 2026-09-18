# FR-OPS-002 - Certificate Lifecycle

## Requirement

Maintain UASOC/ROC application, amendment and renewal cases containing deadlines, evidence requirements, outstanding documents, fleet, personnel, OpsSpec, Operations Manual revision, fees, submission status, authority correspondence and outcome.

## Implementation

- Added `uas_operator_certificate_cases` for operator-bound application, amendment and renewal cases.
- Added `UasOperatorCertificateCase` model and `UasOperator::certificateCases()` relationship.
- Added `CertificateCaseLifecycle` service with documented case types, statuses and derived submission status.
- Added create/update actions with FR-OPS-002 audit evidence.
- Added case options, presenter and operator certificate-case report queries.
- Added nested operator case creation and direct show/edit/update routes.
- Added Inertia certificate case create/edit/show pages and operator show-page case summary.

## Verification

- PHP syntax pass over app/Domains/Uas, app/Providers, routes, tests/Feature/Uas and database/migrations: passed, 157 files.
- `php artisan route:list --path=certificate-cases`: registered 5 certificate case routes.
- `php artisan test --filter=Phase3CertificateLifecycleTest`: passed, 5 tests, 83 assertions.
- `php artisan test`: passed, 85 tests, 757 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_090000_create_uas_operator_certificate_cases_table`.

## Outstanding

- Attachment/document upload workflow for actual case evidence files.
- Deeper integration with controlled Operations Manual revisions once FR-OM-001 is implemented.
- Notification/escalation automation for approaching deadlines.