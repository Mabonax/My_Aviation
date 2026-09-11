# FR-DEF-001 - Defect Management

## Requirement

Defects may originate from pre-flight, in-flight, post-flight, maintenance or inspection sources. Defects shall capture severity and serviceability impact, including automatic aircraft unavailability for grounding defects until an authorised return-to-service workflow is added.

## Implementation

- Added `uas_aircraft_defects` for aircraft-bound defect reports with optional mission link.
- Added defect source, severity, status, serviceability impact, title, description, immediate action, reporter, timestamp, evidence and regulatory traceability fields.
- Added `UasAircraftDefect` model plus aircraft and mission relationships.
- Added `DefectServiceabilityImpact` mapping for documented sources and severities.
- Added `ReportAircraftDefect` action that writes FR-DEF-001 audit evidence and updates aircraft serviceability for maintenance-required, flight-restricted and grounding severities.
- Added defect list and mission defect report queries.
- Added top-level defect routes and mission-scoped defect routes with Inertia list/report pages.
- Added mission show-page defect summary and report action.

## Verification

- PHP syntax pass over app/Domains/Uas, app/Providers, routes, tests/Feature/Uas and database/migrations: passed, 133 files.
- `php artisan test --filter=Phase2DefectManagementTest`: passed, 5 tests, 84 assertions.
- `php artisan test`: passed, 75 tests, 589 assertions.
- `npm.cmd run build`: passed.
- `php artisan route:list --path=defects`: registered 5 defect inventory and mission reporting routes.

## Local Migration

- `php artisan migrate --force`: passed; applied pending Phase 2 migrations through `2026_09_10_170000_create_uas_aircraft_defects_table`.

## Outstanding

- Authorised defect rectification and return-to-service workflow.
- Maintenance release integration for defect closure.
- Defect edit, assignment and evidence upload workflows.