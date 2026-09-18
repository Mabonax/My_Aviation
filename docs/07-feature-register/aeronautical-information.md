# Aeronautical information feature register

**Requirements:** FR-AIM-001 through FR-AIM-009.  
**Status:** IMPLEMENTED; operational and rendered-runtime acceptance remain open.  
**Source:** [functional addendum and internal policy basis](../02-functional-domains/aeronautical-information.md).  
**Verification:** [2026-09-15 integration report](../10-verification/remediation/aeronautical-information-integration.md).

| Layer | Implementation |
|---|---|
| Domain | `app/Domains/Uas/AeronauticalInformation/Domain`: provider/repository contracts, DTOs, information/source enums, source/item/briefing/ack/sync models, policy and fingerprints. |
| Application | Sync/normalize/assess/generate/acknowledge/audit actions; register/item presenter, provider health, briefing/readiness queries. |
| Infrastructure | Eloquent repository; manual, SACAA publication-reference and fixture providers; configuration placeholders for ATNS. |
| HTTP | AeronauticalInformationController, MissionBriefingController, validated requests and existing policy authorization. |
| Persistence | Seven new `uas_aeronautical_*` / `uas_mission_briefing*` tables and mission aeronautical context; three migrations dated 2026-09-15, including provider conformance metadata/indexes. |
| Web | Register, source detail and mission briefing Inertia pages under `resources/js/pages/aeronautical-information`; existing sidebar and mission navigation. |
| Mobile | `C:\xampp\htdocs\yaw_app\lib\features\aeronautical_information`; MissionRepository methods and MissionDetailScreen navigation. |
| Release | Existing MissionComplianceSummary, MissionReleaseGate and ReleaseMission consume the shared readiness result and retain evidence. |
| Tests | AeronauticalInformationTest, AeronauticalInformationEdgesTest, existing mission regression evidence, Flutter briefing tests. |

View uses existing mission-view permissions or active operator membership. Import requires both `aeronautical-information.manage` and `.sync`. Generate/acknowledge use MissionPolicy, assigned pilot/operator management, or explicit `mission-briefing.generate` / `.acknowledge` permissions within view scope. No roles are broadly synchronized or replaced. CLI execution is an administrator operation, outside HTTP authorization.

All public register items are shared aeronautical references; mission briefings retain existing operator access boundaries. Source content and YAW interpretation are visually and structurally separated. Technical reference URLs remain references rather than live-feed claims.

**Commit/push:** uncommitted local implementation; no push. Existing dirty backend/mobile work is preserved.  
**Remaining dependency:** official machine-to-machine aeronautical information access from ATNS/SACAA, plus adapter validation and operational acceptance. See verification report for build/test limitations.

## FR-AIM-009 acceptance and readiness

**Status:** IMPLEMENTED; automated and local HTTP acceptance completed; emulator/browser/external categories are recorded separately in the [operational acceptance report](../10-verification/remediation/fr-aim-009-operational-acceptance.md). Full end-to-end verification is not claimed while visual browser and official-provider acceptance remain outstanding.

Added provider capability and approval contracts, validated full/delta datasets, encrypted generic metadata, ordering/idempotency safeguards, seven health states, safe reasons, lifecycle history, structured audits and reusable provider tests. TypeScript and mobile auth/layout/session baseline remediation accompanies this slice. The [ATNS specification](../11-external-integrations/atns-aim-integration-requirements.md) and [JSON template](../11-external-integrations/atns-provider-capability-template.json) are engagement artifacts, not claims of official interface access.
