# Post-Flight Propagation

Date: 2026-09-12

## Scope

This remediation slice connects completed missions to the regulated post-flight records that already exist in the platform:

- pilot logbook entries;
- aircraft flight folios;
- mission battery usage summaries;
- post-flight checklist evidence;
- mission defects and follow-up indicators.

The change preserves ADR-004 by keeping pilot logbook and aircraft flight folio records separate while allowing one completed mission to feed both records idempotently.

## Implementation Notes

- Add nullable mission linkage to pilot log entries and aircraft flight folios so repeated propagation updates the same records instead of duplicating them.
- Add mission-level propagation state and immutable evidence payload fields.
- Add mission actual takeoff, landing, calculated duration, completion timestamp and closure declaration fields.
- Only allow propagation for missions in `completed` or `post_flight_review` lifecycle states.
- Require a latest post-flight checklist that is not blocked.
- Summarise existing battery usage into the folio without incrementing battery cycle counters again.
- Carry defects, checklist exceptions and track/battery IDs into the propagated evidence payload.
- Reject client-provided derived values such as generated record IDs, readiness status and actual duration.
- Treat the first successful closure as immutable; repeated requests are rejected at the request layer and the action returns existing evidence without rewriting propagated records.

## Implemented

- Added `uas_mission_id` links to `pilot_log_entries` and `aircraft_flight_folios` with unique indexes for idempotent propagation.
- Added mission `post_flight_propagation_state`, `post_flight_propagated_at` and `post_flight_propagation_results`.
- Added mission actuals: `actual_takeoff_at`, `actual_landing_at`, `actual_flight_duration_minutes`, `completed_at` and `post_flight_declaration`.
- Added `PropagatePostFlightRequest` for web/API closure validation and trusted-derived-value rejection.
- Added `PropagatePostFlightRecords` to create/update the pilot logbook entry and aircraft flight folio from completed mission data.
- Added `PostFlightPropagationSummary` for web/API read models.
- Added web `POST /missions/{mission}/post-flight-propagation`.
- Added API V1 `GET` and `POST /api/v1/missions/{mission}/post-flight-propagation`.
- Added a mission-detail propagation panel showing actuals, declarations, logbook/folio IDs, battery cycles, tracks and open defects.

## Closure Lifecycle

The current implemented close-out path uses the existing lifecycle names:

1. Mission is released through release readiness.
2. Mission progresses through flight execution outside this slice.
3. Mission reaches `completed`.
4. Operator submits actual takeoff, actual landing and post-flight declarations.
5. Server calculates actual duration and writes generated pilot logbook and aircraft folio records.
6. Mission moves to `post_flight_review` with immutable propagation evidence.

The system does not yet model a separate `landed` state.

## Verification

- `php -l app/Domains/Uas/Missions/Application/Actions/PropagatePostFlightRecords.php`: passed.
- `php -l app/Domains/Uas/Missions/Application/Queries/PostFlightPropagationSummary.php`: passed.
- `php -l app/Domains/Uas/Missions/Http/Requests/PropagatePostFlightRequest.php`: passed.
- `php -l database/migrations/2026_09_12_140000_add_post_flight_propagation_fields.php`: passed.
- `php -l database/migrations/2026_09_13_080000_add_mission_operational_actuals.php`: passed.
- `composer dump-autoload`: passed; generated optimized autoload files containing 7737 classes.
- `php artisan migrate --force`: passed; applied `2026_09_12_140000_add_post_flight_propagation_fields`.
- `php artisan migrate --force`: passed; applied `2026_09_13_080000_add_mission_operational_actuals`.
- `php artisan route:list --path=missions`: passed; 27 matching mission/GIS mission routes registered.
- `php artisan route:list --path=post-flight-propagation`: passed; 3 web/API propagation routes registered.
- `php artisan route:list --path=api`: passed; 14 API routes registered.
- `php artisan test --filter=MissionPostFlightPropagationTest`: passed; 5 tests, 57 assertions after actuals/closure validation.
- Related mission/checklist/battery/defect/aircraft/API suite: passed; 51 tests, 513 assertions.
- `php artisan test --compact`: passed; 217 tests, 2169 assertions.
- `npm.cmd run build`: passed.
- Conflict marker scan: passed; no matches under app, database, resources, routes, tests or docs.
- `git diff --check`: passed with CRLF normalization warnings only.

## Remaining Gaps

- Mobile/offline mission close-out is not implemented.
- Maintenance programme component counters and release-to-service workflows remain future slices.
- Corrective actions are not yet first-class records.
- A separate mission execution state machine for `in_flight` and `landed` remains future work.
