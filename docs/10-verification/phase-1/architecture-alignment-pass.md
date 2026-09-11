# Phase 1 Architecture Alignment Pass

## Scope

Refactor the existing Phase 1 pilot profile slice into the canonical UAS bounded-domain architecture and standardise the current Phase 1 UI patterns without expanding product scope.

## Current Phase 1 Structure Found

| Feature | Requirement | Current files before pass | Current architecture | Existing tests | Working behaviour | Architectural gap | Required refactor | Risk | Decision |
|---|---|---|---|---|---|---|---|---|---|
| Pilot profile master record | FR-PIL-001 | `app/Domains/Uas/Pilots/{Models,Data,Services}`, `app/Http/Controllers/Uas`, `app/Http/Requests/Uas`, `resources/js/pages/pilots`, `tests/Feature/Uas/PilotProfileTest.php` | Domain-oriented but controller/service/model centric | Pilot feature tests existed and passed before pass | Authenticated users could create/update/view pilot profiles | No application action/query layer, no repository contract, no explicit policy, HTTP classes outside domain module | Move/refactor into Domain/Application/Infrastructure/Http, add provider binding and policy | Medium, because namespaces and model binding changed | REFACTOR |
| Dashboard Phase 1 entry point | Supporting Phase 1 UI | `resources/js/pages/dashboard.tsx` | Starter replacement page | Covered only by existing authenticated dashboard feature test | Dashboard route rendered | UI did not yet share reusable Phase 1 header/card/status language | Add small UAS UI components and apply them | Low | REFACTOR |

## Refactored Structure

- Domain model/enums/policy: `app/Domains/Uas/Pilots/Domain`
- Application actions/DTO/query/presenter: `app/Domains/Uas/Pilots/Application`
- Repository contract: `app/Domains/Uas/Pilots/Domain/Contracts/PilotRepositoryInterface.php`
- Eloquent implementation: `app/Domains/Uas/Pilots/Infrastructure/Repositories/EloquentPilotRepository.php`
- HTTP controller/requests: `app/Domains/Uas/Pilots/Http`
- Binding/policy provider: `app/Providers/UasDomainServiceProvider.php`
- Shared UI components: `resources/js/components/uas`

## Intentionally Left Unchanged

- Database table name `uas_pilots` and existing fields remain unchanged.
- Route URLs and route names remain unchanged.
- Delete remains unavailable until retention and audit controls are implemented.
- Fine-grained roles are deferred because the starter app has no role/permission infrastructure yet.
- Certificate, rating, medical and compliance-state entities remain deferred to FR-PIL-002 and related Phase 1 work.

## Verification

- php artisan test --filter=PilotProfileTest: passed, 6 tests, 29 assertions.
- php artisan test: passed, 33 tests, 93 assertions.
- nnpm.cmd run build: passed.
