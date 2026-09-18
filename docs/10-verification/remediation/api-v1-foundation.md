# API V1 Foundation Verification

Date: 2026-09-12

## Scope

This slice introduced the first YAW REST API foundation after user-pilot ownership and operator tenancy were in place.

## Implemented

- Installed `laravel/sanctum`.
- Added `personal_access_tokens` migration for API token storage.
- Added `HasApiTokens` to `User`.
- Registered `routes/api.php` through Laravel bootstrap routing.
- Added `ApiResponse` as the stable V1 response envelope with `meta.contract_version`.
- Added API exception rendering for authentication, authorization and validation failures.
- Added `/api/v1/auth/login` and `/api/v1/auth/logout`.
- Added `/api/v1/me`.
- Added `/api/v1/me/pilot`.
- Added `/api/v1/me/operators`.
- Added `/api/v1/aircraft`.
- Added `/api/v1/aircraft/{aircraft}`.
- Added `/api/v1/aircraft-catalogue`.
- Added `/api/v1/aircraft-catalogue/{aircraftModel}`.
- Added `/api/v1/missions`.
- Added `/api/v1/missions/{mission}`.
- Added `/api/v1/missions/{mission}/compliance`.
- Added `/api/v1/missions/{mission}/post-flight-propagation` read/write endpoints.

## Contract Shape

```json
{
  "success": true,
  "message": null,
  "data": {},
  "errors": null,
  "error": null,
  "meta": {
    "contract_version": "v1.0"
  }
}
```

## Scoping

- `/api/v1/me/pilot` resolves through `CurrentPilotProfile`.
- `/api/v1/me/operators` returns active memberships for normal users and all accessible operators for global administrators.
- `/api/v1/aircraft` uses the Phase B operator-scoped aircraft query and now includes optional catalogue model summaries.
- `/api/v1/aircraft/{aircraft}` returns scoped physical aircraft detail.
- `/api/v1/aircraft-catalogue` exposes governed model catalogue list/detail data.
- `/api/v1/missions` uses the Phase B operator-scoped mission query.
- `/api/v1/missions/{mission}` returns scoped mission detail.
- `/api/v1/missions/{mission}/compliance` returns structured release-readiness controls.
- `/api/v1/missions/{mission}/post-flight-propagation` returns or writes scoped post-flight propagation state and evidence summary.

## Verification

- `composer dump-autoload`: passed and discovered `laravel/sanctum`.
- `php artisan migrate --force`: passed; applied `2026_09_12_120000_create_personal_access_tokens_table`.
- `php artisan route:list --path=api`: passed; 14 API routes registered after the aircraft catalogue, mission compliance and post-flight propagation extensions.
- `php artisan test tests\Feature\Uas\ApiV1FoundationTest.php`: passed; 6 tests, 47 assertions.
- `php artisan test --compact`: passed; 193 tests, 1961 assertions.
- `npm.cmd run build`: passed.

## Remaining Gaps

- API V1 currently exposes foundation/current-user endpoints plus read-only aircraft catalogue and scoped aircraft detail.
- No create/update aircraft, mission, checklist, document, notification, regulation, form, fee, GIS or training API endpoints yet, except the dedicated mission post-flight propagation write endpoint.
- No mobile device registration, token ability partitioning, rate-limit tuning or OpenAPI document yet.
- Flutter client remains unimplemented.
