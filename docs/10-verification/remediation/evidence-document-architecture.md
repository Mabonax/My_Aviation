# Evidence Document Architecture

Date: 2026-09-14

## Implementation Note

Earlier phases had `RegulatoryDocument` metadata and many domain-specific `evidence_references` JSON fields. This slice adds a governed binary evidence vault without removing those legacy references.

The implementation follows the local Laravel domain layering pattern and uses private disk storage, operator tenancy and polymorphic evidence links so aircraft, missions, operators and compliance findings can share one document architecture.

## Implemented

- Added `uas_evidence_documents` with document UID, operator scope, uploader, disk/path, original filename, MIME type, file size, SHA-256 checksum, version, access level, effective/expiry/retention dates and regulatory traceability metadata.
- Added `uas_evidence_links` as a polymorphic link table for operator, aircraft, mission and compliance-finding evidence.
- Added `EvidenceDocument`, `EvidenceLink`, upload action, target/operator resolvers, presenter and summary query.
- Added `EvidenceDocumentPolicy` for global document permissions, operator-manager uploads and active-member/operator-scoped viewing.
- Added web `/evidence-documents` register and upload workflow.
- Added API V1 `GET /api/v1/evidence-documents` and `POST /api/v1/evidence-documents`.
- Added evidence summaries to operator, aircraft and mission presenters.
- Preserved legacy `evidence_references` arrays for additive migration/backfill later.

## Verification

- `php -l database/migrations/2026_09_14_080000_create_uas_evidence_vault_tables.php`: passed.
- `php -l app/Domains/Uas/Documents/Application/Actions/StoreEvidenceDocument.php`: passed.
- `php -l app/Domains/Uas/Documents/Domain/Policies/EvidenceDocumentPolicy.php`: passed.
- `php -l app/Domains/Uas/Documents/Http/Controllers/EvidenceDocumentController.php`: passed.
- `php -l app/Domains/Uas/Api/Http/Controllers/V1/EvidenceDocumentController.php`: passed.
- `php -l app/Domains/Uas/Documents/Http/Requests/StoreEvidenceDocumentRequest.php`: passed.
- `php -l app/Domains/Uas/Documents/Application/Queries/EvidenceDocumentPresenter.php`: passed.
- `php -l app/Domains/Uas/Documents/Application/Queries/EvidenceSummary.php`: passed.
- `php -l tests/Feature/Uas/EvidenceDocumentVaultTest.php`: passed.
- `php artisan migrate --force`: passed; applied `2026_09_14_080000_create_uas_evidence_vault_tables`.
- `php artisan test tests/Feature/Uas/EvidenceDocumentVaultTest.php --compact`: passed; 3 tests, 35 assertions.
- Related evidence/API/aircraft/mission/operator suite: passed; 49 tests, 449 assertions.
- `php artisan test --compact`: passed; 259 tests, 2476 assertions.
- `npm.cmd run build`: passed.
- `php artisan route:list --path=evidence-documents`: passed; 4 web/API evidence-document routes registered.
- `php artisan route:list --path=api`: passed; 16 API routes registered.
- `git diff --check`: passed with CRLF normalization warnings only.
- Conflict marker scan: passed; no matches under app, database, resources, routes, tests or docs.

## Remaining Limits

- Existing legacy `evidence_references` are not backfilled into `uas_evidence_links` yet.
- The first workflow upload panel is operator-scoped; deeper per-record attach forms should be added to high-use screens next.
- Download/preview endpoints and immutable superseded-document version chains remain future work.
- Flutter/offline evidence upload is not implemented.
