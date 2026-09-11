# FR-TRK-001 - Flight Tracks

## Requirement

Phase 2 shall support mission flight tracks as operational evidence. The implementation must retain mission-bound telemetry/track data with source and traceability context.

## Implementation

- Added `uas_flight_tracks` for mission-bound flight track evidence.
- Stores source type, track reference, start/end timestamps, point list, point count, computed total distance, maximum altitude, anomalies, notes and capturer.
- Added `UasFlightTrack` model and `UasMission::flightTracks()` relationship.
- Added `FlightTrackSummariser` for coordinate normalization, distance calculation and max-altitude derivation.
- Added `RecordFlightTrack` action with FR-TRK-001 audit evidence.
- Added `MissionTrackReport` query for mission show-page summaries.
- Added nested mission flight track routes, request validation, controller and Inertia capture form.
- Added mission show-page flight track summary panel and Add Track action.

## Verification

- PHP syntax checks for FR-TRK-001 migration, model, service, action, query, controller, request, mission controller and feature test: passed.
- PHP syntax pass over app/Domains/Uas, app/Providers, routes, tests/Feature/Uas and database/migrations: passed, 111 files.
- `php artisan test --filter=Phase2FlightTrackTest`: passed, 4 tests, 49 assertions.
- `php artisan test`: passed, 65 tests, 418 assertions.
- `npm.cmd run build`: passed and emitted flight track capture bundle `create-CysBeb-s.js`.
- `php artisan route:list --path=tracks`: registered create and store routes.

## Status

`IMPLEMENTED`

## Local Migration Note

`php artisan migrate --force` could not complete against the local MySQL database because the MySQL service refused the connection on `127.0.0.1:3306`. The migration is covered by the test database refresh path, but local MySQL apply remains an environment verification gap until the database service is running.
