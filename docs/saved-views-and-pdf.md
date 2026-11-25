# Saved views sync and PDF access

- Saved list views are stored per user in `saved_views` (GET/POST /api/v1/saved-views, DELETE /api/v1/saved-views/{id}). The UI merges them with browser-local views, so views follow the user across devices.
- The legacy bearer-auth PDF routes (`/invoices/{id}/pdf`, `/quotations/{id}/pdf`, `/tickets/{id}/pdf`, `/work-orders/{id}/pdf`, `/events/{id}/closeout-pdf`) are removed. PDFs are only available through signed `/documents/{type}/{id}/link` URLs.

# Accessibility check

`frontend/tests/e2e/accessibility.spec.ts` scans the login page and every staff page with axe-core (WCAG 2.0/2.1 A and AA) and fails on any serious or critical issue. Light theme only.

# Offline support

- A service worker (`public/sw.js`, production builds only) caches the app shell, so the app opens with no connection.
- The last successful read of each screen is kept on the device per user (`ef_cache:` in local storage) and shown when the network drops, with an "You are offline" banner. It is cleared on sign out or session expiry.
- Writes are never queued. While offline they fail with a plain message.
- `frontend/scripts/sw-check.mjs` checks the production build: service worker registers, and an offline reload still shows the list.
