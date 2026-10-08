# Competitive readiness: repository inspection and first telemetry slice

Inspected backend main at 47476b2c908201a85fe98e2d6afc60f7513b1f4c and mobile main at 2f6167625c892a24ce5c02767045344c882a0547.
This is source inspection, not a production acceptance audit.

## Findings

| Area | Repository evidence | Assessment / next work |
| --- | --- | --- |
| Post-flight propagation | PropagatePostFlightRecords, MissionPostFlightPropagationTest | Pilot logbook, aircraft folio, battery summaries and audit exist. Planned-time fallback was unsafe and is removed in this change. Mission-row locking added for concurrent close-out. |
| Flight tracks | RecordFlightTrack, FlightTrackSummariser, Phase2FlightTrackTest | Normalised tracks, distance and altitude summaries exist. No native DJI/Autel adapter established. |
| Fleet readiness | AircraftReadinessSummary | Catalogue, serviceability, registration, approvals, defects and battery checks exist. No scheduled maintenance check in this query. |
| Scheduled/component maintenance | FR-MNT-001/002/003 in docs/02-functional-domains/maintenance-and-assets.md | No maintenance domain implementation located in the inspected tree. Build schedules, counters, component history, work records and authorised return to service. |
| Operator isolation | CurrentOperatorContext, MissionPolicy, tenant isolation tests | Existing operator membership and context controls reused by telemetry API. Production isolation acceptance remains necessary. |
| Aeronautical information | AeronauticalInformation domain and provider/briefing/release queries | Integration code exists. Authoritative provider access and operational freshness need separate acceptance; code presence does not establish live availability. |
| Regulatory/training | Regulatory, Training and related tests/pages in backend | Earlier competitor comparison understated training implementation. Courses and compliance links exist; complete ATO enrolment, instructor assessment and portable record journey are not established by this inspection. |
| Mobile | Aircraft, missions and briefing repositories/screens; operator and token stores | Existing API workspaces. No durable offline execution queue or conflict-resolution engine located. Images/icons named offline or sync are not implementations. |

Baseline GitHub Actions run 37426044490 on backend main: 46 failed, 364 passed, 2,488 assertions.
Failures report missing Inertia page components although those files exist under resources/js/pages.
The default Inertia lookup uses resources/js/Pages; explicit lowercase page paths fix this Linux mismatch while retaining page-existence assertions.

## Delivered first slice

FR-TEL-001 starts with a deliberately explicit YAW CSV v1 contract.
One file contains one flight and one aircraft; first/last rows must explicitly declare takeoff/landing.
It is not a DJI/Autel binary or proprietary-log decoder, and sample bounds alone are not inferred flight events.

1. Authenticated operator manager uploads a bounded CSV to a completed, unpropagated mission.
2. Validate timestamps, event boundaries, sample count, coordinates, altitude and aircraft serial.
3. Save raw evidence, raw SHA-256, normalised SHA-256, pilot/aircraft snapshots and staging audit.
4. Review summary; explicitly confirm telemetry, aircraft, pilot, defects and occurrence declarations.
5. Accept under mission/import locks; reject conflicts, changed identities, inactive aircraft assignments and cross-operator requests.
6. Atomically write a flight track and invoke existing guarded post-flight propagation into logbook and folio.
7. Retry accepted imports without duplicating operational records. Roll back all acceptance writes if any closure/checklist guard fails.

No automatic battery-cycle inference or maintenance due calculation is introduced.
Existing battery usage entries are summarised by the existing closure action.
No telemetry acceptance changes mission-release requirements or grants regulatory approval.

## API and sample

All endpoints use API V1 authentication and the existing X-YAW-Operator context.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | /api/v1/missions/{mission}/telemetry-imports | Paginated review summaries; raw file/sample arrays excluded |
| POST | /api/v1/missions/{mission}/telemetry-imports | Multipart file field, maximum 2 MiB / 10,000 samples |
| POST | /api/v1/missions/{mission}/telemetry-imports/{import}/accept | Explicit declarations; existing post-flight checklist must permit propagation |

See docs/examples/telemetry/yaw-flight-v1.csv. Replace DEMO-AIRCRAFT-SERIAL with the mission aircraft serial.
CSV timestamps require ISO 8601 with timezone and no fractional seconds.
Altitude is in feet, 0–100,000. Headers and field order are exact.
Repeated equivalent normalised files are detected within an operator, including across missions.
Different sampling/resampling of the same physical flight is not yet a semantic duplicate detector; one completed propagation per mission still applies.

Acceptance JSON:

```json
{
  "telemetry_confirmed": true,
  "pilot_confirmed": true,
  "aircraft_confirmed": true,
  "defects_declared": false,
  "occurrence_declared": false,
  "closure_notes": "Reviewed source file and flight boundaries."
}
```

## Verification

Added parser rejection datasets and feature regressions for API staging/acceptance, idempotency, rollback, conflict protection, operator isolation, identity changes and planned-time rejection.
No PHP, Composer or Flutter runtime is installed in the editing environment. Execute the existing GitHub Actions test workflow on the PR; do not interpret authored tests as passing tests.

Local commands after checking out the branch:

```bash
composer install
php artisan migrate
php artisan test tests/Unit/CanonicalCsvTelemetryParserTest.php tests/Feature/Uas/MissionPostFlightPropagationTest.php
php artisan test
```

Use a development database for initial migration and acceptance.

## Remaining competitive programme

1. Add web/mobile upload, review and acceptance UI using existing mission workspace and logo assets.
2. Validate manufacturer adapters against real DJI/Autel samples, identifying explicit flight boundaries, serials, parser versions and ambiguous data.
3. Maintenance: derive aircraft hours/cycles from accepted folios with defined baseline counters; add component/schedule/work-release records and date/hour/cycle due checks to mission release.
4. Offline Flutter: durable assigned-mission cache, local evidence/outbox, idempotency keys, operator-bound queues and conflict handling.
5. Operational compliance demonstration with authoritative aeronautical data and versioned requirements.

Real-device/browser acceptance, MySQL concurrency testing and real manufacturer logs remain required.
