# Aeronautical information API, web routes and imports

Version: API V1 / envelope `v1.0`; 2026-09-15. Backend: `C:\xampp\htdocs\myaviation`. Mobile: `C:\xampp\htdocs\yaw_app`.

## Routes

| Method | Web route | API V1 route | Shared application operation |
|---|---|---|---|
| GET | `/aeronautical-information` | `/api/v1/aeronautical-information` | AeronauticalRegister::execute |
| GET | `/aeronautical-information/{item}` | `/api/v1/aeronautical-information/{item}` | AeronauticalRegister::detail |
| POST | `/aeronautical-information/import` | None | SyncAeronauticalInformation; privileged reference upload |
| GET | `/missions/{mission}/briefing` | `/api/v1/missions/{mission}/briefing` | MissionBriefing::execute |
| POST | `/missions/{mission}/briefing` | `/api/v1/missions/{mission}/briefing` | GenerateMissionBriefing, then MissionBriefing |
| POST | `/missions/{mission}/briefing/{briefing}/acknowledge` | `/api/v1/missions/{mission}/briefing/{briefing}/acknowledge` | AcknowledgeMissionBriefing, then MissionBriefing |

The existing API success/data/meta envelope is retained. GET and acknowledgement return 200; generation returns 201. Web writes redirect to the briefing/register. API uses Sanctum and the same policies/actions as authenticated web routes. Existing API validation/authorization error envelopes remain in use. There is no new mobile release route.

Register query parameters: `search`, `type`, `provider`, `status` (`current`, `active`, `cancelled`, `superseded`, `all`), `validity` (`active`, `expired`, `future`), and `page`. Page size is 25. Unknown validity remains visible under active filtering; a date filter is not a claim of complete source coverage. Results contain `items` pagination, filters, supported types, provider health and `canImport`.

Detail returns `item` plus `history`. `item.source` contains raw message/payload, source identifier/revision, URL, received/issued/effective dates, checksum and classification. The normalized interpretation is distinct. Canonical geometry is GeoJSON order `[longitude, latitude]`; mission point arrays retain existing `{latitude, longitude}` convention.

Briefing GET optionally accepts `revision=<positive integer>`. Its data contains:

```json
{
  "mission": {"id": 123, "mission_number": "example", "location": "example", "lifecycle_state": "approved", "aeronautical_context": null},
  "briefing": null,
  "compliance": {"status": "red", "briefing_id": null, "current": false, "freshness": "unavailable", "blocking": true},
  "revisions": [],
  "permissions": {"generate": true, "acknowledge": false}
}
```

This abbreviated example illustrates an unavailable source, not live operational data. A generated `briefing` contains ID/revision, historical status, generation/validity timestamps, dataset revision digest, assessment version, captured mission, provider health, summary counts, immutable item snapshots, empty-data explanation and append-only acknowledgements. `compliance` always describes the latest live release control even when the selected briefing is historical. It includes blockers/warnings, reasons, source status, acknowledgement and currentness. Clients must not use the historical `briefing.status` as current release permission.

Generation accepts an empty body to use existing context, or:

```json
{"aeronautical_context":{"altitude_reference":"AGL","minimum_altitude_ft":0,"fir_codes":[],"aerodrome_codes":[]}}
```

Allowed datums are AGL/AMSL or unknown; minimum altitude is required for a complete AMSL comparison. Existing mission maximum altitude is the upper bound. FIR/aerodrome codes are uppercase four-letter identifiers. Context changes occur within the generation transaction and are captured in the new snapshot. Flutter currently reuses existing mission context; web supports datum/minimum confirmation.

Acknowledgement accepts exactly the review declaration needed by the workflow: `{"reviewed":true}`. It records server actor/time, checks mission/briefing ownership, latest revision, freshness and hard blockers, and is idempotent per actor. Clients receive refreshed server state after every write. Mobile clears cached briefing actions on fetch failure and refreshes on app resume and each minute.

## Provider and import contract

`AeronauticalInformationProviderInterface` exposes key, classification, operational usability and `fetch(ProviderRequest): ProviderDataset`. ProviderRequest carries local path, payload or future cursor; ProviderDataset carries canonical records, source timestamp and coverage. Incoming metadata cannot promote a reference adapter to official operational authority.

Use a reviewed local JSON dataset:

```powershell
C:\xampp\php\php.exe artisan uas:sync-aeronautical-information manual --path=C:\path\reference-dataset.json
C:\xampp\php\php.exe artisan uas:sync-aeronautical-information sacaa_publications --path=C:\path\publication-metadata.json
```

The manual adapter accepts a JSON object with `dataset_timestamp` and `records` (20 MB limit). Every record needs `source_identifier`, `source_revision`, `information_type` and `title`; additional provenance includes `source_url`, `issued_at`, `effective_from`, `effective_until` and `raw_message`. The complete input record is retained as raw_payload. `normalized` holds structured fields such as text/summary, geometry, FIR/aerodrome, Q-code, traffic/purpose/scope, altitude limits/units/references, schedule, permanent flag, hazard (`restriction`, `warning`, `information`, `unknown`), status and replaces_identifier. Consult NormalizeAeronauticalInformation for exact validation. Partial Q-line fields are extracted without interpreting prose or assuming FL equals AGL.

SACAA publication imports permit AIP, AIP_AMENDMENT, AIP_SUPPLEMENT, AIC and AIRAC metadata with an HTTPS `caa.co.za` reference. Identifiers/revisions carry publication number/version; issued/effective fields carry dates. The checksum hashes imported metadata, not remote PDF content; this adapter does not download a publication. Manual/publication/fixture imports discard supplied coverage and remain unusable for operational release.

A future operational adapter must provide coverage with `complete=true`, `information_types` including NOTAM, `bbox=[west,south,east,north]`, `valid_from`, and `valid_until`. ProviderHealth requires that coverage include the mission's buffered geometry and full planned interval. Required-source TTL is evaluated against the provider's dataset timestamp, not download time; future or regressing source timestamps are rejected.

Configure placeholders in `.env.example`: ATNS_AIM_ENABLED, ATNS_AIM_BASE_URL, ATNS_AIM_CLIENT_ID and ATNS_AIM_CLIENT_SECRET. Enabling the flag alone does not create an adapter. No production fixture is seeded, no File2Fly scraping occurs, and no official endpoint/authentication contract is assumed.

## FR-AIM-009 additive contract (2026-09-16)

No route or V1 envelope change. Provider health retains `status` (`fresh`, `stale`, `unavailable`) for compatibility and adds `health_status`, `reason`, `operational`, `capabilities` and `dataset_mode`. The current configured ATNS response is conceptually:

```json
{"provider":"atns_aim","status":"unavailable","health_status":"unconfigured","operational":false,"dataset_timestamp":null,"last_successful_sync_at":null,"coverage_complete":false,"usable_for_release":false,"reason":"Official operational provider is not configured."}
```

This is a YAW illustration, not an ATNS response; use the backend's actual `reason` verbatim. `health_status` may also be healthy, stale, unavailable, sync_failed, coverage_insufficient or authority_insufficient. `operational` indicates current server approval, while `usable_for_release` additionally requires successful fresh complete mission coverage. Historical snapshot health and live compliance health remain separate. No credentials, raw exceptions, approval references or cursors appear in these objects.

`AeronauticalInformationProviderInterface::capabilities()` now returns ProviderCapabilities. ProviderDataset adds `mode` (unknown/full_snapshot/incremental) and generic metadata. Ingest validates advertised types/modes, source time, sequences and predecessor cursors. Supported metadata keys are `dataset_sequence`, `provider_cursor`, `previous_cursor`, `source_transaction_id`, `snapshot_id`, `publication_revision`; these are YAW adapter inputs, not ATNS field names. Metadata is encrypted in `ProviderSync.sync_metadata`, excluded from serialization, and never accepted as operational approval. Signed/query-bearing source URLs are rejected; adapters must supply a credential-free provenance reference.

Only a complete full_snapshot with the required types and bounds can establish coverage. Unknown/incremental datasets are incomplete for release. Deltas require a matching accepted base cursor and a changed next cursor; gaps fail closed. Duplicate identical datasets are idempotent. Out-of-order/conflicting payloads fail atomically with a safe diagnostic. Cancellation/replacement preserves history. Detail `history` now includes the selected identifier's revisions plus directly linked predecessor/successor identifiers, allowing chain navigation.

No provider configuration write API exists. Future adapters must satisfy `tests/Support/Contracts/AeronauticalProviderContract.php`, using a test harness that supplies synthetic transport data through their normalization boundary. Provider-specific schema, transport, pagination and actual sandbox/production conformance remain required; the fixture suite alone cannot approve a real adapter.

Flutter retires session-owned mission/aircraft controllers on logout, removes private navigator routes and reloads the next account's data. Fetch failures clear actionable briefing state; recovery accepts only fresh server results. No client-side authority, freshness or release algorithm is added.
