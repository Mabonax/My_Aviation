# FR-PIL-001 - Pilot Profile Verification

## Requirement

Maintain a unique pilot record containing identity, certificate, RPC category, ratings, medical, radiotelephony, language proficiency, training, examiner, operator affiliation and supporting-document reference data.

## Implementation Evidence

- Migration: `database/migrations/2026_09_09_201200_create_uas_pilots_table.php`
- Model: `app/Domains/Uas/Pilots/Domain/Models/UasPilot.php`
- Enums: `app/Domains/Uas/Pilots/Domain/Enums/*`
- Policy: `app/Domains/Uas/Pilots/Domain/Policies/PilotProfilePolicy.php`
- Repository contract: `app/Domains/Uas/Pilots/Domain/Contracts/PilotRepositoryInterface.php`
- Repository implementation: `app/Domains/Uas/Pilots/Infrastructure/Repositories/EloquentPilotRepository.php`
- Actions/DTOs/queries: `app/Domains/Uas/Pilots/Application/*`
- Requests/controller: `app/Domains/Uas/Pilots/Http/*`
- Provider: `app/Providers/UasDomainServiceProvider.php`
- UI: `resources/js/pages/pilots/*`, `resources/js/components/uas/*`
- Tests: `tests/Feature/Uas/PilotProfileTest.php`

## Regulatory Traceability

| Field | Value |
|---|---|
| Source | Civil Aviation Regulations Part 71; UAS Compliance & Operations Platform FRS FR-PIL-001 |
| Source version | FRS v1.0, dated 2026-09-09 |
| Effective date | 2026-09-09 |
| Applicability | Remote pilot profile master record for South African UAS operations managed in the VMT platform |
| Responsible role | Compliance Manager |
| System control | Authenticated pilot profile CRUD with unique identifiers, explicit server-side policy and no delete route |
| Evidence | Persisted `uas_pilots` record and supporting-document reference metadata |
| Validity rule | Certificate/revalidation validity is deferred to FR-PIL-002 through FR-PIL-004 |
| Retention rule | Retention control is not yet implemented; destructive route is withheld |

## Verification Commands

- php artisan test --filter=PilotProfileTest: passed, 6 tests, 38 assertions.
- php artisan test: passed, 36 tests, 132 assertions.
- npm.cmd run build: passed.

## Status

`VERIFIED`

FR-PIL-001 is verified at repository level through authenticated CRUD, server-side policy coverage, audit trail integration, Phase 1 verification workflow coverage and frontend build proof. Production sign-off remains subject to deployment-site browser acceptance and official operating-role assignment.
