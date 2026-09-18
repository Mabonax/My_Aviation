# Aircraft Package Instantiation

Date: 2026-09-13

## Implementation Note

The current aircraft catalogue stores governed model-level performance and source metadata, while physical aircraft records already link to a catalogue model and battery records already support aircraft compatibility, cycle limits and readiness checks. A full maintenance programme is not yet modelled; only folio maintenance certification JSON and roadmap placeholders exist.

This slice therefore adds a conservative package-instantiation foundation:

- store catalogue-level package definitions for batteries, components and maintenance baseline guidance;
- instantiate physical batteries and component baseline records when an aircraft is onboarded from a catalogue model;
- make instantiation idempotent through physical-aircraft/package-item unique keys;
- expose package summaries on aircraft web/API payloads;
- surface package coverage in aircraft readiness without replacing existing battery, defect or approval rules;
- document maintenance as baseline guidance until first-class schedules/counters exist.

## Implemented

- Added catalogue package JSON fields on `uas_aircraft_models` for battery, component and maintenance baseline definitions.
- Added physical-aircraft package instantiation state, timestamp and result snapshot fields.
- Added package provenance to `uas_batteries` and a unique physical-aircraft/package-item guard.
- Added `uas_aircraft_components` for instantiated component baselines, life-limit hints and evidence references.
- Added `InstantiateAircraftPackage` and wired it into `CreatePhysicalAircraft`.
- Kept instantiation idempotent: repeat calls return the existing result snapshot without creating duplicate assets or audit entries.
- Extended aircraft catalogue import and presenters so package metadata is API/web visible.
- Added aircraft onboarding and detail UI package summaries.

## Verification

- `php -l app/Domains/Uas/Aircraft/Application/Actions/InstantiateAircraftPackage.php`: passed.
- `php -l app/Domains/Uas/Aircraft/Application/Actions/ImportAircraftCatalogue.php`: passed.
- `php -l app/Domains/Uas/Aircraft/Application/Actions/CreatePhysicalAircraft.php`: passed.
- `php -l database/migrations/2026_09_13_090000_create_aircraft_package_instantiation_tables.php`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_13_090000_create_aircraft_package_instantiation_tables`.
- `php artisan test tests/Feature/Uas/AircraftCatalogueOnboardingTest.php --compact`: passed; 12 tests, 113 assertions.
- Related aircraft/mission/battery/API suite: passed; 37 tests, 360 assertions.
- `php artisan test --compact`: passed; 219 tests, 2187 assertions.
- `npm.cmd run build`: passed.
- `php artisan route:list --path=aircraft`: passed; 11 aircraft-related web/API routes registered.
- `php artisan route:list --path=api`: passed; 14 API routes registered.
- `git diff --check`: passed with CRLF normalization warnings only.
- Conflict marker scan: passed; no matches under app, database, resources, routes, tests or docs.

## Remaining Limits

- Maintenance remains baseline guidance only; first-class maintenance programmes, due calculations and release-to-service workflows are future work.
- Flutter/mobile onboarding does not yet consume the package-instantiation summary.
- Existing public catalogue data does not yet carry vendor-specific package definitions; importer support exists for governed future rows.
