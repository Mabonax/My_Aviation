# FR-GIS-003 - Dataset and Layer Capture

## Status

`IMPLEMENTED`

## Scope

Implements the Phase 5 GIS hierarchy steps for Dataset, Orthomosaic / Spatial Output and Layer described in functional requirements section 32 and Phase 5 section 41. This slice stores controlled references and provenance metadata rather than performing geospatial processing.

## Repository Evidence

- `database/migrations/2026_09_12_090000_create_uas_gis_dataset_tables.php`
- `app/Domains/Uas/Geography/Domain/Models/UasGisDataset.php`
- `app/Domains/Uas/Geography/Domain/Models/UasGisSpatialLayer.php`
- `app/Domains/Uas/Geography/Domain/Services/GisDatasetCatalogue.php`
- `app/Domains/Uas/Geography/Application/Actions/CreateGisDataset.php`
- `app/Domains/Uas/Geography/Application/Queries/GisDatasetOptions.php`
- `app/Domains/Uas/Geography/Http/Controllers/GisDatasetController.php`
- `resources/js/pages/geography/projects/datasets/create.tsx`
- `tests/Feature/Uas/Phase5GisDatasetCaptureTest.php`

## Behaviour

- Captures geospatial dataset identity, type, capture source, storage URI, checksum, coordinate reference system, resolution, capture/processing timestamps, processing status, quality status, provenance notes and evidence notes.
- Captures dataset-linked spatial layers with layer type, geometry type, source URI, style metadata, analysis notes and lifecycle status.
- Shows dataset and layer summaries under assigned GIS missions on the project detail page.
- Records FR-GIS-003 audit evidence when datasets are captured.
- Keeps binary GIS files external to the database as controlled references.

## Verification

- `php artisan test --filter=Phase5GisDatasetCaptureTest --compact`: passed, 4 tests, 58 assertions.
- `php artisan route:list --path=gis-project`: passed, 9 routes registered including dataset create/store routes.
- PHP syntax checks for FR-GIS-003 models, service, action, query, controller, request and feature test: passed.
- `php artisan test --compact`: passed, 164 tests, 1756 assertions.
- `npm.cmd run build`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_12_090000_create_uas_gis_dataset_tables`.

## Outstanding

- Dataset status update workflow after initial capture.
- Real orthomosaic processing integration and rendered layer/map verification.
- Feature, opportunity/finding, evidence and report records built on top of captured layers.
