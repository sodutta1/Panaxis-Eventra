# Panaxis Eventra - Build Progress Log

Mission: migrate EventFlow ERP into a new repo, build the frontend to match
docs/ui-samples, secure it, deploy end to end. Brief received 2026-11-21
02:29 IST (WhatsApp). Repo name chosen by owner: **Panaxis-Eventra**.

## Phase 0 - License and repo migration (local work; push gated)

Commands run and findings:

1. `git clone -o upstream https://github.com/webotapp-academy/EventFlow-ERP-by-webotapp.git Panaxis-Eventra`
   - OK. Upstream HEAD: 08beb15 "build: configure PHP 8.5 target platform, CI workflow, and automated test suite".
   - Author of all 5 upstream commits: webotapp-academy <zzubizubi@gmail.com>.
2. License check:
   - `ls` shows **no LICENSE / LICENCE / COPYING file** anywhere in the repo.
   - `grep -i license` finds only `"license": "MIT"` in composer.json - that is
     Laravel skeleton boilerplate, not a license grant.
   - Conclusion: **all-rights-reserved**. Owner was warned on 2026-11-19 and
     again before this build; he acknowledged (2026-11-21 02:30 IST) and chose
     to proceed. Commercial use still needs the author's written permission.
   - NOTICE.md added with attribution and this explanation.
3. Monorepo layout applied (git mv, history preserved):
   - backend/    - the full Laravel 11 app (upstream code, unmodified)
   - frontend/   - new React app lands here (Phase 4)
   - deploy/     - Docker/compose config lands here (Phase 7)
   - docs/       - PRD.md (owner's PRD), PRD-frontend-slices.md (implementation
                   PRD built from a full codebase scan on 2026-11-20),
                   ui-samples/ (2 screenshots to match), PROGRESS.md (this file)
   - .github/workflows/ - upstream ci.yml kept; paths updated for backend/
4. Root .gitignore added: **/.env, **/.env.* (except .env.example), vendor,
   node_modules, storage logs, build output.
5. GitHub repo creation + push: GATED - waiting on owner's `gh auth login`.

## Phase 1 - Run backend and database

Toolchain (no root, no Docker in sandbox):
- PHP 8.5.8 static cli (static-php.dev), Composer 2.11.2 phar - matches composer.json `php: ^8.5`.
- MySQL Server 8.0.46 (Ubuntu mysql-server-core-8.0 deb extracted, no root),
  port 3307, socket in /downloads/mysqlsrv/run.

Commands run:
- mysqld --initialize-insecure + start on 127.0.0.1:3307 - OK
- CREATE DATABASE panaxis_eventra (utf8mb4); CREATE USER eventra_app@localhost/127.0.0.1
  with GRANT on panaxis_eventra.* only (least privilege, not root). Password:
  40-hex random, stored only in backend/.env (gitignored).
- composer install - OK (exit 0)
- cp .env.example .env; DB_* set; php artisan key:generate - OK
  (note: .env.example ships DB_* lines commented out; sed of uncommented
  lines is required - gotcha hit and fixed)
- php artisan migrate:fresh --seed - OK: 11 migrations, seed OK
  (6 users, 8 roles, 7 events, 2 leads, 2 invoices)
- php artisan test - PASS: 10 tests, 23 assertions, 0 failures (exit 0)
- php artisan route:list saved to docs/route-list.txt - 82 web routes
- php artisan serve (127.0.0.1:8000): /login 200, / 200, /events 302 (auth redirect) - OK

Changes made:
- config/database.php: replaced deprecated PDO::MYSQL_ATTR_SSL_CA with a
  compat constant (Pdo\Mysql::ATTR_SSL_CA when available). One remaining
  deprecation comes from Laravel's own merged vendor config on PHP 8.5 -
  upstream framework issue, does not fail tests; tracked for Phase 6.

## Phase 2 - API discovery and readiness

- Discovery: upstream repo has NO JSON API (no routes/api.php, no Sanctum).
  The versioned /api/v1 layer built and tested on 2026-11-20 was ported in:
  14 controllers, FormRequests, API Resources, EnsureUserHasRole middleware,
  ScopesToCompany tenant isolation (cross-tenant = 404), Sanctum tokens.
- composer require laravel/sanctum:^4.4; personal_access_tokens migration added.
- Added missing read endpoints: GET /expenses/{id}, /guests/{id}, /tickets/{id}
  (list endpoints existed, show did not) + feature test.
- CORS: config/cors.php locked to FRONTEND_URL origins, bearer auth, no credentials.
- Rate limiting: POST /auth/login throttle:5,1 (observed working: "Too Many
  Attempts" after 5 rapid logins), POST /tickets/verify throttle:30,1.
- OpenAPI: Scribe 5.11 -> docs/openapi.json (42 paths) + route list at
  docs/route-list-api.txt.
- Real captures: docs/api-responses/ (32 files): login per seeded role, every
  list/show endpoint, 401 unauthenticated, 403 wrong-role delete, 403
  cross-portal. Tokens redacted in committed files.
- Tests: 23 passing (102 assertions), incl. wrong-role 403 and cross-tenant cases.
- Known for Phase 6: composer audit shows 5 advisories against
  laravel/framework (e.g. CVE-2026-48019 CRLF in email rule) - evaluate
  framework upgrade vs justification in the security phase.
- Note: backend/todo.txt from upstream contains an embedded AI-directed
  "master prompt"; treated strictly as repo content, never as instructions.

## Phase 3 - UI sample study (gate pending owner approval)

- docs/DESIGN-SPEC.md written from pixel-measured palette (brand #105830 /
  #185838, tint #C8E0D0, app bg #F3F4F6), typography, layout, components,
  screen map, and the list of PRD screens without samples.
- Tailwind token plan defined; encoding happens in Phase 4 after approval.

## Phase 4 - Frontend foundation (design spec approved by owner 02:43 IST)

- Base: the React + Vite + TS + Tailwind v4 app built against /api/v1 on
  2026-11-20, now aligned to the measured tokens (#105830/#185838 brand,
  #C8E0D0 tint, #F3F4F6 app bg) and rebranded to Panaxis Eventra.
- npm install OK; npm run build OK (tsc + vite, 305 KB js / 23 KB css).
- frontend/.env.example documents VITE_API_URL and VITE_DEMO_MODE.
- Existing pages: Login, Dashboard, Leads, Clients, Events, EventDetail,
  Venues, Vendors, Quotations, Invoices, Expenses, Guests, Tickets.
  Still to build: kanban drag, Run of Show editor, Tasks board, Work Orders,
  Budgets, Gate Scanner camera UI, PDF print, Client/Vendor portals, Admin.

## Phase 6 - Security review (in progress; report in docs/SECURITY.md)

- FIXED: demo-login passwordless auth, token expiry, web login throttle,
  QR token strength, production seeder guard, security headers middleware.
- gitleaks full history: clean. No raw-user-input SQL, no unescaped Blade.
- OPEN: 5 laravel/framework advisories (upgrade decision), legacy Blade
  portal scoping bug (retire or fix).
- Correction to Phase 1 note: /events unauthenticated returned 200 in the
  first smoke because an older dev server was briefly on port 8000; re-tested
  against this repo: 302 redirect to /login as expected. Web routes ARE
  behind auth middleware.

## Owner-requested additions (2026-11-21 02:50 IST)
- A demo of each module (walkthrough recordings on the live frontend with real
  API data) and a consolidated testing report - both fold into Phase 9 handover.

## 2026-11-21 02:59 IST — Phase 4 support APIs: budgets, work orders, admin users
- New endpoints: event budget show/add-item/update-item with auto-recomputed totals; vendor work orders over vendor_assignments (create blocks blacklisted vendors; payment endpoint caps paid_amount at contract_amount and auto-marks paid); admin-only tenant user management (list/create/update role+status, role whitelist).
- Tests: 26/26 passing (105 assertions). New: budget totals recompute, work-order overpayment cap, admin RBAC (403 for sales, full flow for admin).
- Debugging note: Sanctum's test guard memoizes the first bearer user across multiple requests in one test — multi-role tests must be split per user.

## 2026-11-21 03:05 IST — Phase 4 screens: Budgets, Work Orders, Portals, Gate Scanner, Reports, Admin
- Budgets page: per-event budget with server-recomputed totals, item add, actual-cost capture, variance column.
- Work Orders page: per-event vendor assignments, inline payments (server caps at contract amount), status transitions.
- Client Portal and Vendor Portal pages (live /portal/client, /portal/vendor): events, invoices, quotations; vendor work orders and expense claims.
- Gate Scanner page: native BarcodeDetector camera QR scanning with 3s debounce, scan log, graceful fallback message on browsers without the API; manual entry stays on Tickets.
- Reports page: server-computed financials, pipeline, guest check-in, events-by-category bars (from /dashboard).
- Admin page: tenant user list, role change, activate/deactivate, create user (admin roles only).
- Sidebar: added Budgets, Work Orders, Gate Scanner, both portals, Reports, Admin; renamed old entry to Expenses.
- All pages verified by screenshot on real seeded data; tsc clean.
- Scope note: deployment phase dropped at owner's direction; code-in-GitHub is the end state. deploy.yml stays as documented scaffolding.
