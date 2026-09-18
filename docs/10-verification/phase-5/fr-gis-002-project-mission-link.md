# FR-GIS-002 - GIS Project Mission Link

## Status

`IMPLEMENTED`

## Scope

Implements the Phase 5 hierarchy link from GIS Project to Mission described in functional requirements section 32 and Phase 5 section 41. GIS project mission assignments preserve separation between geospatial deliverable intent and the existing Phase 2 flight-operations mission record.

## Repository Evidence

- `database/migrations/2026_09_12_080000_create_uas_gis_project_missions_table.php`
- `app/Domains/Uas/Geography/Domain/Models/UasGisProjectMission.php`
- `app/Domains/Uas/Geography/Domain/Services/GisProjectMissionAssignment.php`
- `app/Domains/Uas/Geography/Application/Actions/AssignMissionToGisProject.php`
- `app/Domains/Uas/Geography/Application/Queries/GisProjectMissionOptions.php`
- `app/Domains/Uas/Geography/Http/Controllers/GisProjectMissionController.php`
- `resources/js/pages/geography/projects/missions/create.tsx`
- `tests/Feature/Uas/Phase5GisProjectMissionAssignmentTest.php`

## Behaviour

- Links one operational UAS mission to one GIS project assignment.
- Captures mapping objective, capture plan, expected outputs, field verification requirement, evidence notes and assignment status.
- Exposes only unassigned missions as assignable options.
- Records FR-GIS-002 audit evidence when a mission is assigned to a GIS project.
- Shows assigned mapping missions on the GIS project detail page.

## Verification

- `php artisan test --filter=Phase5GisProjectMissionAssignmentTest --compact`: passed, 5 tests, 50 assertions.
- `php artisan route:list --path=gis-projects`: passed, 7 routes registered.
- PHP syntax checks for FR-GIS-002 model, service, action, query, controller, request and feature test: passed.
- `php artisan test --compact`: passed, 160 tests, 1698 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_12_080000_create_uas_gis_project_missions_table`.

## Outstanding

- Dataset, orthomosaic, spatial layer, feature, opportunity/finding, evidence and report records after mission assignment.
- Mission assignment status update workflow after initial assignment.
- Rendered map/canvas verification for assigned mission geometry and project boundary alignment.
