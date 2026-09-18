# Aircraft Catalogue and Physical Onboarding Verification

Date: 2026-09-12

## Scope

This slice introduced a governed aircraft model catalogue and linked it to physical operator aircraft onboarding.

## Implemented

- Added `uas_manufacturers` and `uas_aircraft_models` for manufacturer/model catalogue records.
- Added optional `aircraft_model_id` and onboarding fields to `uas_aircraft` while preserving legacy manufacturer/model/serial fields.
- Added an idempotent `uas:import-aircraft-catalogue {path?}` command for JSON and CSV source files.
- Imported catalogue records from `public/uas_drone_catalogue.json`.
- Preserved source URLs, image source URLs, image licence status, media status, null technical values and `verified_at`.
- Added catalogue list/detail web screens and physical aircraft onboarding from selected catalogue models.
- Added `/api/v1/aircraft-catalogue`, `/api/v1/aircraft-catalogue/{aircraftModel}` and `/api/v1/aircraft/{aircraft}`.
- Extended physical aircraft API/list payloads with catalogue model summaries.
- Kept API responses inside the V1 envelope, including authorization failures.

## Architecture Notes

- Catalogue data is technical and source/provenance oriented.
- Physical aircraft data remains operator/asset oriented, including registration, serial, firmware, base, supplier and assignment.
- Image downloads are intentionally not performed; only source URL, licence status and media status are stored.
- Operator assignment uses the existing Phase B `uas_operator_aircraft` pivot.
- Legacy aircraft rows without `aircraft_model_id` remain readable and listable.

## Import Evidence

- First import: 13 manufacturers created, 26 models created, 0 rejected rows.
- Second import: 0 manufacturers created, 0 models created, 26 unchanged rows, 0 rejected rows.
- Manufacturer distribution after import:
  - AgEagle: 1
  - Autel Robotics: 4
  - BRINC: 1
  - Delair: 1
  - DJI: 10
  - Freefly Systems: 1
  - Inspired Flight: 1
  - JOUAV: 1
  - Parrot: 2
  - Quantum-Systems: 1
  - Skydio: 1
  - Teledyne FLIR: 1
  - Wingtra: 1
- Sample records confirmed:
  - Parrot ANAFI Ai: null MTOW preserved, verified, verified_at 2026-09-11.
  - DJI Matrice 350 RTK: MTOW 9.200 kg, verified, verified_at 2026-09-11.
  - DJI Matrice 400: MTOW 15.800 kg, verified, verified_at 2026-09-11.

## Verification

- `composer dump-autoload`: passed.
- `php artisan migrate --force`: passed; nothing to migrate after catalogue migration had run.
- `php artisan uas:import-aircraft-catalogue`: passed.
- `php artisan route:list --path=api`: passed; 10 API routes registered.
- `php artisan route:list --path=aircraft`: passed; catalogue, onboarding, show and API aircraft routes registered.
- `php artisan test tests/Feature/Uas/AircraftCatalogueOnboardingTest.php`: passed; 7 tests, 76 assertions.
- `php artisan test tests/Feature/Uas/ApiV1FoundationTest.php tests/Feature/Uas/RemediationOperatorTenancyTest.php tests/Feature/Uas/AircraftCatalogueOnboardingTest.php`: passed; 22 tests, 179 assertions.
- `php artisan test --compact`: passed; 200 tests, 2037 assertions.
- `npm.cmd run build`: passed.

## Remaining Gaps

- No battery/component package instantiation from catalogue data yet.
- No local media approval/download workflow yet.
- No OpenAPI document for the new aircraft catalogue API.
- Flutter aircraft selection/onboarding client remains future work.
