# FR-GEO-001 - Mission Map Verification

## Requirement

Mission planning must support mission polygon, take-off point, landing point, flight route, flight radius, location search and coordinates.

## Implementation Evidence

- Migration: `database/migrations/2026_09_10_100000_add_geometry_fields_to_uas_missions_table.php`
- Geometry service: `app/Domains/Uas/Missions/Domain/Services/MissionGeometry.php`
- Mission model/DTO/request/presenter fields: `location_search_query`, `takeoff_point`, `landing_point`, `mission_polygon`, `flight_route`, `flight_radius_m`
- React geometry editor/preview: `resources/js/components/uas/mission-geometry-editor.tsx`
- Mission create/show integration: `resources/js/pages/missions/create.tsx`, `resources/js/pages/missions/show.tsx`
- Tests: `tests/Feature/Uas/Phase2MissionTest.php`

## Design Decision

No external basemap provider is introduced in this slice. The implementation captures and previews operational geometry directly in the mission workflow. Authoritative aviation overlays and spatial rule evaluation remain separate requirements under FR-GEO-002 and FR-GEO-003.

## Verification Commands

- PHP syntax pass over app/Domains/Uas, routes, tests/Feature/Uas and database/migrations: passed.
- php artisan migrate --force: applied `2026_09_10_100000_add_geometry_fields_to_uas_missions_table` locally.
- php artisan test --filter=Phase2MissionTest: passed, 6 tests, 35 assertions.

## Status

`IMPLEMENTED`

FR-GEO-001 is implemented at repository level. It is not a claim that Google Maps, SACAA airspace data, controlled-airspace overlays or spatial regulatory rule evaluation are complete.
