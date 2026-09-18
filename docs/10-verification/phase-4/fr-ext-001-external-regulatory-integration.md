# FR-EXT-001 - External Regulatory Integration

## Status

`VERIFIED`

## Scope

Implements the External Regulatory Integration register described in section 31 of the functional requirements. The section names the capability without a formal requirement code, so `FR-EXT-001` is used for traceability.

## Repository Evidence

- `database/migrations/2026_09_11_200000_create_regulatory_external_integrations_table.php`
- `app/Domains/Uas/Regulations/Domain/Models/RegulatoryExternalIntegration.php`
- `app/Domains/Uas/Regulations/Domain/Services/ExternalRegulatoryIntegrationClassifier.php`
- `app/Domains/Uas/Regulations/Application/Actions/CreateRegulatoryExternalIntegration.php`
- `app/Domains/Uas/Regulations/Application/Actions/UpdateRegulatoryExternalIntegrationStatus.php`
- `app/Domains/Uas/Regulations/Http/Controllers/RegulatoryExternalIntegrationController.php`
- `resources/js/pages/regulations/external-integrations/*`
- `tests/Feature/Uas/Phase4ExternalRegulatoryIntegrationTest.php`

## Behaviour

- Classifies authority workflows as manual, document-based, verified external or API.
- Captures authority, regulatory area, supported process, authoritative URL, required evidence and workflow notes.
- Keeps undocumented API assumptions explicitly blocked, including forced blocking for API-classified entries until a documented authority integration exists.
- Records FR-EXT-001 audit evidence when an integration is created or its lifecycle status changes.
- Exposes authenticated Inertia pages for listing, creating, viewing and status-managing external integration records.

## Verification

- `php artisan test --filter=Phase4ExternalRegulatoryIntegrationTest --compact`: passed, 6 tests, 65 assertions.
- `php artisan route:list --path=regulatory-external-integrations`: passed, 5 routes registered.
- PHP syntax checks for FR-EXT-001 model, service, actions, controller and feature test: passed.
- `php artisan test --compact`: passed, 149 tests, 1571 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_11_200000_create_regulatory_external_integrations_table`.

## Outstanding

- Controlled production source verification for actual SACAA e-Services URLs and operating instructions.
- Any real external API connector, pending documented and authorised SACAA or authority integration.
- Linking external integration records directly into prepared application pack submission workflows.
- Browser sign-off with authenticated production-like data.
