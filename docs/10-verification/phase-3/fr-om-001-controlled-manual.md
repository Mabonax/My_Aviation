# FR-OM-001 - Controlled Manual

## Requirement

Operations Manuals shall be version controlled. Store Manual, Revision, Effective date, Approval status, Authority approval reference, Sections, Change summary and Superseded revision.

## Implementation

- Added `uas_operations_manual_revisions` for operator-bound Operations Manual revision control.
- Added `UasOperationsManualRevision` model and `UasOperator::manualRevisions()` relationship.
- Added `OperationsManualControl` approval-status service for draft, internal review, authority submission, approved, superseded and rejected states.
- Added create/update actions with FR-OM-001 audit evidence and automatic superseding of referenced prior revisions.
- Added manual revision options, presenter and operator manual-revision report queries.
- Added nested operator manual revision creation and direct show/edit/update routes.
- Added Inertia manual revision create/edit/show pages and operator show-page manual revision summary.
- Repaired the local MySQL migration index naming with short explicit index names for `uas_operator_id + approval_status` and `effective_date`.

## Verification

- PHP syntax pass over the new controller, requests, migration and feature test: passed.
- `php artisan route:list --path=manual-revisions`: registered 5 manual revision routes.
- `php artisan test --filter=Phase3OperationsManualControlTest`: passed, 6 tests, 88 assertions.
- `php artisan test`: passed, 91 tests, 845 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_100000_create_uas_operations_manual_revisions_table` after shortening index names.

## Outstanding

- File attachment workflow for controlled manual source documents and authority approval letters.
- FR-OM-002 distribution records for required personnel.
- FR-OM-003 acknowledgement workflow for receipt/readership.
- FR-OM-004 training triggers when a manual amendment affects personnel competency.
