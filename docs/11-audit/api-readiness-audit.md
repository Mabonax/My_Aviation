# API Readiness Audit

Date: 2026-09-12

## API Status

| Area | Status | Evidence |
|---|---|---|
| Route file | IMPLEMENTED FOUNDATION | `routes/api.php` is registered through `bootstrap/app.php`. |
| API routes | IMPLEMENTED FOUNDATION | 10 `/api/v1` routes exist for auth, current user, pilot, operators, aircraft, aircraft catalogue and missions. |
| API controllers | IMPLEMENTED FOUNDATION | `App\Domains\Uas\Api\Http\Controllers\V1`. |
| API Resources/DTOs | PARTIAL | API response envelope exists; endpoints reuse current presenters/queries rather than dedicated resources for every domain. |
| API tests | IMPLEMENTED FOUNDATION | `ApiV1FoundationTest` covers auth, envelope, current-user and scoped list contracts. |
| Authentication | IMPLEMENTED FOUNDATION | Sanctum personal access tokens support `/api/v1/auth/login` and `/api/v1/auth/logout`. |
| Versioning | IMPLEMENTED FOUNDATION | `/api/v1` route prefix and `meta.contract_version = v1.0`. |
| OpenAPI/Swagger | NOT IMPLEMENTED | No API documentation artifacts found. |
| Tenancy dependency | READY FOR FIRST API SLICE | `CurrentOperatorContext`, `uas_operator_memberships`, `uas_operator_pilots`, `uas_operator_aircraft`, `uas_missions.uas_operator_id` |

## Required Status Output

```text
API STATUS
----------
Authentication: IMPLEMENTED FOUNDATION with Sanctum personal access tokens.
Pilot API: IMPLEMENTED FOUNDATION for current user's linked pilot profile.
Aircraft API: IMPLEMENTED FOUNDATION for operator-scoped list/detail with catalogue summaries and readiness checks.
Aircraft Catalogue API: IMPLEMENTED FOUNDATION for list/detail and filters.
Mission API: IMPLEMENTED FOUNDATION for operator-scoped list/detail and compliance readiness.
Compliance API: NOT IMPLEMENTED
Regulation API: NOT IMPLEMENTED
Forms API: NOT IMPLEMENTED
Fees API: NOT IMPLEMENTED
Documents API: NOT IMPLEMENTED
Notification API: NOT IMPLEMENTED
GIS API: NOT IMPLEMENTED
```

## Architecture Recommendation

The codebase is well-positioned for API adapters because most web controllers already call application actions, queries, policies and presenters. API V1 should not duplicate web business logic. It should introduce:

- `routes/api.php` with `/api/v1`.
- Token authentication, preferably Laravel Sanctum for first-party mobile.
- A clinic-inspired response envelope with `success`, `message`, `data`, `errors`, `error` and `meta.contract_version`.
- Stable API Resources or explicit response DTOs.
- API controllers under domain modules or a consistent `Api\V1` adapter namespace.
- Contract tests for every mobile-facing endpoint.
- Shared application queries/actions for web and API.

Target shape:

```text
Laravel Domain/Application
    -> Inertia Web Adapter
    -> REST API V1 Adapter
        -> Flutter
```

## Recommended First API Contract

Do not implement all endpoints at once. Start with the pilot journey:

| Endpoint | Priority | Backing capability |
|---|---|---|
| `POST /api/v1/auth/login` | P0 | IMPLEMENTED |
| `POST /api/v1/auth/logout` | P0 | IMPLEMENTED |
| `GET /api/v1/me` | P0 | IMPLEMENTED |
| `GET /api/v1/me/pilot` | P0 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/me/operators` | P0 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/me/compliance` | P1 | Pilot compliance summary query to create |
| `GET /api/v1/me/certificates` | P1 | `PilotCertificate` |
| `GET /api/v1/aircraft` | P1 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/aircraft/{aircraft}` | P1 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/aircraft-catalogue` | P1 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/aircraft-catalogue/{aircraftModel}` | P1 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/missions` | P1 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/missions/{mission}` | P1 | IMPLEMENTED FOUNDATION |
| `GET /api/v1/missions/{mission}/compliance` | P1 | IMPLEMENTED FOUNDATION |
| `POST /api/v1/missions/{mission}/preflight` | P1 | Existing checklist action |
| `POST /api/v1/missions/{mission}/postflight` | P1 | Existing checklist action |
| `GET /api/v1/regulatory/requirements` | P2 | Regulatory requirement presenter |
| `GET /api/v1/regulatory/forms` | P2 | Regulatory form presenter |
| `GET /api/v1/regulatory/fees` | P2 | Regulatory fee presenter |
| `GET /api/v1/notifications` | P2 | Compliance notifications scoped to user |

Former blocking dependency: object ownership/tenancy is now defined for user/pilot/operator/aircraft/mission web-backed records. API resources still need their own contract tests before any endpoint is considered implemented.

## Remediation Phase B API Readiness

Remediation Phase B did not create API routes. API V1 Foundation now uses the Phase B scoping primitives:

- `/api/v1/me/operators`: can resolve active operator memberships for the authenticated user.
- `/api/v1/aircraft`: can list aircraft assigned to accessible active operators.
- `/api/v1/missions`: can list missions tied to accessible active operators.

These endpoints are now foundation-level implemented and contract-tested. Write endpoints and broader domain APIs remain unimplemented.

## API V1 Foundation Update

The first API foundation is implemented:

- `laravel/sanctum` is installed.
- `personal_access_tokens` stores mobile/API tokens.
- `routes/api.php` is registered.
- API responses use a stable envelope with `meta.contract_version`.
- Authentication, validation and authorization failures render through the same envelope for `/api/*`.
- Current-user pilot/operator/aircraft/mission list endpoints are contract-tested.

## Aircraft Catalogue Update

The aircraft catalogue and physical aircraft detail API is implemented at foundation level:

- `/api/v1/aircraft-catalogue` returns filtered catalogue model summaries.
- `/api/v1/aircraft-catalogue/{aircraftModel}` returns catalogue detail and provenance.
- `/api/v1/aircraft/{aircraft}` returns scoped physical aircraft detail with optional catalogue model summary.
- `/api/v1/aircraft` and `/api/v1/aircraft/{aircraft}` include derived readiness status, blocking reasons, review reasons and per-control checks.
- API authorization failures under `/api/*` are rendered through the V1 envelope.
- `AircraftCatalogueOnboardingTest` covers idempotent import, API filtering/detail, deprecated catalogue records, physical aircraft links and unauthorized aircraft access.

## Aircraft Readiness Update

Aircraft readiness is now available through existing aircraft API responses:

- The readiness payload is derived from catalogue linkage, aircraft serviceability, registration, UASLA/RLA approval, defects and compatible batteries.
- The API exposes green, amber and red outcomes without storing manual readiness state.
- Mission API integration remains future work; mission release gates should consume this shared summary rather than duplicating aircraft checks.

## Mission Compliance Update

Mission compliance API is implemented at foundation level:

- `/api/v1/missions` returns operator-scoped mission rows with lightweight compliance status.
- `/api/v1/missions/{mission}` returns mission detail through the existing V1 envelope.
- `/api/v1/missions/{mission}/compliance` returns the full structured mission compliance controls.
- Mission compliance is tenant-scoped through the existing mission policy and `CurrentOperatorContext`.
- No mission release/write API exists yet; web release is enforced server-side through `ReleaseMission`.

## Reference Benchmark

`C:\xampp\htdocs\gperp-clinic` provides the best local API reference. Its `routes/api.php` groups mobile endpoints under `/api/mobile/v1`, applies JSON/mobile/persona middleware, names routes under `api.mobile.v1.*`, and routes authenticated patient/practitioner workflows through API controllers. Its `MobileApiResponse` helper standardises successful and error payloads and adds `meta.contract_version`.

YAW should adapt that structure, not its healthcare route names. The first YAW API should be small, current-user scoped and contract tested before any broad domain list endpoints are exposed.

## Security Concerns

- Current web policies mostly check broad role permissions. API list endpoints must not leak data across operators or pilots.
- API responses must not expose raw Eloquent models.
- Mobile token/session handling needs rate limits, revocation and device identity before production.
- Regulatory/admin endpoints should be read-only for pilot mobile V1 unless specific persona requirements exist.
