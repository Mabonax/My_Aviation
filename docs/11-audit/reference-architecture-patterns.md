# Reference Architecture Patterns

Date: 2026-09-12

This benchmark compares YAW against adjacent local repositories as reference implementations only. The purpose is not to merge products or copy business logic, but to identify proven architecture, workflow, API and mobile patterns that YAW should adopt or adapt.

## Reference Repositories Reviewed

| Repository | Role in benchmark | Evidence reviewed |
|---|---|---|
| `C:\xampp\htdocs\myaviation` | YAW current product state | UAS domain modules, web routes, Phase 3-5 status, audit docs, tests and build state. |
| `C:\xampp\htdocs\AB4IRERP` | Mature Laravel/Inertia web workspace reference | Domain modules, Spatie-style permission routing, document library services/resources, workspace pages, reusable React components. |
| `C:\xampp\htdocs\gperp-clinic` | Mature API-first/mobile-safe Laravel reference | `routes/api.php`, `/api/mobile/v1`, mobile middleware, `MobileApiResponse`, mobile auth, platform and communication routes. |
| `C:\xampp\htdocs\mobile\app` | Flutter mobile architecture reference | `dioProvider`, secure storage, `ApiEnvelope`, `go_router`, Riverpod providers, role-aware communication service. |

## Cross-Project Pattern Matrix

| Concern | AB4IRERP pattern | Clinic backend pattern | Flutter app pattern | YAW recommendation |
|---|---|---|---|---|
| Domain organisation | `app/Domains/*` modules with controllers, services, policies, requests, resources and tests. | Domain modules plus events, jobs, API controllers, resources and mobile support classes. | Feature modules with models, providers, services and screens. | Keep YAW's current `Domain/Application/Http` separation; add API adapters without moving business rules into controllers. |
| Web workspace UX | Domain navigation, reusable tables/forms, workspace pages and shadcn/Radix UI primitives. | Admin/clinical web workspaces exist but are less relevant to YAW. | Not applicable. | Adapt AB4IR's workspace shell pattern for Pilot, Aircraft, Mission, Operator, Compliance and GIS workspaces. |
| Permissions | Route-level `permission:*` middleware and policy-backed feature tests. | Mobile persona middleware and authenticated API groups. | Role-aware route segments such as patient/practitioner. | Combine YAW object ownership with explicit permissions; avoid broad role-only access for mobile/API. |
| API versioning | No API baseline found. | `/api/mobile/v1` named routes with `force.json`, mobile security and persona middleware. | Service methods call stable `/api/mobile/v1/...` paths. | Adopt a versioned YAW API, starting with `/api/v1` or `/api/mobile/v1` depending on whether the first consumer is general REST or Flutter-only. |
| Response contract | JsonResource classes exist for web/API-shaped serialization. | `MobileApiResponse` standardises `success`, `message`, `data`, `errors`, `error` and `meta.contract_version`. | `ApiEnvelope<T>` decodes the same success/message/data/error shape. | Adopt the clinic envelope and contract-version metadata before building Flutter. |
| Mobile auth | Not applicable. | OTP/token/PIN/trusted-device flows with throttled route groups. | Secure token storage and Dio authorization interceptor. | Start with Sanctum token auth, then adapt PIN/trusted-device flows when pilot field usage needs them. |
| Mobile client architecture | Not applicable. | Stable mobile controllers and resources. | Riverpod providers, Dio client, secure storage, `go_router` guards. | Use the Flutter app's structure as the YAW mobile skeleton, but replace all Drhealth domain language and endpoints. |
| Documents/evidence | Rich document library with files, folders, versions, activity, approvals, links and resources. | Patient document routes and mobile download flow. | Document viewer capability exists. | Adapt AB4IR's document/version/access ideas into a YAW evidence vault linked to compliance, GIS, operators and missions. |
| Notifications | Web-domain notifications exist in places. | Mobile push registration/revocation and communication unread/read endpoints. | Push token registration and unread communication flows. | Adapt the clinic platform/push pattern for compliance notifications after the API contract exists. |
| Testing | Broad feature workflow tests and permission test helpers. | Mobile contract snapshots and API feature coverage. | Client service/provider tests are the natural fit. | Add API contract tests and mobile client tests beside existing YAW feature tests. |

## Best Patterns Found In AB4IRERP

- Mature domain workspace composition: repeated modules use domain navigation, index/detail pages, reusable table/form components and feature tests.
- Permission-aware web routing: `routes/web.php` centralises domain permission strings and applies explicit middleware to workspace routes.
- Resource classes for shaped payloads: many domains expose `JsonResource` classes instead of leaking model internals.
- Document architecture depth: the document library has file, folder, version, activity, approval, link, preview, search and template services.
- Workflow tests: feature tests exercise real user journeys rather than only unit-level fragments.

## Best Patterns Found In Clinic Backend

- Versioned mobile API entry point: all mobile routes sit behind `/api/mobile/v1` with named route prefixes.
- Mobile-safe middleware layers: `force.json`, mobile security, clinic/persona/session middleware and throttles are applied close to routes.
- Stable response envelope: `MobileApiResponse` resolves resources/arrays and always includes contract metadata.
- Mobile auth lifecycle: OTP/token/PIN/trusted-device flows are explicit and route-tested.
- Platform endpoints: handshake, capabilities, push registration/revocation and sync-state routes provide a mobile operating surface, not just CRUD.
- Communication flow: unread, resolve, inbox, thread, send, delivered, read, archive, pin/correct/retract routes show a complete mobile interaction pattern.

## Best Patterns Found In Flutter App

- Central Dio provider: `shared/network/providers.dart` sets the base URL, JSON headers, bearer token, tenant/clinic headers and structured error mapping.
- Secure session storage: tokens and session scope are handled outside feature screens.
- Typed API envelope: `ApiEnvelope<T>` matches the backend mobile response contract.
- Riverpod feature providers: services and async feature state are created through providers, keeping screens thin.
- Role-aware navigation: `go_router` redirects based on auth state, app role and binding state.
- Feature service pattern: communication service methods map directly to backend routes and return typed models.

## Where YAW Is Reinventing Existing Solutions

- API response shape: YAW has no API yet, so it should not invent a new envelope when clinic already proves a Laravel/Flutter-compatible contract.
- Mobile networking: YAW should reuse the Dio + secure storage + Riverpod service-provider pattern instead of building screen-local HTTP calls.
- Workspace UI primitives: YAW can adapt AB4IR's table/form/domain-nav workspace structure rather than designing every register from scratch.
- Evidence/document governance: YAW's fragmented evidence references should converge toward an AB4IR-style governed document/evidence architecture.
- Push/device operating surface: YAW should adapt the clinic platform/push pattern instead of adding notifications as isolated endpoints.

## Where YAW Is Architecturally Stronger

- UAS compliance traceability is more explicit than the reference projects: regulatory requirements, forms, fees, training links, findings and external integration records are version-aware.
- The current YAW modules keep a clean `Domain/Application/Http` split that is already suitable for adding web and API adapters.
- Mission and GIS work already reference domain records instead of persisting only free-text operational snapshots.
- Phase verification discipline is stronger: each slice has focused feature tests, status docs and verification artifacts.

## Adopt, Adapt, Avoid

| Classification | Pattern | Decision |
|---|---|---|
| Adopt immediately | Stable JSON envelope with `success`, `message`, `data`, `errors`, `error` and `meta.contract_version`. | Use for all YAW API V1 endpoints before Flutter begins. |
| Adopt immediately | Versioned mobile/API route group and named route prefix. | Add `routes/api.php` with a small P0 surface after ownership scoping. |
| Adopt immediately | API contract tests. | Treat route response snapshots as product contracts, not incidental controller tests. |
| Adapt | AB4IR domain workspaces and reusable UI primitives. | Fit them to YAW's UAS workspaces and current design system. |
| Adapt | AB4IR document library architecture. | Build a UAS evidence vault, not a generic intranet library. |
| Adapt | Clinic mobile auth, PIN and trusted-device flow. | Start simpler with Sanctum, then add field-device controls when requirements justify them. |
| Adapt | Flutter role-aware navigation. | Replace patient/practitioner roles with pilot/operator/admin/inspector as product requirements mature. |
| Avoid | Copying AB4IR's absence of an API. | YAW's mobile roadmap requires API-first work now. |
| Avoid | Copying healthcare clinic concepts, route names or patient/practitioner domain language. | Reuse architecture only. |
| Avoid | Building YAW mobile against web routes or raw Eloquent payloads. | Flutter must consume stable API resources/contracts only. |

## Doctor Health Journey Trace

The communication journey demonstrates the reusable mobile pattern:

```text
Flutter communication screen
  -> Riverpod communication provider
  -> CommunicationService
  -> dioProvider with bearer token and scope headers
  -> /api/mobile/v1/{patient|doctor}/communication/...
  -> MobileCommunicationController
  -> CommunicationService domain logic
  -> Communication models/receipts
  -> MobileApiResponse envelope
  -> ApiEnvelope<T> typed Flutter model
```

YAW should use this as the template for a pilot notification/compliance inbox later:

```text
Flutter compliance inbox
  -> Riverpod notification provider
  -> ComplianceNotificationService
  -> Dio bearer token
  -> /api/v1/me/notifications
  -> YAW API controller
  -> NotificationEngine/Application query
  -> ComplianceNotificationResource
  -> YawApiResponse envelope
  -> typed Flutter model
```
