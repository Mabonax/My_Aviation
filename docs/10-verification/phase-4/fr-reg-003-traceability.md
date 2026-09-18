# FR-REG-003 - Traceability

## Status

`VERIFIED`

## Requirement

Every automated compliance control should be traceable to its regulatory or organisational source.

## Implementation

- Added regulatory requirement relationships from compliance findings, compliance notifications and audit entries using their `requirement_id`.
- Added `ComplianceTraceabilityReport` to aggregate findings, notifications, recent audit entries and training compliance links.
- Classifies controls as `traceable`, `source_text_only` or `missing_source`.
- Added compliance traceability controller and authenticated Inertia report page.
- Added Traceability sidebar navigation.
- Uses `regulations.view` permission because the report exposes regulatory source and compliance-control context.

## Verification

- PHP syntax pass over compliance, notifications, records and UAS feature tests: passed.
- `php artisan route:list --path=compliance/traceability`: registered 1 compliance traceability route.
- `php artisan test --filter=Phase4RegulatoryTraceabilityTest`: passed, 3 tests, 33 assertions.
- `npm.cmd run build`: passed and included `resources/js/pages/compliance/traceability.tsx` in the Vite manifest.
- `php artisan test`: passed, 121 tests, 1232 assertions.
- `php artisan migrate --force`: later passed for the pending Phase 4 regulatory migrations through `2026_09_11_170000_create_regulatory_forms_table`.

## Outstanding

- Exportable traceability evidence pack.
- Complete regulatory source import so all existing requirement IDs resolve to controlled `regulatory_requirements` rows.
- Notification workflow for `missing_source` traceability gaps.
