# FR-MIS-001 / FR-MIS-002 - Mission Planning Verification

## Requirement

Phase 2 starts with governed mission planning and lifecycle control.

FR-MIS-001 stores mission purpose, client/project, location, coordinates, mission polygon, operation category, aircraft, pilot, observers/crew, planned date/time, maximum altitude, planned distance, VLOS/EVLOS/BVLOS mode, day/night operation, weather, airspace assessment, approvals, risk assessment and emergency arrangements.

FR-MIS-002 defines the mission lifecycle from draft through planning, compliance review, approval, flight execution, post-flight review, closure and cancellation.

## Implementation Evidence

- Migration: `database/migrations/2026_09_10_090000_create_uas_missions_table.php`
- Model: `app/Domains/Uas/Missions/Domain/Models/UasMission.php`
- Lifecycle enum/service: `MissionLifecycleState`, `MissionLifecycle`
- Release gate service: `MissionReleaseGate`
- Action/DTO/query layer: `app/Domains/Uas/Missions/Application/*`
- Policy: `app/Domains/Uas/Missions/Domain/Policies/MissionPolicy.php`
- Request/controller: `app/Domains/Uas/Missions/Http/*`
- UI: `resources/js/pages/missions/*`
- Route: `Route::resource('missions', MissionController::class)->only(['index', 'create', 'store', 'show'])`
- Test: `tests/Feature/Uas/Phase2MissionTest.php`

## Compliance Gate Evidence

The initial release gate distinguishes regulatory prohibitions from internal policy controls:

- Regulatory checks: pilot assignment, active pilot profile, RPC status, medical status, aircraft serviceability, aircraft registration and UASLA approval.
- Internal policy check: risk assessment capture.
- Result states: `green`, `amber`, `red`.

## Remediation Update

Mission release readiness is now verified in `docs/10-verification/remediation/mission-compliance-release-readiness.md`.

The mission release gate now consumes `MissionComplianceSummary`, which consumes `AircraftReadinessSummary` instead of duplicating aircraft readiness rules. Green and amber releases are server-side actions with audit evidence; red releases are rejected by backend validation.

## Authorization Evidence

Mission routes use `MissionPolicy` and require UAS role permissions:

- `missions.view`
- `missions.create`
- `missions.update`

Delete is denied for Phase 2 mission records.

## Verification Commands

- PHP syntax pass over app/Domains/Uas, routes, tests/Feature/Uas and database/migrations: passed.
- php artisan migrate --force: applied `2026_09_10_090000_create_uas_missions_table` locally.
- php artisan test --filter=Phase2MissionTest: passed, 4 tests, 23 assertions.
- php artisan test: passed, 40 tests, 155 assertions.
- npm.cmd run build: passed and included mission pages in the Vite manifest.

## Status

`VERIFIED`

This slice starts Phase 2. It does not complete Phase 2 because maps, aviation overlays, spatial rule evaluation, versioned checklists, crew workflow, tracks, battery management and defects remain pending.
