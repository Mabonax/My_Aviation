# FR-GIS-004 - Feature and Opportunity/Finding Capture

## Status

`IMPLEMENTED`

## Scope

Implements the Phase 5 GIS hierarchy steps for Feature and Opportunity / Finding described in functional requirements section 32 and Phase 5 section 41. This slice stores interpreted layer features and analytical opportunity/finding records without asserting final report publication.

## Repository Evidence

- `database/migrations/2026_09_12_100000_create_uas_gis_feature_tables.php`
- `app/Domains/Uas/Geography/Domain/Models/UasGisFeature.php`
- `app/Domains/Uas/Geography/Domain/Models/UasGisOpportunityFinding.php`
- `app/Domains/Uas/Geography/Domain/Services/GisFeatureCatalogue.php`
- `app/Domains/Uas/Geography/Application/Actions/CreateGisFeature.php`
- `app/Domains/Uas/Geography/Application/Queries/GisFeatureOptions.php`
- `app/Domains/Uas/Geography/Http/Controllers/GisFeatureController.php`
- `app/Domains/Uas/Geography/Http/Requests/StoreGisFeatureRequest.php`
- `resources/js/pages/geography/projects/features/create.tsx`
- `tests/Feature/Uas/Phase5GisFeatureOpportunityTest.php`

## Behaviour

- Captures layer-derived GIS features with feature code, type, geometry reference, confidence score, verification status, interpretation notes and evidence notes.
- Captures feature-linked opportunity/finding records with type, category, title, description, significance, priority, status, evidence reference, due date and responsible role.
- Shows feature and opportunity/finding summaries under captured spatial layers on the GIS project detail page.
- Records FR-GIS-004 audit evidence when features are captured.
- Keeps interpreted geometry and evidence as controlled references rather than binary GIS payloads in the database.

## Verification

- `php artisan test --filter=Phase5GisFeatureOpportunityTest --compact`: passed, 4 tests, 58 assertions.
- `php artisan route:list --path=gis-layers`: passed, 2 feature routes registered.
- PHP syntax checks for FR-GIS-004 migration, models, service, action, query, controller, request and feature test: passed.
- `php artisan test --compact`: passed, 168 tests, 1814 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_12_100000_create_uas_gis_feature_tables`.

## Outstanding

- Dedicated feature and opportunity/finding update workflows after initial capture.
- Evidence attachment workflow for feature-level proof.
- GIS report builder and rendered map/layer verification.
