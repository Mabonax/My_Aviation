# YAW public website

Status: IMPLEMENTED. User-requested public presentation scope, 2026-10-05. This supports discovery of Phase 1 pilot/fleet workflows and later mission, governance and training modules; it introduces no new regulatory control and does not change existing founding requirement verification states.

Related founding requirements: FR-PIL-001 (pilot profile), FR-LOG-001 (pilot flight entry). Public descriptions are informational; actual permissions, readiness and eligibility remain in the existing backend domains.

Public surfaces: `/`, `/solutions`, `/for-pilots`, `/for-operators`, `/compliance`, `/how-it-works`, `/about`. The existing protected `/pilots` and `/operators` routes retain their operational purpose.

Implementation: `resources/js/components/public/public-site.tsx`, `resources/css/public-site.css`, `resources/js/pages/welcome.tsx`, `resources/js/pages/public-page.tsx`, `routes/web.php`. Shared layout, mobile navigation, role selector, expandable FAQs, auth-aware registration/workspace links and page metadata. No new persistence, permissions or domain service is required for static information.

Design reference: https://bolt.eu/en-za/ — photographic hero, service-card grid, generous whitespace, alternating image/copy blocks and pill actions. Brand authority: existing YAW vector logo, user-supplied homepage concept and `C:/Users/John Mabona/Downloads/yaw`. Supplied images are stored as optimized JPEGs under `public/images/yaw`; `logo.svg` derives from the existing horizontal SVG with a cropped viewBox only. No Bolt artwork or copy is included. ATNS-branded supplied imagery is not presented as a YAW partnership.

Verification: `tests/Feature/PublicPagesTest.php` and [public-page acceptance](../10-verification/public-pages/README.md). No demo scheduling, published app-store availability, external-feed access or regulatory approval is claimed.

Mobile app presentation: homepage `#mobile-app` section with an explicitly illustrative phone preview, feature cards and truthful existing-account/registration action. Added on user follow-up, 2026-10-05.
