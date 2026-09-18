# FR-REG-002 - Version Awareness

## Status

`VERIFIED`

## Requirement

Changes to regulations shall create new versions rather than silently modifying historical regulatory rules.

## Implementation

- Added `previous_requirement_id` linkage to `regulatory_requirements` for explicit version-chain traversal.
- Added `RegulatoryRequirement::previousRequirement()` and `RegulatoryRequirement::supersedingRequirements()` relationships.
- Added regulatory requirement policy using `regulations.view`, `regulations.create` and `regulations.update` permissions.
- Added create and supersede actions. Superseding creates a new active row and marks the prior row as superseded with a superseded date.
- Added FR-REG-001 audit evidence for initial requirement creation and FR-REG-002 audit evidence for superseded/version-created events.
- Added regulatory requirement index/create/show/supersede Inertia pages and sidebar navigation.

## Verification

- PHP syntax pass over regulations domain, provider, migrations and UAS feature tests: passed.
- `php artisan route:list --path=regulatory-requirements`: registered 6 regulatory requirement routes.
- `php artisan test --filter=Phase4RegulatoryVersionAwarenessTest`: passed, 5 tests, 67 assertions.
- `npm.cmd run build`: passed.
- `php artisan test`: passed, 118 tests, 1199 assertions.
- `php artisan migrate --force`: later passed; applied `2026_09_11_160000_add_version_linkage_to_regulatory_requirements_table` locally.

## Outstanding

- Controlled SACAA source import/reconciliation workflow.
- Diff view between regulatory versions.
- Regulatory change notification/escalation workflow.
