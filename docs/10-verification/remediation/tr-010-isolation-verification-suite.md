# TR-010 — Operator Isolation Verification Matrix

## Status
Verification suite implemented. Runtime execution remains required in the deployment/local environment.

| Attack / invariant | Expected result | Automated coverage |
|---|---|---|
| Alpha member sends Bravo X-YAW-Operator | 403 | TR010 |
| Alpha context requests Bravo mission ID | 404 | TR010 + API V1 |
| Alpha aircraft list leaks Bravo aircraft | No leak | TR010 |
| Valid Alpha+Bravo member switches context | Only selected tenant returned | TR010 |
| Multiple memberships without selection | 409 operational APIs | TR010 + API V1 |
| Suspended membership retains API access | Denied immediately | TR010 |
| Suspended membership retains web session workspace | Active workspace resolves null | TR010 |
| Generic operators.view/missions.view becomes platform bypass | Never | TR001/TR004/TR010 |
| Explicit platform super-admin accesses tenants | Allowed | TR010 |
| Cross-tenant GIS project bridge | Blocked | TR004 |
| Audit entry loses operator identity after later relationship changes | Operator stored directly on audit entry | TR009 |
| Foreign operator selected in web session | 403 on selection | TR007 |
| Foreign evidence operational list | Tenant context required/scoped | TR003/TR010 |

## Security invariants
1. User identity is not tenant authority.
2. Pilot identity is not tenant authority.
3. Only active operator membership grants normal tenant entry.
4. Operational pilot approval is independent from membership.
5. Tenant-bound API requests resolve CurrentOperatorContext.
6. Multi-operator users must choose an operator for operational APIs.
7. Direct object IDs never override tenant scope.
8. Suspended/ended membership immediately stops normal tenant resolution.
9. Cross-tenant access requires explicit platform authority.
10. Audit evidence stores operator identity directly.

## Execution
Run:
php artisan test --filter=TenantIsolationVerificationTest
php artisan test --filter=TenantIsolationPolicyTest
php artisan test --filter=RemediationOperatorTenancyTest
php artisan test --filter=ApiV1FoundationTest
php artisan test --filter=WebOperatorWorkspaceTest
php artisan test --filter=TenantAuditContextTest
php artisan test

Do not mark runtime verification passed until these commands succeed in the target environment.

## Tenancy remediation completion
TR-001 through TR-010 now form the YAW multi-tenant security baseline:
authority model, active context, tenant-scoped APIs, policy hardening, membership lifecycle, pilot approval, web workspace, Flutter workspace, tenant audit evidence and adversarial isolation verification.
