# Implementation Status

This directory tracks implementation progress against the founding Functional Requirements Specification and phased roadmap.

## Status files

- `phase-1-status.md`
- `phase-2-status.md`
- `phase-3-status.md`
- `phase-4-status.md`
- `phase-5-status.md`

## Status legend

| Status | Meaning |
|---|---|
| `NOT STARTED` | No material implementation exists |
| `IN PROGRESS` | Partially implemented |
| `IMPLEMENTED` | Code exists but full verification is outstanding |
| `VERIFIED` | Requirement, tests, permissions, workflow and documentation confirmed |
| `BLOCKED` | Cannot proceed because a dependency or clarification is unresolved |

## Governance rule

Status must be based on repository evidence, not intention.

Never change a requirement to `VERIFIED` without recording verification evidence under `docs/10-verification/`.

## FR-AIM-009 — 2026-09-16

**IMPLEMENTED.** Baseline remediation and provider readiness hardening are recorded in the [operational acceptance evidence](../10-verification/remediation/fr-aim-009-operational-acceptance.md). Source health continues to block release for the unconfigured required `atns_aim` provider. Automated, HTTP, visual browser, emulator and external-provider evidence are separate; no production or official-source verification is claimed. ATNS engagement can proceed using the specification and capability template.
