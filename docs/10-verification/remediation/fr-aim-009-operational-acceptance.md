# FR-AIM-009: Operational acceptance, baseline remediation and ATNS readiness

- Requirement: FR-AIM-009, extending FR-AIM-001â€“008.
- Work/evidence dates: 2026-09-15 to 2026-09-16.
- Backend/web: `C:\xampp\htdocs\myaviation`; mobile: `C:\xampp\htdocs\yaw_app`.
- Implementation commit: none. Both repositories contain substantial earlier uncommitted work; see Git discipline below.
- Governing decisions: ADR-007, ADR-008 and [ADR-009](../../09-decisions/ADR-009-provider-operational-conformance.md).
- Source of truth: Laravel domain/application services. React and Flutter render backend aviation decisions.

## 1. Baseline reproduced and classified

| Baseline issue | Reproduction/classification | Remediation |
|---|---|---|
| 12 TypeScript errors | Three missing EmptyState icons; six Record<string,string> form intersections incompatible with nested/array values (including GeoPoint typing); reset-password interface incompatible with Inertia form typing; acknowledgement inferred as literal false; unsupported plus-darker CSS type | Explicit form field contracts and type aliases, correctly typed boolean form, icons and supported darken blend mode. No any/ts-ignore/strictness exclusions. |
| Four Flutter auth failures | Existing onboarding/navigation changed the screen reached by tests; stale sign-in expectations and scroll assumptions; genuine onboarding flex overflow at the test viewport | Tests traverse the actual entry flow and retain meaningful validation/success/error/logout assertions. Onboarding/splash and buttons use constrained scrolling/wrapping. |
| Constrained auth layout | Small viewport, keyboard insets, errors and scaled text competed with fixed layout | Scrollable layouts, intrinsic minimum height, wrapping buttons/labels, bounded errors and no double keyboard padding. Branding/assets preserved. |
| Cross-session stale domain state | Real Android run showed the previous account's mission failure after a different account signed in | Session-owned mission/aircraft controllers are retired on logout and recreated on login; private nested navigation is removed. Regression covers logout from a private route and fresh domain loading. |

Reproduction logs (ignored local evidence): backend `storage/logs/fr-aim-009-types-baseline.log`; Flutter `build/fr-aim-009-tests-baseline.log`. Baseline: 12 TypeScript errors; Flutter 51 passing / four failing. Original Laravel baseline reproduced: 299 tests, 2604 assertions. Failures were not skipped or assertions weakened.

## 2. Automated verification

Final verification results from the completed local runs:

| Check | Result |
|---|---|
| Full Laravel regression | PASS: 347 tests, 2776 assertions |
| AIM focused / reusable provider conformance | Original AIM 40 tests / 126 assertions passed; new contract/safety 44 tests / 116 assertions passed before final full regression; four additional operational acceptance tests included in final suite. |
| PHP syntax and style | PASS: 50 PHP files linted; scoped Pint applied |
| TypeScript | PASS: zero errors |
| Web production build | PASS |
| Flutter analysis | PASS, zero issues |
| Flutter full suite | PASS, 62 tests; includes focused authentication, briefing and mission tests |
| Authentication viewport matrix | PASS: 320Ã—568, 393Ã—852 and 412Ã—915 at text scale 1.0 and 1.3, keyboard inset 240, validation/API errors and recovery |
| Android debug build | PASS, Gradle app:assembleDebug using process-local JDK 17; 117 tasks, 22 executed / 95 up-to-date |
| Route inventory | 178 total / 21 API; unchanged |
| Migrations | All applied; AIM batches 33, 34 and new conformance batch 35 |
| Git diff --check | PASS in both repositories; one pre-existing trailing blank line in pubspec.yaml removed |

Commands: `php artisan test --compact`; `npx.cmd tsc --noEmit`; `npm.cmd run build`; `php artisan route:list --json`; `php artisan migrate:status`; scoped PHP lint/Pint; `dart format` for changed mobile paths; `flutter analyze --no-pub`; `flutter test --no-pub`; `gradlew.bat app:assembleDebug --no-daemon`; both repositories' `git diff --check`.

Logs under backend `storage/logs/fr-aim-009-*` and mobile `build/fr-aim-009-*` are ignored local verification artifacts. They are not production telemetry or portable CI evidence. Build success does not establish runtime or external-provider acceptance.

## 3. Authenticated web/API acceptance against local MySQL

A dedicated development-only account was created through existing User factory/model mechanisms, with a uniquely named synthetic mission and nonoperational fixture reference. Setup guarded the local/development environment. Credentials were randomly generated and held only in ignored private local storage; no source-controlled credentials or production records were introduced. After acceptance, all tokens and web sessions for this dedicated account were revoked, its password rotated to an unrecorded random value, and the private credentials file removed. Clearly labelled development mission/reference/audit evidence remains for traceability (`storage/logs/fr-aim-009-cleanup.log`). The real web session uses CSRF cookies; API acceptance uses a real bearer login and revokes that token afterward.

The live HTTP harness passed 15 assertions against `http://127.0.0.1:8000`:

1. Login page HTTP response.
2. Authenticated web session login.
3. Register component and combined search/provider/type/active filters.
4. Test-fixture classification and operational authority false.
5. Detail raw source, interpretation, provenance, dates, geometry and revision history.
6. Superseded records retained.
7. Empty result.
8. Authenticated web POST briefing generation.
9. Unconfigured ATNS and current blocking explanation.
10. Acknowledgement cannot override unavailable official data (422).
11. Mission release blocked with validation reasons (422).
12. Historical revision separate from current compliance and no historical acknowledgement action.
13. Existing mission compliance contains aeronautical control.
14. Real API login.
15. API briefing source health matches the canonical web result.

Evidence: `storage/logs/fr-aim-009-http-acceptance.json`. Additional automated feature tests exercise active/expired/future filters, directly linked replacement history, allowed warning acknowledgement through authenticated web routes, policy isolation and forbidden pilot import/configuration writes. Allowed acknowledgement is tested using an explicitly synthetic provider in test-process configuration; local development ATNS requirements were never bypassed.

### Visual browser status â€” NOT EXERCISED

Browser CUA initialization failed three times before navigation because the Windows sandbox helper/node kernel exited. Maximum authenticated HTTP/Inertia-prop and feature-level checks were completed instead. HTTP responses do not prove rendered navigation, visual empty states, layout or browser interaction. These remain visual browser acceptance gaps.

## 4. Android emulator/device acceptance

**PARTIAL runtime acceptance; final APK installed, complete final walkthrough not established.**

On 2026-09-15, emulator-5554 ran the debug app (`com.example.yaw_app`, host API `http://10.0.2.2:8000/api/v1`). Observed screens covered sign-out, onboarding, login with the development account, authenticated home, mission list, retry and mission detail. A captured login screen was visually inspected with branding, fields, remember/forgot controls and sign-in visible without clipping. XML evidence uses unique `build/fr-aim-009-device-01.xml` through `device-09.xml`; the unrelated initial account screen is not published. A stale unsuccessful dump was discarded.

The first mission list reused a previous account's failure until Retry. This exposed the session-reset defect now corrected and covered by the new private-route logout/relogin regression. On 2026-09-16 the rebuilt APK containing this fix passed Gradle and installed successfully (`adb install -r` returned Success), and YAW's MainActivity launch was requested. Android then displayed **System UI isn't responding**, recorded in `build/fr-aim-009-final-03.xml`. Choosing Wait did not restore reliable automation; the next UI dump failed and the emulator disconnected. No final on-device session-fix or full briefing walkthrough pass is claimed.

The host experienced severe memory pressure (less than 40 MB free during the first retry). Only the task-started emulator was stopped; it was retried with process-local RAM/core settings, ultimately its saved 2 GB RAM allocation. No saved AVD configuration or other user applications were changed. Startup diagnostics are `storage/logs/fr-aim-009-emulator.log` and `fr-aim-009-emulator-errors.log`. The final emulator process exited; no task emulator is left running.

**Remaining device checks:** mission detail → pre-flight briefing → item source/normalized interpretation → back; permitted acknowledgement in an approved test scenario; actual network failure/refresh/recovery; final session switching. Controller/widget tests cover backend status, severity, release effect, acknowledgement, source-health reason and fetch-failure state clearing/recovery, but these do not replace device proof. Live default ATNS acknowledgement remains forbidden because the required official source is unconfigured.

Software-keyboard layout and moderate scaling are proved by the six-case widget matrix. The inspected emulator login screenshot did not show a software keyboard because the emulator used hardware input; it is not evidence of a visible on-device keyboard. No physical device or production deployment was exercised.

## 5. Provider architecture and lifecycle

Key implementation locations:

- `Domain/DTOs/ProviderCapabilities.php`, `ProviderDataset.php`, `Domain/Enums/DatasetMode.php` and the provider interface.
- `Application/ProviderRegistry.php`, `Actions/ValidateProviderDataset.php`, `Actions/SyncAeronauticalInformation.php`.
- `Domain/Services/ProviderPayloadSafety.php`, `BriefingFingerprint.php` and `Application/Queries/ProviderHealth.php` / `BriefingReadiness.php`.
- `Infrastructure/Repositories/EloquentAeronauticalRepository.php`, normalized/source models and `Application/Queries/AeronauticalRegister.php`.
- Shared web/API controllers and React/Flutter health rendering.

Capabilities cover identity, authority, operational intent, information types, coverage types, modes, NOTAM/PIB/MET/AIXM, timestamps, cancellation/replacement, geometry, altitude, FIR and aerodrome. Approval requires exact registered adapter identity/class, enabled/approved/operational server settings, required capabilities and ten reviewed evidence references. An adapter's official_live claim alone is insufficient. Manual, publication-reference and fixture providers cannot promote themselves.

Full snapshots, incremental and unknown modes are distinct. Incoming complete=true cannot make an incremental response complete. Delta continuity uses encrypted generic sequence/cursor/previous-cursor/transaction/snapshot/publication metadata. A subsequent valid full snapshot is required to restore complete coverage after a delta. Unknown source time cannot satisfy the typed dataset contract; future or regressing source time is rejected. Source TTL is based on source time, not receipt time.

Sync is atomic, serialized by the dataset lock and idempotent for identical accepted data. Numeric source revision regression, conflicting content under reused identity, dataset sequence regression/disappearance, unknown predecessors and delayed older completion fail safely. A failed latest sync blocks the required source; a superseded late sync cannot overwrite a newer result. NEW/REPLACE/CANCEL tests retain raw historical evidence, remove cancelled/replaced predecessors from current assessment and leave historical briefing snapshots unchanged.

Reusable harness: `tests/Support/Contracts/AeronauticalProviderContract.php`, executed by `tests/Feature/Uas/AeronauticalProviderContractTest.php`. A future official adapter must map controlled synthetic transport responses through its own parser into this harness and add provider-specific transport/schema/pagination/error tests. Passing the fixture suite does not certify an unimplemented ATNS adapter.

## 6. Source health and release regressions

Additive health_status values are healthy, stale, unavailable, sync_failed, coverage_insufficient, authority_insufficient and unconfigured. V1 status retains fresh/stale/unavailable. Safe backend reasons, capabilities, mode, dataset time, successful-sync time, coverage and usability are exposed without credentials or cursors. React/Flutter display these server results.

| Scenario | Verified expected result |
|---|---|
| Required ATNS unconfigured | BLOCK |
| Latest synchronization failed | BLOCK |
| Dataset stale | BLOCK |
| Fresh authoritative source with incomplete coverage | BLOCK |
| Complete fresh approved snapshot with zero applicable NOTAMs | No aeronautical block merely for absence; other mission gates remain independent |
| Relevant warning without hard blocker | Review/acknowledgement according to existing governance |
| Relevant hard-blocking item | BLOCK; acknowledgement cannot override |
| Old green briefing then stale/failed/disabled/revoked provider | Current release BLOCK; historical green state grants no permission |

Evidence includes existing AeronauticalInformationTest/EdgesTest and new ProviderContract, ProviderSafety and OperationalAcceptance suites. Default local `atns_aim` remains enabled=false, adapter=null, operational=false, approved=false with no approval evidence; required provider configuration remains intact. No test provider is registered in production code and no environment bypass was added.

## 7. Security and observability

Credential-like keys, bearer/private-key markers and credential/query/fragment-bearing source URLs are rejected before source evidence persists. Provider exceptions become safe fixed messages and error codes; raw exceptions and credentials do not reach API/audit or persisted sync errors. Tests inject representative credentials and verify rejection/redaction. Generic metadata is encrypted and hidden; approval references are represented by a digest, not returned to clients.

These checks complement adapter review: arbitrary secrets embedded in unstructured prose cannot be universally detected. Future transports must keep credentials outside data/URLs and undergo sandbox security review. Ordinary pilots cannot import provider data or change configuration; there is no provider-configuration write API.

Existing structured UasAuditEntry conventions record sync started/completed/failed, provider unavailable, dataset rejected/stale, insufficient coverage and briefing blocking. Useful diagnostics include provider/sync identifiers, counts, dataset mode/digests and safe codes. Approval references, cursor values and credentials are excluded.

## 8. Performance review

Register pagination is 25 with eager-loaded sources. Candidate assessment eager loads source evidence; briefing items/acknowledgements are loaded for snapshot presentation and history returns summaries. ProviderHealth retrieves latest success/outcome in two bounded queries across all providers, with a query-count regression test. Added `aim_sync_outcome` index covers provider/status/id. Existing type, status/effective dates, source identifier/revision and checksum indexes remain appropriate for current paths.

No broad optimization or scale claim is made. Mission-list compliance is still evaluated per mission and must be profiled with representative production volumes before capacity commitments. Dataset ingestion is capped at 10,000 records per envelope; official provider size/pagination requirements must be confirmed before implementing its adapter.

## 9. ATNS engagement artifacts and external dependencies

Created the [18-section ATNS specification](../../11-external-integrations/atns-aim-integration-requirements.md), including 22 confirmation questions, a field-level matrix, processing model and five integration phases. Created the [machine-readable capability template](../../11-external-integrations/atns-provider-capability-template.json), explicitly a YAW questionnaire with unknown provider values null and activation pending.

Outstanding: official access/contact and service contract; confirmed schemas/endpoints and auth; sandbox credentials/samples; coverage/completeness and ordering semantics; cancellation/replacement and failure evidence; source authority; licensing/redistribution/retention/attribution; explicit operational-use permission; operational pilot and production connectivity. No File2Fly scraping, invented endpoint or fake ATNS adapter was implemented. SACAA web summaries are not accepted as operational flight-preparation data.

## 10. Documentation and Git discipline

Updated functional requirement, feature register, architecture/API documentation, ADR/index, phase-2/status index, verification index, root docs index and September development log. Earlier reports remain historical; this report supersedes their TypeScript/Flutter baseline limitations only where new evidence is stated.

Before edits, both repositories were dirty, with FR-AIM-001â€“008 and many unrelated modules uncommitted. Existing files modified by this slice were backed up under ignored `storage/app/fr-aim-009-before` for comparison. No unrelated work was discarded; no broad staging occurred. A safely independent commit cannot be made without incorporating earlier uncommitted foundations in the same files, so no commit or push was made.

## Final result

The requested repository hardening, baseline remediation, ATNS engagement artifacts and reproducible automated/HTTP verification are complete. Visual browser and complete final device acceptance remain explicit limitations; official-provider acceptance has not begun. Default operational release remains blocked.

**ATNS READINESS VERDICT: TECHNICALLY READY FOR ATNS ENGAGEMENT**

This verdict permits technical discovery and licensing discussions. It does not certify sandbox integration, operational use or production readiness.
