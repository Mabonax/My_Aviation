# FR-CREW-001 - Crew Management

## Requirement

Mission records shall support observers/crew. Phase 2 requires a dedicated crew management workflow beyond the mission JSON placeholder.

## Implementation

- Added `uas_mission_crew_members` for dedicated mission crew assignments.
- Stores crew role, display name, contact details, optional linked pilot/user, briefing status, competency status, acceptance status, emergency contact, notes and assigner.
- Added `UasMissionCrewMember` model and `UasMission::crewMembers()` relationship.
- Added `AssignMissionCrewMember` action with FR-CREW-001 audit evidence.
- Added `MissionCrewReport` query with summary counts for total, briefed, accepted, competency verified and attention required.
- Added mission crew request, controller and nested mission routes.
- Added crew assignment Inertia form and mission show-page crew summary panel.

## Verification

- PHP syntax checks for FR-CREW-001 migration, model, action, query, controller, request, mission controller and feature test: passed.
- PHP syntax pass over app/Domains/Uas, app/Providers, routes, tests/Feature/Uas and database/migrations: passed, 103 files.
- `php artisan test --filter=Phase2CrewManagementTest`: passed, 4 tests, 53 assertions.
- `php artisan test`: passed, 61 tests, 369 assertions.
- `npm.cmd run build`: passed and emitted crew assignment bundle `create-Cv25p8To.js`.
- `php artisan route:list --path=crew`: registered create and store routes.

## Status

`IMPLEMENTED`

## Local Migration Note

`php artisan migrate --force` could not complete against the local MySQL database because the MySQL service refused the connection on `127.0.0.1:3306`. The migration is covered by the test database refresh path, but local MySQL apply remains an environment verification gap until the database service is running.
