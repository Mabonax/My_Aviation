# FR-TRN-001 - Learning Structure

## Status

`VERIFIED`

## Requirement

Training and competency shall model Course, Module, Lesson, Resource, Quiz, Practical, Assessment, Competency and Certificate/Record while distinguishing regulated/ATO training, operator/internal competency training and general education.

## Implementation

- Added `uas_training_courses`, `uas_training_modules`, `uas_training_lessons`, `uas_training_resources`, `uas_training_assessments`, `uas_training_competencies` and `uas_training_competency_records`.
- Added training domain models and relationships for the learning structure.
- Added `TrainingClassification` service separating regulated/ATO, operator-internal competency and general education content.
- Added validation requiring an authority approval reference before a course may be classified as regulated/ATO training.
- Added training course policy with `training.view`, `training.create` and `training.update` permissions.
- Added course creation action with FR-TRN-001 audit evidence and regulatory traceability.
- Added training course catalogue, creation and show pages through Inertia.
- Added Training sidebar navigation.

## Verification

- PHP syntax pass over training migration, controller, request, action and feature test: passed.
- `php artisan route:list --path=training-courses`: registered 4 training course routes.
- `php artisan test --filter=Phase4TrainingCourseStructureTest`: passed, 5 tests, 63 assertions.
- `php artisan test`: passed, 108 tests, 1061 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_140000_create_uas_training_learning_structure_tables`.

## Outstanding

- FR-TRN-002 compliance links between regulatory/organisational requirements, competency requirements and training records.
- Update workflows for course structure authoring after initial creation.
- Assessment attempt and certificate issuance workflows.
