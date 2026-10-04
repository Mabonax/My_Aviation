# YAW Productisation — Persona Journey Foundation

This slice introduces the product/journey layer identified by comparing YAW with Ready4School/Khita.

## Implemented

- `UserExperienceBootstrap` resolves persona, workspace, onboarding progress, readiness, blocking items, capabilities and navigation.
- New authenticated API: `GET /api/v1/me/bootstrap`.
- The API returns user, pilot, accessible operators and experience context in one contract.
- Persona resolution distinguishes personal pilot, operator pilot, operator manager, compliance officer, maintenance officer and platform admin.
- Web dashboard consumes the same experience resolver and now presents readiness and journey progress rather than a generic Phase 1-only landing page.
- API tests cover personal-pilot and operator-manager bootstrap semantics.

## Architectural rule

The backend remains authoritative for tenancy, capabilities, readiness and the user's next operational action. React and Flutter render the same product context instead of independently recreating aviation business rules.

## Next slices

1. Expand readiness resolvers into pilot, operator, aircraft and mission readiness.
2. Add dedicated persona dashboards and capability-driven navigation.
3. Add mission journey workspace and lifecycle timeline.
4. Add action/notification centre.
5. Add end-to-end persona acceptance tests.
6. Expand public website onboarding for pilots and operators.
