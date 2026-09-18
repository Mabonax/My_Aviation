# Mission Compliance Release Readiness Verification

Date: 2026-09-12

## Scope

This slice introduced an explainable mission release-readiness summary and server-side release enforcement.

## Architecture

- `MissionComplianceSummary` is the shared mission readiness query for web/API/release workflows.
- `MissionReleaseGate` remains as the compatibility adapter for existing `release_gate_state` and `release_gate_results` consumers.
- Aircraft readiness is consumed from `AircraftReadinessSummary`; aircraft readiness rules are not duplicated in the mission layer.
- Mission list payloads use lightweight stored release-gate status to avoid full per-row compliance expansion.
- Mission detail and `/api/v1/missions/{mission}/compliance` compute the current full control set.

## Controls

- Aircraft readiness: delegates to `AircraftReadinessSummary`.
- Pilot readiness: checks active profile, valid medical state and RPC state from `PilotComplianceEvaluator`.
- Operator compliance: checks an assigned active operator.
- Airspace / geometry: consumes `MissionSpatialRuleEvaluator`.
- Checklist: requires the latest active pre-flight checklist to be completed, with blocked items preventing release.
- Approvals: requires mission approval evidence when airspace controls make it mandatory.
- Risk assessment: keeps the existing internal-policy amber warning when risk assessment is missing.

## Result Structure

The summary returns:

- `status`: green, amber or red.
- `label`: release-ready wording for the state.
- `blocking_count`.
- `warning_count`.
- `controls`: explainable per-control results.
- `evaluated_at`.

## Release Gating

- Green mission: can be released.
- Amber mission: can be released under current governance and stores warning evidence.
- Red mission: release is rejected server-side with validation errors.
- Release also requires the existing mission lifecycle to allow transition into `ready_for_flight`.

## API

- Added `GET /api/v1/missions/{mission}`.
- Added `GET /api/v1/missions/{mission}/compliance`.
- Mission list/detail payloads include compliance summary data.
- API routes use Sanctum, existing policies and the stable V1 `ApiResponse` envelope.
- Operator membership scoping prevents cross-operator mission compliance access.

## UI

- Mission detail now shows a prominent Mission Release Readiness panel.
- Each control shows status, explanation and optional review link.
- The release action button shows `Release Mission`, `Review & Release` or `Release Blocked` based on readiness.
- Mission index shows lightweight compliance state, blocking count and warning count.

## Release Evidence

`ReleaseMission` records `mission.released` audit entries with:

- mission id
- release actor
- released timestamp
- compliance status
- blocking count
- warning count
- aircraft readiness status
- serialized control snapshot

Historical release evidence is appended through audit entries and not silently overwritten.

## Catalogue Source Boundary

The public catalogue files remain in `public/`:

- `public/YAW_Drone_Master_Catalogue.xlsx`
- `public/uas_drone_catalogue.json`
- `public/uas_drone_catalogue.csv`

Mission compliance does not read these files. It uses governed database catalogue records after import.

## Verification

- `composer dump-autoload --no-scripts`: passed; generated optimized autoload files containing 7734 classes.
- `php artisan migrate --force`: passed; nothing to migrate.
- `php artisan route:list --path=missions`: passed; 24 routes registered including mission release and API mission compliance routes.
- `php artisan route:list --path=api`: passed; 12 API routes registered.
- `php artisan test tests/Feature/Uas/MissionComplianceReleaseReadinessTest.php --compact`: passed; 9 tests, 56 assertions.
- `php artisan test tests/Feature/Uas/Phase2MissionTest.php --compact`: passed; 6 tests, 35 assertions.
- `php artisan test tests/Feature/Uas/MissionComplianceReleaseReadinessTest.php tests/Feature/Uas/Phase2MissionTest.php tests/Feature/Uas/AircraftCatalogueOnboardingTest.php tests/Feature/Uas/ApiV1FoundationTest.php tests/Feature/Uas/RemediationOperatorTenancyTest.php --compact`: passed; 40 tests, 289 assertions.
- `php artisan test tests/Feature/Uas/Phase2PreFlightChecklistTest.php tests/Feature/Uas/Phase2SpatialRuleEvaluationTest.php tests/Feature/Uas/Phase2BatteryManagementTest.php tests/Feature/Uas/Phase2DefectManagementTest.php --compact`: passed; 18 tests, 247 assertions.
- `php artisan test --compact`: passed; 212 tests, 2112 assertions.
- `npm.cmd run build`: passed.
- `php -l` passed for new mission compliance/release PHP files.

## Remaining Gaps

- No mission release API write endpoint yet.
- No historical mission compliance snapshot table separate from audit entries.
- Mission release does not yet consume training competency, document vault evidence or post-flight propagation.
- Flutter mission compliance and release UI remain future work.

## Subsequent extension - 2026-09-15

The [aeronautical information integration](aeronautical-information-integration.md) adds an eighth control and immutable briefing/acknowledgement references to release evidence. Mission list summaries now calculate live compliance so source expiry/failure is visible. The seven-control/stored-list description above records the earlier slice, not the current release implementation.
