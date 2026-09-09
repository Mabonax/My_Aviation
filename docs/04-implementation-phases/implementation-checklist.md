# Implementation Checklist

Apply this checklist to every implementation slice.

- [ ] Regulatory or organisational source identified
- [ ] Applicability defined
- [ ] Responsible role defined
- [ ] Database entities/relationships defined
- [ ] Lifecycle/state machine defined
- [ ] Permissions/authorisation defined
- [ ] Required evidence/documents defined
- [ ] Validity, expiry and retention rules defined
- [ ] Notifications/escalations defined
- [ ] Audit events defined
- [ ] Domain/application service rules defined
- [ ] Routes/endpoints defined
- [ ] React/Inertia UI defined
- [ ] Mobile/offline needs considered
- [ ] Reports/exports defined
- [ ] Unit and feature tests added
- [ ] Regulatory version/source tests added where compliance-critical
- [ ] Unverified SACAA assumptions explicitly documented

## Architectural rule

Compliance-critical logic belongs in domain/application services and versioned regulatory data — not in controllers or UI components.
