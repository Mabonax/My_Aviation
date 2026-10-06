# Public-page acceptance — 2026-10-05

Implemented the YAW homepage and six public information pages from the user-requested Bolt layout reference while retaining YAW branding. This is local source/build/browser evidence; no production deployment or authenticated operational acceptance is claimed.

## Checks

- `npx.cmd tsc --noEmit`: passed, zero errors.
- Focused ESLint for public-site.tsx, welcome.tsx and public-page.tsx: passed.
- `npm.cmd run build`: passed.
- `php artisan test --compact tests/Feature/PublicPagesTest.php tests/Feature/ExampleTest.php`: 8 tests, 97 assertions passed.
- Headless Chromium against `http://localhost:8000`: homepage at 1440, 768, 390 and 320 pixels, no horizontal overflow or failed images after loading below-fold lazy assets. Six detail pages at 320px, no horizontal overflow. Mobile navigation reaches `/for-pilots`; Technical teams selector changes content; FAQ disclosure opens. No JavaScript page errors recorded.
- [Machine-readable browser results](browser-results.json).
- Screenshots: [desktop](home-1440.png), [tablet](home-768.png), [phone](home-390.png), [compact phone](home-320.png).

The initial browser run identified compact-header overflow at 320px; sizing was fixed before the successful repeat. Initial guest tests identified protected route collisions; public pilot/operator pages now have dedicated `/for-*` paths. Image checks explicitly load lazy images before inspection to avoid treating deferred loading as asset failure.

## Scope and limits

Registration/login/workspace actions use existing named routes. No demo-booking backend exists, so actions truthfully say Get started. No app-store badges, invented partners or unverified live-feed claims were added. The supplied ATNS-marked image remains an unused asset and is not shown as a partnership endorsement. The broader backend regression suite was not rerun for this static presentation change. Context7 tools were unavailable; existing installed Inertia patterns were preserved. Native browser tooling failed at sandbox initialization; local Chromium used the bundled Playwright runtime instead.

## Mobile app presentation follow-up

The homepage includes `#mobile-app`, a branded illustrative phone preview and mission, aircraft and compliance messaging. Store download links remain absent because public distribution URLs have not been verified. The CTA uses existing registration/workspace routes. Repeated TypeScript, scoped lint and production build pass; responsive browser results and screenshots include the new section.

App-store badge clarification: added visible black App Store and Google Play badges in the mobile section. No listing URLs exist in the inspected project; badges display Coming soon and are not linked until the owner supplies published listings. TypeScript/build and responsive browser checks repeated.
