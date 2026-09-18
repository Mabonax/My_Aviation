# FR-OM-004 - Training Trigger

## Requirement

An Operations Manual amendment may generate mandatory training and competency requirements.

## Implementation

- Added `uas_operations_manual_training_requirements` for revision-bound amendment training triggers.
- Added `UasOperationsManualTrainingRequirement` model and `UasOperationsManualRevision::trainingRequirements()` relationship.
- Added `ManualTrainingControl` type/status definitions for internal competency, safety briefing, security awareness and regulated-training review triggers.
- Added create action with FR-OM-004 audit evidence and regulatory traceability defaults.
- Added training options and manual revision training report queries.
- Added nested manual revision training requirement create/store routes.
- Added Inertia training requirement form and manual revision show-page training trigger summary/list.
- Repaired local MySQL migration key naming with explicit short foreign-key and index names.

## Verification

- PHP syntax pass over migration, model, action, controller, request and feature test: passed.
- `php artisan route:list --path=training-requirements`: registered 2 training requirement routes.
- `php artisan test --filter=Phase3ManualTrainingTriggerTest`: passed, 4 tests, 50 assertions.
- `php artisan test`: passed, 103 tests, 998 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_130000_create_uas_operations_manual_training_requirements_table` after shortening key names.

## Outstanding

- Phase 4 full training/competency module integration.
- Assignment/completion workflow for training requirements beyond trigger capture.
- Notification/escalation workflow for overdue training requirements.
