# FR-GIS-001 - GIS Project Register

## Status

`IMPLEMENTED`

## Scope

Implements the first Phase 5 GIS project spine described in functional requirements section 32 and Phase 5 section 41. The source specification names the GIS project capability without assigning a formal requirement code, so `FR-GIS-001` is used for traceability.

## Repository Evidence

- `database/migrations/2026_09_11_210000_create_uas_gis_projects_table.php`
- `app/Domains/Uas/Geography/Domain/Models/UasGisProject.php`
- `app/Domains/Uas/Geography/Domain/Services/GisProjectLifecycle.php`
- `app/Domains/Uas/Geography/Application/Actions/CreateGisProject.php`
- `app/Domains/Uas/Geography/Application/Actions/TransitionGisProjectLifecycle.php`
- `app/Domains/Uas/Geography/Application/Queries/ListGisProjects.php`
- `app/Domains/Uas/Geography/Http/Controllers/GisProjectController.php`
- `resources/js/pages/geography/projects/*`
- `tests/Feature/Uas/Phase5GisProjectRegisterTest.php`

## Behaviour

- Captures GIS project code, type, stakeholder, area, centroid, boundary, source reference, source version, data-governance notes, required evidence and responsible role.
- Keeps GIS project records independent of general flight operations while preserving a future link point for missions, datasets, layers, findings, evidence and reports.
- Implements the documented workflow spine: plan, authorise, fly, capture, process, map, analyse, report, closed and cancelled.
- Enforces one-step lifecycle transitions, with cancellation blocked after closure.
- Records FR-GIS-001 audit evidence when GIS projects are created or transitioned.
- Exposes authenticated Inertia pages for listing, creating, viewing and transitioning GIS projects.

## Verification

- `php artisan test --filter=Phase5GisProjectRegisterTest --compact`: passed, 6 tests, 77 assertions.
- `php artisan route:list --path=gis-projects`: passed, 5 routes registered.
- PHP syntax checks for FR-GIS-001 model, service, actions, controller and feature test: passed.
- `php artisan test --compact`: passed, 155 tests, 1648 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_210000_create_uas_gis_projects_table`.

## Outstanding

- Direct GIS project to mission assignment workflow.
- Geospatial dataset, orthomosaic, spatial layer, feature, opportunity/finding, evidence and report entities.
- Rendered map/canvas verification for project boundaries and derived layers.
- Production data-governance review for imagery, location evidence and client deliverables.
