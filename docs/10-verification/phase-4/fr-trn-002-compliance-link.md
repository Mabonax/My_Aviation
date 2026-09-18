# FR-TRN-002 - Compliance Link

## Status

`VERIFIED`

## Requirement

A regulatory or organisational requirement may reference a required competency and training record.

## Implementation

- Added `uas_training_compliance_links` to connect a training course, required competency, competency record and optional regulatory requirement.
- Captures requirement source type, reference, title, responsible role, applicability, evidence required, validity/retention context, due date and link status.
- Added `UasTrainingComplianceLink` model and relationships from training courses, competencies and competency records.
- Extended training course creation so compliance links are created transactionally with the learning structure.
- Added validation requiring at least one competency and competency record when compliance links are captured.
- Added FR-TRN-002 audit evidence through `training.compliance_link.created`.
- Added training course list/show visibility for compliance-link counts and detail.
- Added course creation selection of active `regulatory_requirements` records to populate compliance links with source-controlled requirement data.

## Verification

- PHP syntax pass over training domain, migrations and UAS feature tests: passed.
- `php artisan route:list --path=training-courses`: registered 4 training course routes.
- `php artisan test --filter=Phase4TrainingComplianceLinkTest`: passed, 5 tests, 71 assertions.
- `php artisan test --filter=Phase4TrainingCourseStructureTest`: passed, 5 tests, 63 assertions.
- `php artisan test`: passed, 113 tests, 1132 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: later passed; applied `2026_09_11_150000_create_uas_training_compliance_links_table` locally.

## Outstanding

- Dedicated compliance-link edit/update workflow after initial course creation.
- Notification/escalation workflow for overdue competency links.
