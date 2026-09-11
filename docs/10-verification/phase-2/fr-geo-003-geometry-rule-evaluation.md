# FR-GEO-003 - Geometry Rule Evaluation

## Requirement

The compliance engine shall evaluate mission geometry against configured regulatory spatial rules and flag conditions requiring review or authorisation.

## Implementation

- Added `MissionSpatialRuleEvaluator` to evaluate mission geometry against active aviation overlay zones.
- Evaluates mission take-off, landing, centre point, polygon, route and radius-derived bounding area where available.
- Returns explicit states: `insufficient_geometry`, `clear`, `review_required` and `authorisation_required`.
- Maps configured overlay types to rule outcomes while preserving source publisher, source URL, source version and authoritative marker for each match.
- Added mission show-page output for spatial rule review results beside the operational release gate.
- Added FR-GEO-003 feature tests for authorisation flags, insufficient geometry and mission show-page exposure.

## Source Boundary

This rule evaluator only uses configured `aviation_overlay_zones` from FR-GEO-002. It does not claim full national aviation data coverage until official SACAA/ATNS datasets are imported and reconciled.

## Verification

- PHP syntax pass over app/Domains/Uas, app/Providers, routes, tests/Feature/Uas and database/migrations: passed, 83 files.
- `php artisan test --filter=Phase2SpatialRuleEvaluationTest`: passed, 3 tests, 24 assertions.
- `php artisan test`: passed, 47 tests, 212 assertions.
- `npm.cmd run build`: passed and emitted updated mission show bundle `show-Do0HlOld.js`.

## Status

`IMPLEMENTED`