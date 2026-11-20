# Panaxis Eventra - Testing Report

Date: 2026-11-21 (IST) · Branch: main · Laravel 12.73.0 · PHP 8.3 · MySQL 8 · React 19 + Vite 7

All tests run against the live MySQL backend (no mocks). Frontend e2e tests run the production build
(`vite preview` with /api proxy) against `php artisan serve`, so every e2e assertion exercises the real
frontend -> API -> database path.

## Results summary

| Suite | Where | Result |
| --- | --- | --- |
| Backend feature/unit tests | `php artisan test` | 28 passed, 136 assertions |
| Frontend unit tests (Vitest) | `npx vitest run` | 11 passed |
| E2E core flows (Playwright) | `tests/e2e/app.spec.ts` | 4 passed |
| E2E thorough whole-app (Playwright) | `tests/e2e/thorough.spec.ts` | 11 passed |
| Lighthouse (production build) | docs/lighthouse/ | Login 100/100/100, Dashboard 97/100/100 |
| Static analysis (Larastan level 5) | `vendor/bin/phpstan analyse` | 0 actionable (Eloquent-magic notices baseline) |
| Dependency audit | `composer audit` | 0 advisories (Laravel 12.73.0) |

## Backend tests (28)

Multi-tenant scoping, RBAC per role (admin/manager/sales/finance/client/vendor), auth login/logout/me,
validation errors, 401/403 envelopes, per-module CRUD (leads, clients, events, venues, vendors, quotations,
invoices, expenses, guests, tickets, budgets, work orders), GST invoice PDF, quotation PDF, ticket verify +
check-in idempotency, lead convert, work-order payment cap at contract value, budget totals recompute
(including item create with null actual_cost - regression below), reports aggregates.

## Frontend unit tests (11)

api client envelope handling, auth store, INR formatting, GST math display helpers, StatusBadge mapping,
form validation.

## E2E core flows (4)

1. Login lands on a dashboard populated from the API.
2. Leads kanban drag moves a card between stages via the API (seeds its own lead first).
3. Invoice PDF downloads a real PDF document.
4. Gate verification accepts a real ticket token.

## E2E thorough whole-app (11)

1. Client role lands in the client portal with only their data.
2. Vendor role sees the vendor portal with assignments.
3. Vendor role is blocked from the admin page by the API (403 enforced server-side).
4. Create a client end to end through the UI form.
5. Budget item add recomputes totals server-side.
6. Work order create then payment capped at contract (server caps overpayment, marks paid).
7. Admin creates a user and it appears in the list.
8. Guest check-in toggles both ways through the row click.
9. Lead create, then convert with confirmation dialog, creates a client record (verified in Clients).
10. Quotation PDF downloads a real PDF.
11. Reports page shows server-computed figures.

## Bugs found by thorough testing and fixed

1. **Budget item create with null actual_cost 500'd** - `event_budget_items.actual_cost` is NOT NULL with
   no default; the controller passed explicit nulls from validated input. Fixed by filtering null fields
   before insert/update in BudgetController; regression test added (create with null actual_cost/notes).
2. **Login rate limit vs test bursts (verified working, tuned for non-prod)** - login is throttled 5/min in
   production as designed; the e2e burst hit 429s. Non-production environments now use 120/min so the suite
   can log in per test while production keeps the strict limit.

## Security posture evidence

See docs/SECURITY.md (15 findings, all FIXED/CLEAN/VERIFIED) plus docs/api-responses/ for captured
401/403 envelopes. Login throttling confirmed live: 6th login attempt within a minute returns 429.
Composer audit: 0 advisories on Laravel 12.73.0.

## Accessibility & performance

Lighthouse on the production build: login page 100 performance / 100 accessibility / 100 best practices;
dashboard 97 / 100 / 100 (JSON reports in docs/lighthouse/). Login page contrast issue found and fixed.

## Known notes

- E2E suites intentionally mutate demo data (create clients, leads, work orders, toggle check-ins).
  Re-seed with `php artisan migrate:fresh --seed` for a pristine demo state.
- Playwright suites use unique per-run names so repeated runs never collide.
