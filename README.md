# Panaxis Eventra

Event management ERP for event-production companies: leads CRM, clients, events with Run of Show,
venues, vendors and work orders, quotations and GST invoices (PDF), expenses, event budgets, guests/RSVP,
QR tickets with a gate scanner, client and vendor portals, reports, and tenant admin.

Migrated and rebuilt from EventFlow ERP (see NOTICE.md for attribution).

## Stack

- Backend: Laravel 12.73 (PHP 8.3), MySQL 8, Sanctum token auth, multi-tenant (company scoping), spatie/laravel-pdf (dompdf)
- Frontend: React 19, Vite 7, TypeScript, Tailwind CSS 4, React Router 7, TanStack Query
- Testing: PHPUnit feature suite, Vitest unit tests, Playwright e2e against the live API, Larastan, Lighthouse

## Repository layout

- `backend/` - Laravel API (`/api/v1`), migrations, seeders, tests
- `frontend/` - React SPA (screens for every module), unit + e2e tests
- `docs/` - PRD, PROGRESS log, SECURITY review, TESTING-REPORT, Lighthouse JSON, API captures, demo videos
- `deploy/` - CI/CD scaffolding (deployment is out of scope for this milestone)

## Quickstart (local)

1. MySQL 8 running, database `panaxis_eventra`, app DB user credentials in environment:
   `DB_USERNAME` / `DB_PASSWORD` (see docs/RUNBOOK.md for the sandbox MySQL recipe).
2. Backend:
   ```
   cd backend
   composer install
   cp .env.example .env   # set DB_*, keep APP_ENV=local
   php artisan key:generate
   php artisan migrate --seed   # seeds demo company, users, and full demo data
   php artisan serve --host=127.0.0.1 --port=8000
   ```
3. Frontend (dev):
   ```
   cd frontend
   npm install
   npm run dev              # http://127.0.0.1:5173, /api proxied to 127.0.0.1:8000
   ```
   Production check: `npm run build && npm run preview` (preview also proxies /api).

Demo logins (seeded): `admin@`, `manager@`, `sales@`, `finance@`, `client1@`, `vendor1@` - all `@eventflow.test`,
password `password`.

## Tests

- Backend: `cd backend && php artisan test` (28 tests, 136 assertions)
- Frontend unit: `cd frontend && npx vitest run` (11 tests)
- E2E (backend + seeded DB + `npm run preview` running): `cd frontend && npx playwright test` (15 tests)
- Static analysis: `cd backend && vendor/bin/phpstan analyse`
- Audit: `cd backend && composer audit`

Full results: docs/TESTING-REPORT.md. Security review: docs/SECURITY.md.
Per-module demo videos (recorded on the production build against the live DB): docs/demos/.
