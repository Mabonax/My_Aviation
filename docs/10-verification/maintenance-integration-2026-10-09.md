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
- Current component installation state is not a historical installation/removal ledger. Historical flights skip components installed later or currently retired/removed.
- One mission represents one takeoff/landing cycle in this milestone.
- No manufacturer baseline is automatically interpreted as an approved maintenance interval. Managers must configure source-backed thresholds.
- Recurring task generation, work orders, replacement/removal workflows, dedicated technician authority, battery maintenance intervals and formal return-to-service certification remain future work.
- Task completion records a manager's supporting references; it does not verify the technician's credentials with an external authority.
- No native DJI/Autel parser or Flutter maintenance UI is included.

## Verification

Regression coverage includes telemetry-to-usage-to-due-task propagation, duplicate acceptance, blocked-checklist rollback, installed component eligibility, equality limits, stale relations, dates, threshold validation, shared-aircraft tenancy, immutable completion and release rechecking a previously green gate.

PHP is unavailable in the editing environment. GitHub Actions validation is required; browser acceptance and MySQL concurrency testing remain outstanding.
