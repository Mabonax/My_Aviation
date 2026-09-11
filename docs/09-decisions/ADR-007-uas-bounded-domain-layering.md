# ADR-007 - UAS Bounded Domains Use Layered Modular Architecture

**Status:** ACCEPTED  
**Date:** 2026-09-09

## Context

Phase 1 began with a correct domain-oriented intention, but the first pilot implementation still followed a simplified controller-to-service-to-model flow. That shape is acceptable for a starter slice, but it does not provide enough separation for compliance-critical aviation workflows as the platform grows.

The VMT UAS platform must keep aviation regulation, business rules, application workflows, persistence implementation, HTTP transport and frontend presentation from becoming tightly coupled.

## Decision

UAS bounded domains use a layered modular-monolith structure:

```text
app/Domains/Uas/{Domain}/
    Domain/
    Application/
    Infrastructure/
    Http/
```

Substantive UAS workflows should follow this flow:

```text
Route
  -> Http Controller / Form Request
  -> Application Action or Query
  -> Domain model, enum, policy, service or repository contract
  -> Infrastructure implementation
```

Repository contracts are introduced only where they create a meaningful aggregate or persistence boundary. They must be bound through an explicit UAS service provider rather than scattered through unrelated providers.

Controllers remain transport orchestration only. Validation belongs in Form Requests. Business eligibility and compliance logic belong in the application or domain layers. Persistence implementation belongs in infrastructure.

## Consequences

- Phase 1 pilot profile code is aligned to `Pilots/Domain`, `Pilots/Application`, `Pilots/Infrastructure` and `Pilots/Http`.
- `Controller -> Service -> Model` is not the preferred pattern for substantive UAS features.
- Future pilot, aircraft, compliance, document and notification work should use this structure only where it improves clarity and testability.
- Empty architectural ceremony is still discouraged; create layer directories only when implemented functionality justifies them.
