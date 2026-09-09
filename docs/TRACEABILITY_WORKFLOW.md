# Requirements-to-Implementation Traceability Workflow

## Core chain

`FOUNDING DOCUMENT`
→ `REQUIREMENT ID`
→ `IMPLEMENTATION PHASE`
→ `FEATURE REGISTER`
→ `CODE`
→ `TESTS`
→ `VERIFICATION`
→ `STATUS`
→ `DEVELOPMENT LOG`
→ `COMMIT`

## Before implementation

1. Read `CODEX_CONTEXT.md`.
2. Identify the requirement IDs.
3. Read the relevant founding/phase/feature documents.
4. Inspect existing implementation.
5. Confirm applicable ADRs.

## During implementation

1. Keep controllers/UI thin.
2. Put business/compliance logic in domain/application services.
3. Store regulatory provenance for compliance-critical logic.
4. Add tests with the implementation.
5. Do not broaden the requirement silently.

## After implementation

1. Run targeted tests.
2. Run applicable regression tests.
3. Run frontend build/type checks.
4. Update feature register.
5. Update phase status.
6. Write verification evidence.
7. Add development-log entry.
8. Add/update ADR if architecture changed.
9. Commit code and documentation together.

## Periodic reconciliation audit

Ask Codex to:

> Read `docs/CODEX_CONTEXT.md`, the founding FRS, implementation-status files, feature register and ADRs. Audit the actual repository implementation against those documents. Verify migrations, models, services, policies, routes, UI and tests. Identify documentation drift, implementation drift, missing tests, unsupported VERIFIED statuses and requirements implemented without traceability. Update documentation only where supported by repository evidence.

Run this audit at:
- end of every major feature;
- end of every implementation phase;
- before release;
- after significant refactoring.
