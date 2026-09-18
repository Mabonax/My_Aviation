# FR-PACK-001 - Application & Renewal Pack Builder

## Status

`VERIFIED`

## Scope

Implements the first repository-backed slice of the Application & Renewal Pack Builder described in section 29 of the functional requirements. The source specification names the capability without assigning a formal requirement code, so `FR-PACK-001` is used for traceability.

## Repository Evidence

- `app/Domains/Uas/Operators/Application/Queries/ApplicationRenewalPackReport.php`
- `app/Domains/Uas/Operators/Http/Controllers/ApplicationRenewalPackController.php`
- `resources/js/pages/operators/certificate-cases/application-pack.tsx`
- `resources/js/pages/operators/certificate-cases/show.tsx`
- `routes/web.php`
- `tests/Feature/Uas/Phase4ApplicationRenewalPackBuilderTest.php`

## Behaviour

- Builds a read-only application or renewal pack from an operator certificate case.
- Produces cover-sheet data, required form references, applicable fee references, checklist items, readiness score and evidence index.
- Pulls current active regulatory forms by required transaction and active regulatory fees by transaction code.
- Marks evidence checklist items incomplete when they remain in the case outstanding-document list.
- Links the pack from the certificate case show screen and protects access with operator view permission.

## Verification

- `php artisan test --filter=Phase4ApplicationRenewalPackBuilderTest`: passed, 3 tests, 36 assertions.
- `php artisan route:list --path=application-pack`: passed, 1 route registered.
- `php artisan test`: passed, 138 tests, 1441 assertions.
- `npm.cmd run build`: passed and included `resources/js/pages/operators/certificate-cases/application-pack.tsx` in the Vite manifest.
- `php artisan migrate --force`: passed; nothing to migrate for this read-only slice.

## Outstanding

- PDF submission pack export.
- Persistent prepared-pack snapshots and explicit internal approval workflow.
- Controlled mapping table between case transaction types, required forms and fee codes.
- Browser sign-off with authenticated production-like data.
