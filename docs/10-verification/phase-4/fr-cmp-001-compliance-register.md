# FR-CMP-001 - Compliance Register

## Status

`VERIFIED`

## Requirement

Calculate compliance at organisation, pilot, aircraft, operation, maintenance, security, training and safety levels. Compliance scores must never obscure critical regulatory failures.

## Implementation

- Added `ComplianceRegisterReport` to calculate per-domain compliance scores from open compliance findings.
- Added domain grouping for organisation, pilots, aircraft, operations, maintenance, safety, security and training.
- Critical findings are counted separately and returned as a dedicated visible list.
- Corrective actions are counted from open findings with recommended action text.
- Added authenticated compliance register controller and Inertia page at `/compliance/register`.
- Added Compliance sidebar navigation.

## Verification

- PHP syntax pass over compliance domain and UAS feature tests: passed.
- `php artisan route:list --path=compliance/register`: registered 1 compliance register route.
- `php artisan test --filter=Phase4ComplianceRegisterTest`: passed, 3 tests, 30 assertions.
- `npm.cmd run build`: passed and included `resources/js/pages/compliance/register.tsx` in the Vite manifest.
- `php artisan test`: passed, 124 tests, 1262 assertions.
- `php artisan migrate --force`: later passed for the pending Phase 4 regulatory migrations through `2026_09_11_170000_create_regulatory_forms_table`.

## Outstanding

- Real expiry-window aggregation beyond current certificate/dashboard-specific counts.
- Corrective-action lifecycle model beyond finding recommended-action text.
- Browser sign-off with production-like data volume.
