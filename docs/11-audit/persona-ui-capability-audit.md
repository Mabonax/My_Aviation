# YAW Persona, UI, Capability and Validation Audit

Date: 2026-09-21
Status: Baseline audit / remediation input

## Purpose

Audit YAW as end-to-end user journeys rather than isolated backend features. Every user-visible action must trace through UI, route/API, authorization, validation, tenant boundary, domain action/query, audit evidence and automated tests.

## Audit rule

For each action trace:

Persona -> UI -> Route/API -> Controller -> Action/Query -> Policy -> Validation -> Tenant boundary -> Audit evidence -> Tests

Statuses:
- VERIFIED: implementation and automated evidence found.
- IMPLEMENTED: implementation found; test evidence must still be traced.
- PARTIAL: capability exists but user journey is incomplete.
- GAP: expected user action is not exposed or not safely governed.
- FUTURE: anticipated capability, not represented as current implementation.

## Personas

1. Individual / pilot
2. Operator pilot
3. Operator manager
4. Operator compliance / administrator
5. Platform administrator

## Initial cross-cutting findings

### A-001 Sidebar is not persona-aware — HIGH

The authenticated sidebar exposes the same large navigation catalogue to all users: Operators, Pilots, Aircraft, Training, Regulations, Forms, Fees, External, Compliance, Evidence, Traceability, GIS, Notifications, Batteries, Defects and verification.

Backend authorization may reject access, but discoverability itself does not reflect the user's role or active operator context.

Remediation: share explicit UI capabilities from the server and render navigation/actions from those capabilities. Never use hidden navigation as the authorization boundary.

### A-002 Generic dashboard is not persona-aware — HIGH

The current dashboard is a Phase 1 compliance summary. A pilot should instead land on a personal readiness/work queue: profile, credential readiness, operator membership/approval, assigned missions, briefings, expiries and required actions. Operator managers require an operator-level operational dashboard.

Remediation: introduce persona/workspace dashboard composition while retaining server authorization.

### A-003 Pilot self-service can submit regulatory readiness states — CRITICAL GOVERNANCE REVIEW

Own-profile create/update validation currently accepts `medical_status` and `radiotelephony_qualification`. Mission compliance treats medical validity as a regulatory release control.

This creates a trust-boundary concern: self-asserted profile data must not silently become verified operational readiness.

Remediation direction:
- distinguish pilot-declared information from verified compliance state;
- pilot may submit credential/evidence information;
- authorized verification establishes operational status;
- mission readiness consumes verified state, not an unrestricted self-assertion.

Exact regulatory evidence workflow remains a follow-on design decision and must not be invented by the UI.

### A-004 Personal pilot and operator membership journeys are disconnected — HIGH

Backend API membership lifecycle exists, including memberships, invitations and join requests. The web pilot workspace does not yet present membership/invitation/join-request state as a first-class pilot journey.

Remediation: add a pilot "Operators & memberships" surface and action queue. Self-service join requests remain remote-pilot only.

### A-005 Pilot compliance is mostly read-only summary — MEDIUM

`/my/compliance` exposes status, certificate count, expiry count, logbook hours and document count. It does not yet provide a coherent credential wallet/evidence remediation workflow.

Remediation: expose actionable compliance items without allowing the pilot to self-verify regulated states.

## Pilot persona baseline

| User intent | Backend | Web UI | Authorization / validation | Test evidence | Audit |
|---|---|---|---|---|---|
| View own pilot profile | CurrentPilotProfile + MyPilotWorkspace | /my/pilot | viewOwn | RemediationUserPilotOwnershipTest | VERIFIED |
| Create own pilot profile | self-service action/request | /my/pilot/create | createOwn; ownership/privileged fields protected | RemediationUserPilotOwnershipTest | VERIFIED |
| Edit own profile | self-service update | /my/pilot/edit | own-profile authorization | RemediationUserPilotOwnershipTest | VERIFIED |
| Change privileged profile status | protected by backend action | no legitimate self-service action | client value ignored | RemediationUserPilotOwnershipTest | VERIFIED |
| View compliance | MyPilotWorkspace | /my/compliance | viewOwn | ownership suite covers self-service boundary; dedicated UI action coverage to add | PARTIAL |
| Update medical readiness | profile request accepts status | profile form | enum validation, but verification trust boundary unclear | existing ownership tests use unverified | GAP / GOVERNANCE |
| View certificates/expiries | workspace aggregates counts | summary only | own workspace | evaluator/phase tests exist elsewhere | PARTIAL |
| Upload/link personal evidence | evidence domain exists | no clear pilot-owned wallet | requires tenancy/ownership review | to trace | GAP |
| View logbook | workspace aggregates hours/count | summary only | own workspace | post-flight propagation exists | PARTIAL |
| View operator memberships | API lifecycle exists | no first-class web pilot surface found | membership lifecycle rules | OperatorMembershipLifecycleTest | PARTIAL |
| Accept/decline invitation | API lifecycle exists | web pilot action not found | invited user | OperatorMembershipLifecycleTest | PARTIAL |
| Request operator membership | API exists | web pilot action not found | self-service remote_pilot only | OperatorMembershipLifecycleTest | PARTIAL |
| View operator approval | approval domain exists | not first-class in pilot workspace | active membership + approval validity | PilotOperatorApprovalTest | PARTIAL |
| View assigned missions | tenant mission APIs/routes exist | generic /missions shortcut | tenant context required | tenancy/mission tests | PARTIAL |
| Mission briefing/acknowledgement | routes/API exist | mission-level UI to trace | tenant/mission policy | AIM tests to trace | IMPLEMENTED |
| Flight/post-flight workflow | mission/checklist/track/defect/battery routes exist | generic operational pages | tenant/mission rules | Phase 2 tests | IMPLEMENTED |
| Personal notification/action queue | notification engine exists | generic notifications | scope to trace | notification tests | PARTIAL |

## Pilot target journey

Login
-> Personal dashboard
-> My Pilot
   -> Identity & contact
   -> RPC / ratings
   -> Medical evidence and verification state
   -> Radiotelephony / competency evidence
   -> Certificates / credential wallet
   -> Compliance readiness
   -> Operators & memberships
   -> Operator approvals
   -> Assigned missions
   -> Briefings / acknowledgements
   -> Flight history / logbook
   -> Expiries / actions required

A pilot with no active operator may maintain legitimate personal identity/compliance information, but must not obtain operator operational access merely from self-service profile state.

## Required implementation slices

### P1 — Capability-aware shell
- Server shares explicit user/workspace capabilities.
- Sidebar hides irrelevant destinations by persona/capability.
- Tests prove UI capability data does not broaden backend authority.

### P2 — Pilot dashboard
- Personal readiness summary.
- Profile completeness.
- Compliance/credential warnings.
- Operator membership/approval summary.
- Assigned mission/action summary where tenant context permits.

### P3 — Credential trust boundary
- Separate declared profile attributes from verified operational compliance.
- Prevent pilot self-service from turning a regulatory readiness control green without authorized verification.
- Preserve audit history.

### P4 — Operator membership UX
- Invitations.
- Accept/decline.
- Join request.
- Membership status.
- Explicit operator switch.
- Immediate revocation/suspension behavior.

### P5 — Pilot operational journey
- Assigned missions only.
- Briefing acknowledgement.
- Pre-flight permitted actions.
- Post-flight/logbook propagation.
- No cross-tenant discovery.

## Test matrix to add

Each implemented UI action must have:
1. unauthenticated behavior;
2. correct persona allowed;
3. wrong persona denied;
4. own-resource access;
5. foreign-user denial;
6. active-operator tenant isolation where applicable;
7. suspended membership invalidation;
8. validation failure behavior;
9. successful state transition;
10. audit evidence for governed mutations.

## Next audit sequence

1. Pilot self-service and personal dashboard
2. Operator pilot
3. Operator manager
4. Operator compliance/admin
5. Fleet and mission operations
6. Regulatory/compliance
7. GIS
8. Platform administration

This document is a living traceability baseline. A capability is not considered complete merely because a route, model or screen exists.
