# FR-GEO-002 - Aviation Overlays

## Requirement

Display aviation overlay layers that can support mission planning review for aerodromes, controlled airspace, restricted airspace, prohibited airspace, strategic areas and approved operating zones.

## Implementation

- Added source-aware aviation overlay tables for publishers, source URLs, source versions, effective dates and zone records.
- Seeded the initial authoritative-source catalogue for SACAA RPAS information, SACAA aeronautical charts/AIP amendments and ATNS AIM static data products.
- Added an aviation overlay catalogue service and report query to group active layers by type and preserve provenance for each rendered zone.
- Added an authenticated `/aviation-overlays` Inertia page linked from the sidebar.
- Added Phase 2 feature coverage for source seeding, layer grouping, provenance keys and authenticated page access.

## Source Boundary

Checked on 2026-09-10:

- SACAA RPAS industry information: https://www.caa.co.za/industry-information/rpas/
- SACAA aeronautical charts and AIP amendments: https://www.caa.co.za/industry-information/aeronautical-charts/
- ATNS AIM: https://aim.atns.co.za/

The seeded zone geometries are placeholders that carry explicit operational notes until an approved authoritative dataset import is added. Basemap tiles or visual map providers are not treated as aviation authority sources.

## Verification

- PHP syntax pass over UAS domain, routes, tests and migrations.
- `php artisan migrate --force` applied `2026_09_10_110000_create_aviation_overlay_tables` locally.
- `php artisan test --filter=Phase2AviationOverlayTest`: passed, 2 tests, 21 assertions.
- `php artisan test`: passed, 44 tests, 188 assertions.
- `npm.cmd run build`: passed and emitted `overlays-CrV3_nyp.js`.
- `php artisan route:list --path=aviation-overlays`: registered `aviation-overlays.index`.

## Status

`IMPLEMENTED`