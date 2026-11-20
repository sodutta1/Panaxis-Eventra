# EventFlow ERP Frontend – Product Requirements Document

Version 1.0 · Prepared for Sou · Status: Draft for review

## 1\. Purpose and Summary

EventFlow ERP is an enterprise, multi-tenant Event Management ERP built
on Laravel 11+, PHP 8.5+, MySQL 8+ and Tailwind CSS. The repository is
webotapp-academy/EventFlow-ERP-by-webotapp. This document defines the
requirements for a modern, standalone frontend (single-page application)
that connects to the EventFlow backend and delivers every core workflow:
CRM leads, event production, run of show, vendors, budgeting, guests,
ticketing and gate scanning, GST invoicing, and the client and vendor
portals.

**Important assumption to verify first.** The backend README describes a
Blade and Tailwind application with demo role-switching at /login. It
does not state that a JSON REST API exists. Section 5 defines a
mandatory discovery phase that confirms whether routes/api.php exists.
If it does not, Phase 0 adds a versioned API (/api/v1) with token
authentication before any frontend screen is built.

## 2\. Goals and Non-Goals

### Goals

  - Deliver a fast, role-aware web frontend that covers the full event
    lifecycle from lead to invoice.

  - Keep the frontend decoupled from the backend: it talks only to a
    documented REST API.

  - Preserve multi-tenant isolation and role-based access control (RBAC)
    in the UI and on every request.

  - Support mobile-first use for gate staff (QR scanning) and clients
    (portal).

  - Make every screen traceable to a real backend endpoint and a real
    response.

### Non-Goals (v1)

  - Rewriting backend business logic.

  - Native mobile apps (the web app must be responsive and installable
    as a PWA later).

  - Payment gateway integration (payments are recorded, not processed
    online).

  - Multi-language UI (English only; architecture should not block i18n
    later).

## 3\. Users and Roles

The README lists seven roles. Seeded demo accounts use the password
password.

| Role                           | Demo account            | Primary needs in the frontend                            |
| ------------------------------ | ----------------------- | -------------------------------------------------------- |
| Super Administrator            | admin@eventflow.test    | Full access, tenant and user administration, all modules |
| Event Director / Event Manager | manager@eventflow.test  | Events, run of show, tasks, venues, vendors              |
| Sales Lead / Sales Executive   | sales@eventflow.test    | Leads pipeline, conversions, quotations                  |
| Lead Accountant                | finance@eventflow.test  | Budgets, expenses, invoices, payments                    |
| Operations Staff               | (to confirm in seeders) | Tasks, guest logistics, gate check-in                    |
| Client Portal User             | client@eventflow.test   | Read-only itinerary, run of show, quotations, invoices   |
| Vendor Partner                 | vendor@eventflow.test   | Work orders, scope of work, advance history              |

The frontend must show only the menus, buttons and data each role is
permitted to see, and must still handle 403 responses gracefully because
the backend is the source of truth.

## 4\. Product Scope by Module

Each module below is a requirement group. Priority: P0 = launch blocker,
P1 = important, P2 = later.

### 4.1 Authentication and Session (P0)

  - Login with email and password; error states for invalid credentials
    and validation errors (422).

  - Token or cookie session handling per the chosen auth method (see
    Section 7).

  - Demo quick-switch role buttons on the login screen, enabled only
    when a development flag is set.

  - Logout, session expiry handling (401 redirects to login and
    preserves the target route).

  - Current-user profile fetch on load to drive role-based navigation.

### 4.2 Dashboard (P0)

  - Role-specific landing view: upcoming events, open tasks, new leads,
    pending invoices, today's run of show.

  - KPI cards and simple charts using data from existing endpoints (no
    new analytics backend in v1 unless required).

### 4.3 CRM and Lead Pipeline (P0)

  - Kanban board by stage: New, Contacted, Qualified, Proposal Sent,
    Negotiating, Converted, Lost.

  - Drag-and-drop or action menu to change stage, with confirmation for
    Converted and Lost.

  - One-click conversion of a lead into a Client profile and a confirmed
    Event.

  - Lead detail with interaction log and scheduled follow-ups.

  - Filters: owner, source, event type, date range; search by name,
    phone, email.

### 4.4 Clients (P1)

  - Client list and detail with linked events, quotations and invoices.

  - Create and edit client profile.

### 4.5 Events and Production (P0)

  - Event list with filters by type, status and date. Event types:
    Luxury Wedding, Corporate Summit, Live Concert/EDM Festival,
    Milestone Gala, College Festival, Exhibition, Product Launch.

  - Event creation wizard and detail page with tabs: Overview, Run of
    Show, Tasks, Budget, Vendors, Guests, Tickets, Billing.

  - Status transitions with validation messages returned by the backend.

  - Event code display (for example EVT-2026-WED-001).

### 4.6 Run of Show (P0)

  - Minute-by-minute schedule grouped by day.

  - Each entry: time, title, in-charge person, cue notes, performer.

  - Add, edit, reorder and delete entries; timeline and list views;
    print-friendly view.

### 4.7 Production Milestones and Checklists (P0)

  - Task list with priority (Urgent, High, normal), assignee, due date.

  - Real-time completion toggle with optimistic update and rollback on
    failure.

### 4.8 Venues and Vendors (P1)

  - Venue directory: capacity, day rental rate, availability status,
    amenities.

  - Vendor directory by category: sound, floral decor, catering,
    cinematography, stage fabrication.

  - Purchase work orders and contract tracking, including disbursement
    and advance monitoring.

### 4.9 Budgeting and Financial Control (P1)

  - Budget by cost category with Estimated vs Actual and variance
    display.

  - Contingency reserve calculation display.

  - Expense entry: direct expenses, vendor advances, municipal NOC and
    licensing fees, payment receipt log.

### 4.10 Guests and RSVPs (P1)

  - Guest groups: VIP, Family, Media, Corporate Delegates.

  - Per guest: invitation pass code, table number, dietary preference,
    plus-ones.

  - One-click gate admission toggle; bulk import (CSV) as P2.

### 4.11 Ticketing and Gate QR Scanner (P0 for scanner, P1 for ticket management)

  - Ticket tiers: General Admission, VIP Passes, Early Bird; inventory
    and sold counts.

  - Gate scanner screen: camera-based QR scan plus manual code entry
    fallback.

  - Instant result states: Valid (green), Already checked in (amber,
    with time of first scan), Invalid (red).

  - Duplicate scan prevention must be enforced by the backend; the UI
    only displays the result.

  - Large touch targets, works on low-end phones, handles flaky networks
    with a clear offline or retry message.

### 4.12 Quotations, GST Invoicing and Payments (P0)

  - Itemized quotation builder with deliverables and automatic 18% GST
    calculation (values come from the backend; the UI never computes tax
    authoritatively).

  - Convert approved quotation to milestone tax invoices.

  - Record partial payments; show balance due.

  - Printable, GST-compliant invoice view and PDF download.

### 4.13 Client Portal (P0)

  - Read-only views: production itinerary, run of show, approved
    quotations, invoices.

  - Simple navigation, no internal ERP menus.

### 4.14 Vendor Portal (P1)

  - Work orders list and detail, technical scope of work, advance
    disbursement history.

### 4.15 Administration (P2)

  - User and role management, company, branch and department hierarchy
    (for example Mumbai HQ, Delhi NCR, Bangalore Tech Hub).

## 5\. Mandatory Discovery Phase (Backend Analysis)

Before design or coding, the team must produce an API inventory from the
repository. This is the "how" of turning a backend repo into a frontend
PRD.

1.  Clone the repo and run it: composer install, copy .env.example to
    .env, php artisan key:generate, php artisan migrate:fresh --seed,
    php artisan serve.

2.  Run php artisan route:list and save the output. Separate routes in
    routes/api.php from routes/web.php.

3.  Read controllers in app/Http/Controllers, models in app/Models, form
    requests (validation rules), policies and middleware (RBAC and
    tenant scoping), and migrations in database/migrations.

4.  Read the tests directory. The README says tests cover
    authentication, tenant isolation, event creation, status
    transitions, lead conversions, run of show, task checklists and gate
    QR validation; these are the best executable documentation.

5.  Log in as each seeded role and call each endpoint with curl, Postman
    or Bruno. Save real request and response samples.

6.  Fill the API inventory table (Section 8) and the gap list (Section
    15).

7.  Decide the path: **Path A** (JSON API exists, proceed to frontend)
    or **Path B** (add /api/v1 first, Phase 0).

## 6\. User Flows

### 6.1 Core lifecycle

Lead created, then Contacted, Qualified, Proposal Sent, Negotiating,
then Converted. Conversion produces a Client and an Event. The event
then moves through planning (run of show, tasks, vendors, budget), guest
and ticket setup, event day (gate check-in), and billing (quotation,
milestone invoices, payments).

### 6.2 Flow summaries

  - **Sales:** log in, open leads board, update stage, log interaction,
    convert, create quotation.

  - **Event manager:** open event, build run of show, assign tasks,
    attach vendors and work orders, monitor checklist completion.

  - **Accountant:** review budget variance, record expenses and
    advances, issue invoices, record payments.

  - **Gate staff:** open scanner for an event, scan QR, see Valid,
    Duplicate or Invalid result in under a second.

  - **Client:** log in to portal, view itinerary and timeline, review
    quotation, view invoices.

  - **Vendor:** log in, open work order, read scope, review advances.

## 7\. Technical Requirements

### 7.1 Recommended stack (confirm with the product owner)

  - React 18 with Vite and TypeScript, in a /frontend folder or a
    separate repository.

  - Tailwind CSS (matches the backend's design language).

  - React Router for routing; TanStack Query for server state and
    caching; React Hook Form with Zod for forms and validation.

  - Component library: shadcn/ui or Headless UI.

  - Charts: Recharts. QR scanning: a browser library such as
    html5-qrcode or the BarcodeDetector API with fallback.

  - Testing: Vitest and React Testing Library for units; Playwright for
    end-to-end flows.

### 7.2 Authentication approach

  - Preferred: Laravel Sanctum. SPA cookie mode if the frontend and
    backend share a parent domain; personal access tokens otherwise.

  - Store tokens in memory or httpOnly cookies where possible; avoid
    localStorage for long-lived secrets if cookie mode is available.

  - Configure CORS (config/cors.php) and Sanctum stateful domains for
    http://localhost:5173 in development.

### 7.3 API client requirements

  - Single typed API client; base URL from VITE\_API\_URL.

  - Request interceptor adds auth; response interceptor handles 401
    (redirect to login), 403 (permission message), 422 (field-level
    errors) and 5xx (generic error with retry).

  - Pagination, sorting and filtering parameters follow backend
    conventions found in discovery.

  - Types generated or hand-written from the real response shapes
    captured in Section 5.

### 7.4 Backend changes (only if Path B)

  - Add routes/api.php with /api/v1 prefix, API Resources, Form Requests
    and policies.

  - Reuse existing models and services; keep tenant scoping and RBAC
    identical to the web layer.

  - Add feature tests for every new endpoint, including wrong-role (403)
    and cross-tenant (no data) cases.

  - Publish an OpenAPI document (for example via Scribe or l5-swagger)
    so the frontend can generate types.

### 7.5 Project structure

frontend/  
src/  
app/ routes, providers, layout  
api/ client, endpoints, types  
features/ leads, events, runofshow, tasks, vendors, budget,  
guests, tickets, scanner, billing, portals, admin  
components/ shared UI  
hooks/ shared hooks  
lib/ utils, formatters (currency INR, dates)  
tests/

## 8\. API Inventory (to be completed during discovery)

Each row must be filled from the real code and a real call. Do not
invent endpoints.

| Module        | Method    | Path      | Roles allowed  | Request body    | Response sample saved | Status            |
| ------------- | --------- | --------- | -------------- | --------------- | --------------------- | ----------------- |
| Auth          | POST      | (confirm) | All            | email, password | Yes/No                | Exists / To build |
| Leads         | GET       | (confirm) | Sales, Admin   | filters         | Yes/No                | Exists / To build |
| Lead convert  | POST      | (confirm) | Sales, Admin   | lead id         | Yes/No                | Exists / To build |
| Events        | GET/POST  | (confirm) | Manager, Admin | event data      | Yes/No                | Exists / To build |
| Run of show   | GET/POST  | (confirm) | Manager        | entry data      | Yes/No                | Exists / To build |
| Tasks         | PATCH     | (confirm) | Manager, Ops   | completed flag  | Yes/No                | Exists / To build |
| Guests        | GET/PATCH | (confirm) | Ops, Manager   | admitted flag   | Yes/No                | Exists / To build |
| Gate scan     | POST      | (confirm) | Ops, Gate      | token           | Yes/No                | Exists / To build |
| Quotations    | GET/POST  | (confirm) | Sales, Admin   | items           | Yes/No                | Exists / To build |
| Invoices      | GET/POST  | (confirm) | Accountant     | milestone data  | Yes/No                | Exists / To build |
| Payments      | POST      | (confirm) | Accountant     | amount, mode    | Yes/No                | Exists / To build |
| Client portal | GET       | (confirm) | Client         | none            | Yes/No                | Exists / To build |
| Vendor portal | GET       | (confirm) | Vendor         | none            | Yes/No                | Exists / To build |

## 9\. Screen Inventory

| Screen                  | Roles             | Priority | Key components                      |
| ----------------------- | ----------------- | -------- | ----------------------------------- |
| Login                   | All               | P0       | Form, demo role switch (dev only)   |
| Dashboard               | Internal roles    | P0       | KPI cards, upcoming events, tasks   |
| Leads board             | Sales, Admin      | P0       | Kanban, filters, convert dialog     |
| Lead detail             | Sales, Admin      | P1       | Timeline, follow-up scheduler       |
| Clients list/detail     | Sales, Admin      | P1       | Table, linked records               |
| Events list             | Manager, Admin    | P0       | Table, filters, status badges       |
| Event detail (tabs)     | Manager, Admin    | P0       | Tabbed layout                       |
| Run of show             | Manager, Ops      | P0       | Timeline editor, print view         |
| Tasks checklist         | Manager, Ops      | P0       | Priority list, toggle               |
| Venues                  | Manager           | P1       | Directory, availability             |
| Vendors and work orders | Manager, Admin    | P1       | Directory, contract view            |
| Budget and expenses     | Accountant, Admin | P1       | Variance table, expense form        |
| Guests                  | Manager, Ops      | P1       | Table, groups, admit toggle         |
| Tickets                 | Manager, Admin    | P1       | Tier cards, sales counters          |
| Gate scanner            | Ops, Gate         | P0       | Camera view, result banner          |
| Quotation builder       | Sales, Admin      | P0       | Line items, GST summary             |
| Invoices and payments   | Accountant        | P0       | Invoice list, payment dialog, print |
| Client portal           | Client            | P0       | Itinerary, timeline, documents      |
| Vendor portal           | Vendor            | P1       | Work orders, advances               |
| Admin                   | Admin             | P2       | Users, roles, branches              |

## 10\. Non-Functional Requirements

  - **Performance:** first meaningful load under 3 seconds on a
    mid-range phone over 4G; route-level code splitting; list endpoints
    paginated.

  - **Responsiveness:** fully usable from 360px width; scanner and
    portals designed mobile-first, ERP tables optimized for desktop with
    horizontal scroll fallback.

  - **Accessibility:** WCAG 2.1 AA targets, keyboard navigation, visible
    focus, color is never the only status indicator (scanner results
    include icon and text).

  - **Security:** no secrets in the repo, HTTPS in production, CSRF
    handling in cookie mode, role checks in UI and server, no sensitive
    data in logs or URLs.

  - **Reliability:** graceful handling of network failure with retry;
    optimistic updates roll back on error.

  - **Localization-ready formats:** INR currency, Indian number
    grouping, date and time with timezone awareness.

  - **Browser support:** latest two versions of Chrome, Edge, Safari and
    Firefox; iOS Safari and Android Chrome for scanner.

  - **Observability:** error boundary and client error reporting (for
    example Sentry) in staging and production.

## 11\. Design Requirements

  - Clean, professional ERP look with Tailwind design tokens: one
    primary color, neutral grays, status colors for success, warning,
    danger.

  - Consistent patterns: page header with title and primary action,
    filter bar, data table or cards, right-side drawer for quick edits,
    confirmation dialogs for destructive actions.

  - Empty, loading (skeleton) and error states on every data view.

  - Light theme in v1; dark theme as P2.

  - Deliver wireframes for P0 screens before development of each module.

## 12\. Delivery Plan

| Phase                           | Scope                                                                               | Exit criteria                                          |
| ------------------------------- | ----------------------------------------------------------------------------------- | ------------------------------------------------------ |
| 0\. Discovery and API readiness | Run backend, route inventory, real responses, add /api/v1 if needed, CORS and auth  | API inventory complete; login works from a test client |
| 1\. Foundation                  | Frontend scaffold, API client, auth, layout, role-based navigation, dashboard shell | Log in as each role and see correct menus              |
| 2\. Sales and events            | Leads board, conversion, clients, events list and detail                            | Lead to event conversion works end to end              |
| 3\. Operations                  | Run of show, tasks, venues, vendors, work orders                                    | Event manager can plan a full event                    |
| 4\. Guests and gate             | Guests, tickets, gate scanner                                                       | Valid, duplicate and invalid scans verified on a phone |
| 5\. Finance                     | Budget, expenses, quotations, invoices, payments                                    | GST totals match backend values exactly                |
| 6\. Portals                     | Client and vendor portals                                                           | Portal users see only their own data                   |
| 7\. Hardening                   | Tests, accessibility, performance, security review, deployment                      | Acceptance checklist signed off                        |

Build order inside each phase: list screen, detail screen, create or
edit form, then edge cases.

## 13\. Acceptance Criteria (Release Checklist)

  - All P0 screens implemented and connected to real endpoints; no mock
    data in production build.

  - Each of the seven roles can log in and sees only permitted
    navigation and data.

  - A wrong-role request shows a clear permission message; a
    cross-tenant request returns no data and no error leak.

  - Lead conversion creates a client and an event and the UI reflects it
    without a manual refresh.

  - Task toggle persists after reload.

  - Gate scanner correctly distinguishes valid, already-used and invalid
    tokens, and a second scan of the same ticket is rejected.

  - Quotation and invoice totals, including 18% GST, match backend
    values to the paisa.

  - Invoice print view and PDF download work.

  - Client and vendor portals are read-only where specified.

  - Automated tests: unit tests for utilities and hooks, end-to-end
    tests for login, lead conversion, task toggle, gate scan and invoice
    payment.

  - Lighthouse performance and accessibility scores of 90 or higher on
    key screens.

## 14\. Success Metrics

  - Time to convert a lead to an event: under 2 minutes.

  - Gate scan response time: under 1 second on a typical 4G connection.

  - Frontend error rate: under 1% of sessions.

  - Zero cross-tenant data exposure incidents in testing.

  - Task completion rate in user acceptance testing: at least 90% for
    the core flows per role.

## 15\. Risks, Assumptions and Open Questions

### Risks

  - The backend may lack a REST API, which adds Phase 0 effort.

  - The README mentions PHP 8.5+, which may be hard to install on some
    machines; the team may need a Docker image or a closest supported
    version.

  - Backend business rules (status transitions, conversion logic) may be
    coupled to Blade controllers and need refactoring into services.

  - Camera QR scanning varies across browsers and devices.

  - The repository has few commits and a todo.txt file, so some features
    may be incomplete.

### Assumptions

  - The backend repository is the single source of truth for business
    rules.

  - Demo seeded data is available for development and testing.

  - The team has access to modify the backend if the API is missing.

### Open questions for the product owner

1.  Should the frontend be a separate SPA (recommended) or should Blade
    views be modernized with Inertia or Livewire?

2.  Which frontend stack is preferred (React, Vue, Next.js)?

3.  Will the frontend and backend share a domain (cookie auth) or be
    hosted separately (token auth)?

4.  Is a public ticket purchase page in scope, or only internal ticket
    management?

5.  Is offline gate scanning required?

6.  What hosting and deployment targets apply?

7.  Are there branding assets (logo, colors) to follow?

## 16\. Appendix: How to Create This PRD from a Backend Repository

Use this repeatable method for any backend repo.

1.  **Read the README first.** Capture the product purpose, modules,
    roles, demo credentials and setup steps.

2.  **Run the system.** A PRD written without running the backend is
    guesswork.

3.  **Extract the API surface.** Use the route list, controllers and
    tests. Record each endpoint's method, path, auth, role and payload.

4.  **Map data to screens.** For each entity (lead, client, event,
    run-of-show entry, task, venue, vendor, work order, budget line,
    expense, guest, ticket, quotation, invoice, payment), define list,
    detail, create, edit and delete views.

5.  **Map roles to permissions.** Build a role by screen matrix from
    policies and middleware.

6.  **Write user flows.** Follow the real lifecycle in the code, not an
    assumed one.

7.  **Define requirements and priorities.** Use P0, P1 and P2, and keep
    each requirement testable.

8.  **List gaps.** Missing endpoints, inconsistent responses, absent
    validation messages, and missing pagination all become backend
    tasks.

9.  **Add acceptance criteria and a phased plan.** Tie each phase to a
    demonstrable outcome.

10. **Review with stakeholders and keep the document alive.** Update the
    API inventory as endpoints change.

A useful shortcut: give the discovery steps in Section 5 to a coding
agent such as Claude Code, ask it to fill Section 8 with real data and
write the gap list, then update this PRD with the results.

---

## API inventory (filled during Phase 2, 2026-11-21)

Live REST API: `/api/v1`, Sanctum Bearer tokens, tenant-scoped (`company_id`),
role-gated writes. Full machine-readable spec: `docs/openapi.json` (Scribe,
42 paths). Real captured requests/responses: `docs/api-responses/`.

| Group | Endpoint | Methods | Role gate |
|---|---|---|---|
| Auth | /auth/login | POST | public, throttle 5/min |
| Auth | /auth/me, /auth/logout | GET, POST | any token |
| Dashboard | /dashboard | GET | staff roles |
| Leads | /leads, /leads/{id}, /leads/{id}/status, /leads/{id}/convert | GET, POST, PUT/PATCH, DELETE, POST | read: staff; write: sales group; delete: admins |
| Clients | /clients, /clients/{id} | GET, POST, PUT/PATCH, DELETE | read: staff; write: sales; delete: admins |
| Events | /events, /events/{id}, /events/{id}/status, /events/{id}/schedules, /events/{id}/tasks, /tasks/{id}/toggle | GET, POST, PUT/PATCH, DELETE, POST | read: staff; write: ops group; delete: admins |
| Venues | /venues, /venues/{id} | GET, POST, PUT/PATCH, DELETE | read: staff; write: admins |
| Vendors | /vendors, /vendors/{id} | GET, POST, PUT/PATCH, DELETE | read: staff; write: ops; delete: admins |
| Quotations | /quotations, /quotations/{id} | GET, POST, DELETE | read: staff; create: sales; delete: admins |
| Invoices | /invoices, /invoices/{id}, /invoices/{id}/payments | GET, POST, DELETE, POST | read: staff; write: finance; delete: admins |
| Expenses | /expenses, /expenses/{id}, /expenses/{id}/approve | GET, POST | read: staff; write: finance |
| Guests | /guests, /guests/{id}, /guests/{id}/checkin | GET, POST | read: staff; write: ops |
| Tickets | /tickets, /tickets/{id}, /tickets/types, /tickets/verify | GET, POST | read: staff; write: ops; verify: staff, throttle 30/min |
| Portals | /portal/client, /portal/vendor | GET | client role / vendor role (+ admins) |

Role groups: staff = super_admin, company_admin, admin, event_manager,
sales_executive, accountant, staff; admins = super_admin, company_admin,
admin; finance adds accountant; sales adds event_manager + sales_executive;
ops adds event_manager + staff.

Verified live against the seeded DB: login per role, all list + show
endpoints, 401 without token, 403 on wrong-role write and cross-portal
access (see docs/api-responses/).
