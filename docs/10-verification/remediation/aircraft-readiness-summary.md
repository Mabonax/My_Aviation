# Aircraft Compliance Readiness Summary Verification

Date: 2026-09-12

## Scope

This slice introduced a derived readiness summary for physical aircraft records.

## Implemented

- Added `AircraftReadinessSummary` as a shared application query for web and API.
- Derived green, amber and red readiness from existing aircraft records rather than storing manual readiness state.
- Included catalogue model linkage, operational serviceability, registration validity, UASLA/RLA approval validity, open defects and compatible battery health.
- Added readiness output to physical aircraft detail presenter and operator-scoped aircraft list payloads.
- Rendered an aircraft detail readiness panel with per-control explanations.
- Added readiness status to the aircraft list for scan-friendly fleet review.
- Extended aircraft API detail/list responses with the same readiness payload.

## Rules

- Red means not ready because at least one blocking control failed.
- Amber means review is required but no blocking control failed.
- Green means all current controls passed.
- Missing catalogue linkage is amber because legacy aircraft remain supported but require review.
- Missing or expired registration and missing or expired UASLA/RLA approval are red.
- Blocked operational states such as grounded, unserviceable, suspended, de-registered, sold and flight-restricted are red.
- Open serviceability-impacting defects are red; open non-blocking defects are amber.
- No linked compatible batteries is amber; linked batteries with no serviceable battery are red; mixed serviceable and watch batteries are amber.

## Verification

- `php artisan test tests/Feature/Uas/AircraftCatalogueOnboardingTest.php`: passed; 10 tests, 95 assertions.
- `php artisan test tests/Feature/Uas/ApiV1FoundationTest.php tests/Feature/Uas/RemediationOperatorTenancyTest.php tests/Feature/Uas/AircraftCatalogueOnboardingTest.php`: passed; 25 tests, 198 assertions.
- `php artisan test --compact`: passed; 203 tests, 2056 assertions.
- `npm.cmd run build`: passed.
- `php artisan route:list --path=aircraft`: passed; aircraft web/API routes unchanged and available.
- `php artisan route:list --path=api`: passed; 10 API routes registered.

## Remaining Gaps

- Readiness is derived at request time; no historical readiness snapshots exist yet.
- Maintenance programme, component life limits and package-instantiated batteries/components are not yet included.
- Mission release gate has not yet been refactored to consume this aircraft summary directly.
- Flutter aircraft readiness UI remains future work.
