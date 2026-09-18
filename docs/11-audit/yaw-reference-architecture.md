# YAW Reference Architecture

Date: 2026-09-12

This target architecture incorporates the proven local patterns from AB4IRERP, the clinic backend and the Drhealth Flutter app while preserving YAW's aviation-compliance domain model.

## Target Architecture

```text
YAW Laravel
  app/Domains/Uas/{Domain}
    Models, Services, Policies
  app/Domains/Uas/{Application}
    Actions, Queries, DTOs, Presenters
  app/Domains/Uas/{Http}
    Web Controllers, Requests
    Api/V1 Controllers, Requests, Resources
  routes/web.php
    Inertia workspace adapters
  routes/api.php
    Versioned JSON adapters
  resources/js
    Inertia web workspaces
  yaw_flutter_app
    Online-first field app
```

Business rules should stay in domain/application services. Web controllers and API controllers should be thin adapters over the same actions, queries and policies.

## Web UI Reference Pattern

Adopt the AB4IRERP workspace convention for YAW:

| YAW workspace | Purpose | Reference pattern |
|---|---|---|
| Pilot Workspace | Self-service profile, licence wallet, certificates, logbook and training status. | AB4IR domain navigation + workspace pages. |
| Aircraft Workspace | Catalogue selection, aircraft inventory, registrations, approvals, batteries and defects. | AB4IR asset/project workspaces, adapted for UAS ownership. |
| Mission Workspace | Plan, validate, execute, close and review missions. | AB4IR project workspace pattern with stronger YAW release gates. |
| Operator Workspace | Operator profile, members, certificates, manuals and application packs. | AB4IR business-development/project workflow pattern, not its domain semantics. |
| Compliance Workspace | Registers, findings, evidence, controls, traceability and notifications. | AB4IR document library + YAW regulatory traceability. |
| GIS Workspace | Projects, missions, datasets, layers, features, opportunities and reports. | Current YAW Phase 5 structure plus report/evidence layers. |

## API Reference Pattern

YAW API V1 should be introduced after user-to-pilot ownership is secure.

```text
routes/api.php
  /api/v1
    public/system endpoints
    auth endpoints
    authenticated current-user endpoints
    scoped domain endpoints
```

Recommended baseline:

- `force.json` middleware for API routes.
- Sanctum token authentication for first-party mobile.
- Rate limits on auth and mutation routes.
- Object ownership policies on every current-user/domain endpoint.
- Explicit `JsonResource` or response DTO classes.
- Standard response envelope:

```json
{
  "success": true,
  "message": null,
  "data": {},
  "meta": {
    "contract_version": "v1"
  }
}
```

Errors should use:

```json
{
  "success": false,
  "message": "Human readable failure.",
  "errors": {},
  "error": {
    "code": "machine_readable_code",
    "details": {}
  },
  "meta": {
    "contract_version": "v1"
  }
}
```

## First API Slice

| Endpoint | Consumer | Purpose |
|---|---|---|
| `POST /api/v1/auth/login` | Flutter | Create token. |
| `POST /api/v1/auth/logout` | Flutter | Revoke token. |
| `GET /api/v1/me` | Flutter/web integrations | Resolve current user and role/persona. |
| `GET /api/v1/me/pilot` | Flutter | Resolve linked pilot profile. |
| `GET /api/v1/me/compliance` | Flutter | Return explainable pilot compliance summary. |
| `GET /api/v1/me/certificates` | Flutter | Return certificate/licence wallet data. |
| `GET /api/v1/me/notifications` | Flutter | Return current-user compliance notifications. |

Do not expose broad aircraft, mission, operator or GIS lists until ownership and operator membership are enforced.

## Flutter Reference Pattern

The YAW Flutter app should use the Drhealth app as an architecture reference only:

```text
lib/
  app/router.dart
  shared/config/app_config.dart
  shared/network/providers.dart
  shared/models/api_envelope.dart
  shared/storage/secure_token_storage.dart
  features/
    auth/
    pilot_profile/
    compliance/
    certificates/
    aircraft/
    missions/
    checklists/
    logbook/
    notifications/
    learning/
```

Recommended conventions:

- `dioProvider` owns base URL, JSON headers, bearer token and structured error mapping.
- `ApiEnvelope<T>` mirrors the Laravel response envelope.
- Feature services contain HTTP calls; screens do not call Dio directly.
- Riverpod providers expose async state and commands.
- `go_router` redirects based on auth, onboarding and pilot-profile state.
- Secure storage stores tokens and field-session metadata.

## Evidence And Documents

YAW should introduce a governed evidence vault before expanding report exports. The target should adapt AB4IR's document library ideas:

- Versioned files.
- Domain object links.
- Activity/audit log.
- Approval/review state where required.
- Access policy checks.
- Expiry and regulatory-source metadata.

YAW evidence should link to compliance findings, GIS features, mission releases, operator packs, training records and regulatory forms without duplicating file metadata in each domain table.

## Contract Testing Standard

API work is not complete until it has:

- Feature tests for authentication, authorization and success/error payload shape.
- Contract fixtures or snapshots for every Flutter-facing endpoint.
- Tests proving cross-user/operator isolation.
- Tests proving `meta.contract_version` remains present.
- Flutter service tests using representative API payloads.

## Immediate Architecture Decisions

| Decision | Recommendation |
|---|---|
| First implementation after benchmark | User-to-pilot self-service ownership. |
| First API style | Clinic-inspired versioned JSON API with response envelope. |
| First mobile scope | Online-first pilot compliance and licence wallet. |
| First evidence architecture | UAS evidence vault adapted from AB4IR document/version/access patterns. |
| First UI improvement | AB4IR-inspired domain workspace navigation for pilot/product journeys. |
