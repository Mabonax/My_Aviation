# Maintenance integration milestone

## Delivered

- Each accepted mission flight records component usage once, in the same transaction as post-flight evidence.
- Active components with an installation timestamp on or before takeoff receive recorded hours and one flight cycle. A flight cycle is not a battery charge cycle.
- The usage ledger has a unique component/mission key. Aircraft and component locks coordinate concurrent usage; release locks the aircraft before checking current readiness.
- Component hour/cycle limits block readiness at equality. Non-active installed component states also block. Missing installation times produce a review warning.
- Operator maintenance tasks support date, absolute component hours and flight cycle thresholds, remaining intervals, due and overdue states.
- Due obligations affect the physical aircraft across operator assignments; workspace records and completion evidence stay scoped to their owner operator.
- Web and API routes support scheduling, history and explicit completion with work, technician, parts, evidence and certification references.
- Completed evidence is immutable through these endpoints; repeat completion cannot replace evidence or repeat its audit.
- Task completion does not reset component life totals, clear defects, issue regulatory authorisation or force aircraft serviceability.

## Boundaries

- No automatic historical backfill; recorded totals must be reconciled with pre-existing usage before operational adoption.
- New recorded component changes retain installation/removal timestamps. Historical usage follows those intervals. Legacy retired/removed components without recorded change times still lack sufficient installation history.
- One mission represents one takeoff/landing cycle in this milestone.
- No manufacturer baseline is automatically interpreted as an approved maintenance interval. Managers must configure source-backed thresholds.
- Work orders, dedicated technician authority, battery maintenance intervals and formal return-to-service certification remain future work.
- Task completion records a manager's supporting references; it does not verify the technician's credentials with an external authority.
- No native DJI/Autel parser or Flutter maintenance UI is included.

## Verification

Regression coverage includes telemetry-to-usage-to-due-task propagation, duplicate acceptance, blocked-checklist rollback, installed component eligibility, equality limits, stale relations, dates, threshold validation, shared-aircraft tenancy, immutable completion and release rechecking a previously green gate.

PHP is unavailable in the editing environment. GitHub Actions validation is required; browser acceptance and MySQL concurrency testing remain outstanding.

## Recurring programme follow-up

Tasks may configure positive repeat intervals in days, component hours and flight cycles. Every configured due threshold on a recurring task needs its matching interval. Tasks with no repeat intervals remain single tasks.

Completion creates one successor in the same transaction as immutable completion evidence. A unique previous-task link plus parent and aircraft locks prevent duplicate successors. Programme source, ownership and component identity carry forward; completed evidence does not.

Thresholds advance from the previous due values rather than completion date or current usage. Late completion does not extend the programme or skip overdue intervals. This fixed programme model must be used only when it matches the applicable operator/manufacturer programme. Floating point drift is avoided by calculating hour thresholds in hundredths.

The next task participates in the existing readiness controls immediately. Component life totals and limits remain unchanged. Storage overflow rolls back the entire completion transaction. This milestone does not add interval editing, cancellation, schedule rebaselining or calendar-month intervals.

## Component lifecycle follow-up

The maintenance workspace and API record removal and replacement with technician, reason, certification and evidence references. Removal sets an awaiting-replacement state which blocks readiness. Replacement creates a distinct component record linked to its predecessor; old usage and serial history are retained. Existing known life limits are retained when replacement limits are omitted. Initial replacement usage is explicitly declared and must leave usable component life.

Changes use server recording times, not backdated installation times. Historical flight usage is attributed to the component installed for the whole flight. A flight overlapping a component change or vacant installation interval is rejected for review. No automatic historical backfill occurs.

Open maintenance obligations are not deleted or transferred automatically. Completing a removed component's programme can explicitly end recurrence with a reason retained in immutable completion evidence. Active installed component programmes cannot be ended through this option. Replacement requirements must be configured from supporting source evidence.

State and serial history are shared aircraft facts. Installation/removal evidence is visible only in its recording operator context. Aircraft locks and a unique replacement link protect repeat requests; invalid replacements leave the old component unchanged.

This is same-model/component-type replacement, not an engineering modification or regulator-approved return to service. Initial installation of additional components, transfer between aircraft, backdated corrections, independent technician authority and MySQL concurrency/browser acceptance remain outstanding.

## Lifetime counter boundary follow-up

Post-flight component usage validates the resulting lifetime counters before writing a ledger entry. Hour arithmetic uses integer hundredths; totals above 99,999,999.99 hours or 4,294,967,295 flight cycles reject acceptance with a review message. This applies consistently to SQLite and MySQL instead of relying on database overflow behaviour. Reaching the exact storage boundary remains valid and idempotent. Component life-limit readiness checks remain separate.

Regression coverage checks both overflowing counters, including rollback of an earlier component update, telemetry acceptance, mission actuals, track, pilot log and aircraft folio. Exact-boundary acceptance is also covered. Live MySQL concurrency remains unverified.

## Completion serviceability outcome

Maintenance completion now requires a serviceable, flight-restricted or unserviceable assessment and supporting notes. Restricted findings impose an audited aircraft operational restriction within the completion transaction. Unserviceable findings can escalate a flight restriction. Existing grounding, suspension and other blocked states remain unchanged; a serviceable assessment cannot automatically clear them. Repeat completion cannot rewrite evidence or remove a restriction. Recurrence still generates its successor atomically.

This records the FR-MNT-003 outcome and conservatively enforces restrictions. It does not establish independent technician credentials or provide regulatory return-to-service approval. Existing records are retained without inferred outcomes. Web/API clients must submit both new evidence fields. Automated coverage includes missing evidence, immutable repeats, restricted findings and preserved grounding/suspension; browser acceptance remains outstanding.

## Operator-declared certification authority and maintenance return to service

An active operator member now needs an explicit aircraft/operator certification grant to complete a task or record a component change. Operator managers record supporting evidence and an expiry date; they cannot grant themselves authority. Revocation is immutable and audited. Maintenance officers with a current grant may certify without receiving manager permissions. Active membership, aircraft scope, expiry and revocation are checked inside the aircraft transaction. Certification snapshots preserve the grant and the signed-in actor independently of free-text technician attribution. No existing user receives authority automatically.

Return to service requires an additional grant scope. It applies only to a restriction recorded by this operator's maintenance completion, whose current operational state still matches the recorded finding. A new task created after the restriction must be completed with an authorised serviceable assessment. Existing grounding, suspension and restrictions without maintenance provenance cannot be cleared through this action. All configured maintenance must be green, blocking defects must be closed and no mission may be in progress. One immutable, audited release links repair and restriction tasks, authority and evidence; duplicate submissions do not re-release an aircraft or overwrite records. Flight release and its wider compliance checks remain separate.

The web workspace supports granting/revoking authority, certification and explicit maintenance return to service; API V1 exposes matching routes. This models operator-verified authority and does not independently validate a licence or regulatory approval. External credential verification, broader defect rectification workflows, manufacturer scope management, live MySQL concurrency and browser/device acceptance remain outstanding. Browser/MySQL acceptance is deferred at the user's request, not represented as passed.
