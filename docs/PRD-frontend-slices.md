# Table of Contents

# EventFlow ERP — Frontend Product Requirements Document (PRD)

**Source repository analyzed:**
`https://github.com/webotapp-academy/EventFlow-ERP-by-webotapp` (Laravel
11 backend, session-based Blade UI) **Document type:**
Implementation-ready Frontend PRD, organized into vertical functional
slices **Prepared for:** Frontend engineering team — each slice is
written so the ERD, database contracts and UI components can be derived
directly from it.

> **Interface layers found in the codebase.** The cloned repository
> ships **only** a server-rendered Blade frontend over Laravel session
> auth — it has **no JSON API** (`routes/web.php` is the only HTTP route
> file; there is no `routes/api.php`). A versioned REST layer `/api/v1`
> (Laravel Sanctum bearer tokens, 64 route definitions, JSON resources,
> per-role middleware) has since been built on top of this exact
> codebase and is the contract a decoupled frontend should consume.
> Throughout this PRD, the **API Contract Mapping** tables reference the
> `/api/v1` endpoints, and the equivalent legacy web route is noted
> where one exists. Everything in the data models, enums, validation
> rules and workflows below is extracted from the repository’s actual
> migrations, models, controllers and seeders.

# 1\. Executive Summary & System Overview

## 1.1 Product Purpose

EventFlow ERP is a **multi-tenant, end-to-end event management ERP** for
event production companies, wedding planners, corporate-event agencies
and venue operators. Based on the backend domain logic, it covers the
full business cycle:

1.  **Sell** — capture leads, work a 7-stage pipeline (`new → contacted
    → qualified → proposal_sent → negotiating → converted | lost`),
    convert a won lead into a Client in one action.
2.  **Plan** — register Events (weddings, conferences, concerts,
    exhibitions, product launches, etc.) with client, category, venue
    and manager; build multi-day **run-of-show schedules** and
    prioritized **production task checklists**.
3.  **Resource** — maintain partner **venues** and **vendors**, issue
    vendor work orders (assignments with scope and contract amounts),
    track **budgets with variance** (estimated vs actual), record
    **expenses**, and manage **equipment inventory** with allocations.
4.  **Host** — manage **guest lists, groups, RSVPs and gate check-in**;
    sell/issue **tiered tickets with unique QR tokens** and run a **live
    gate scanner** that blocks duplicate entry.
5.  **Bill** — build **itemized quotations** and **GST tax invoices**
    (default 18%), record **partial payments**, and track balance due
    per invoice and per event.
6.  **Report & share** — a KPI dashboard, a financial/operations
    **reports** module, and read-only **client and vendor portals**.

Tenant model: `companies → branches → departments`, with `employees`,
`roles`, `permissions` and `system_settings` as tenant-scoped foundation
tables. Nearly every business table carries `company_id`, and the seeded
demo tenant is an Indian company (INR, ₹ symbol, GST tax fields, Indian
branches: Mumbai HQ, Delhi NCR, Bengaluru).

## 1.2 Backend Tech Stack Summary

| Layer                           | What the code shows                                                                                                                                                                                         |
| ------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Language / Framework            | PHP 8.5+, **Laravel 11** (`laravel/framework ^11.31`)                                                                                                                                                       |
| Database / ORM                  | **MySQL 8**, Eloquent ORM; 8 migration files creating \~40 tables; no soft deletes anywhere                                                                                                                 |
| Auth — web (in repo)            | Laravel **session auth** (`Auth::attempt`, `sessions` table, CSRF forms); login/logout/demo-login routes only. No registration, password reset UI, or email verification flow                               |
| Auth — API (added layer)        | **Laravel Sanctum** personal access tokens: `POST /api/v1/auth/login` issues a bearer token; `GET /api/v1/auth/me`; `POST /api/v1/auth/logout`                                                              |
| API style                       | **REST**, JSON resources (`App\Http\Resources\V1\*`), Laravel paginator responses; versioned prefix `/api/v1`; tenant scoping via `company_id` (`ScopesToCompany` concern); RBAC via `role:` middleware     |
| Authorization                   | `users.role` string column + hardcoded role lists in middleware; `roles`/`permissions` tables exist but are **not wired** to any checks (see gaps)                                                          |
| Server-rendered UI (in repo)    | Blade + Tailwind CSS + Vite, dark theme, Font Awesome icons; 36 Blade views                                                                                                                                 |
| PDF / print                     | `barryvdh/laravel-dompdf` and `picqer/php-barcode-generator` are installed; print today is done via **print-styled Blade views** (`quotations.print`, `invoices.print`, `tickets.print`), not PDF downloads |
| Background jobs / notifications | `jobs`, `cache` tables migrated; **no jobs, queues, mail or notification code present**                                                                                                                     |
| Tests                           | PHPUnit 11 scaffolding; feature tests for the API layer (`tests/Feature/Api/V1/ApiV1Test.php`)                                                                                                              |

## 1.3 Frontend Architecture Recommendations

| Concern               | Recommendation (matched to backend)                                                                                                                                                                                                                                                                                                                                                                               |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Framework             | React 18/19 + TypeScript + Vite (or Next.js). The backend is a pure JSON REST API, so a decoupled SPA is the natural fit                                                                                                                                                                                                                                                                                          |
| API client            | A single typed client (Axios/fetch wrapper) with base URL `/api/v1`, an auth-token interceptor, and a global 401/403/422 handler. Generate types from the resource shapes in Section 3                                                                                                                                                                                                                            |
| Auth & token handling | Token login: `POST /auth/login {email, password, device_name}` → store the returned Sanctum token (memory + httpOnly persistence strategy per deployment), attach as `Authorization: Bearer <token>`, bootstrap the app from `GET /auth/me`, clear on `POST /auth/logout` or any 401                                                                                                                              |
| State management      | **TanStack Query** for server state (lists, details, dashboards — maps 1:1 onto the paginated JSON resources) + lightweight local state (Zustand/Context) for UI state (sidebar, modals, scanner mode). No global entity store needed                                                                                                                                                                             |
| Routing               | Route tree mirrors the vertical slices in Section 3: `/login`, `/dashboard`, `/leads`, `/clients`, `/events`, `/events/:id` (tabbed), `/venues`, `/vendors`, `/quotations`, `/invoices`, `/expenses`, `/inventory`, `/guests`, `/tickets` (+ `/tickets/scan`), `/reports`, `/portal/client`, `/portal/vendor`. Route guards read the role from `GET /auth/me` and apply the RBAC matrix in Section 2              |
| Forms & validation    | React Hook Form + Zod schemas generated from the backend validator tables in each slice — the backend returns Laravel `422 {message, errors{field: [...]}}`, which the forms display field-by-field                                                                                                                                                                                                               |
| Money, dates, IDs     | Currency INR with Indian digit grouping (₹, lakh/crore display on dashboard cards); dates `YYYY-MM-DD` (`date` columns) and ISO datetimes (`dateTime` columns); every business record has a human code (`EVT-2026-XXXXX`, `QTN-2026-####`, `INV-2026-####`, `PAY-2026-####`, `EXP-2026-####`, `TCK-…`, `QR-XXXX-XXXX-####`, `GUEST-XXXXXX`, `CL-####`, `VND-###`, `VEN-###`) — display codes, key by numeric `id` |
| Pagination            | Laravel paginator: `{data: [...], links, meta: {current_page, per_page, total, ...}}` — build one reusable paginated-table component (page sizes in the backend: 12 events/clients/venues/vendors, 15 leads/quotations/invoices/expenses/inventory, 20 guests/tickets)                                                                                                                                            |
| Real-time             | No WebSockets in the backend. The gate scanner and dashboard use **polling/refetch-on-mutation** only (see Slice 9)                                                                                                                                                                                                                                                                                               |

# 2\. User Roles & Access Control Matrix (RBAC)

## 2.1 Roles found in the codebase

Seeded in `DatabaseSeeder` (table `roles`) and used by
`User::isAdmin()/isEventManager()` and the API `role:` middleware:

| \# | Role slug         | Display name           | Where enforced                                                                        |
| -- | ----------------- | ---------------------- | ------------------------------------------------------------------------------------- |
| 1  | `super_admin`     | Super Administrator    | API middleware; `User::isSuperAdmin()`                                                |
| 2  | `company_admin`   | Company Admin          | API middleware; `User::isAdmin()`                                                     |
| 3  | `admin`           | (generic admin alias)  | API middleware + `User::isAdmin()` — **not seeded**; treat as alias of company\_admin |
| 4  | `event_manager`   | Lead Event Manager     | API middleware; `User::isEventManager()`                                              |
| 5  | `sales_executive` | Senior Sales Executive | API middleware                                                                        |
| 6  | `accountant`      | Lead Accountant        | API middleware (finance group)                                                        |
| 7  | `staff`           | Operations Staff       | API middleware (ops group); default value of `users.role`                             |
| 8  | `client`          | Client Portal User     | Portal routes only                                                                    |
| 9  | `vendor`          | Vendor Partner         | Portal routes only                                                                    |

**API role groups** (from `routes/api.php`): `staff` = all internal
roles; `admins` = super\_admin, company\_admin, admin; `finance` =
admins + accountant; `sales` = admins + event\_manager +
sales\_executive; `ops` = admins + event\_manager + staff.

> `[Backend Gap / Clarification Needed]` The legacy **web routes have no
> role middleware at all** — the whole `routes/web.php` authenticated
> group is gated only by `auth`, so any logged-in user (including a
> `client`- or `vendor`-role login) can open every screen and POST every
> form. RBAC exists only in the `/api/v1` layer. The frontend must
> therefore (a) consume `/api/v1`, and (b) still treat role checks as UX
> gating only — the backend must stay the enforcer. Separately, the
> `roles`/`permissions` tables are seeded but never referenced by any
> middleware, gate or policy; permission slugs per module do not exist
> yet. If granular permissions are required, that is new backend work.

## 2.2 Access-control matrix (derived from the `/api/v1` role middleware)

**C**reate / **R**ead / **U**pdate / **D**elete / **S**pecial actions.
`✓` = allowed, `–` = denied (403).

| Module / Feature         | Super Admin / Company Admin                  | Event Manager | Sales Executive                 | Accountant | Staff (Ops)  | Client Portal    | Vendor Portal         |
| ------------------------ | -------------------------------------------- | ------------- | ------------------------------- | ---------- | ------------ | ---------------- | --------------------- |
| Dashboard & KPIs         | CRUD n/a — R ✓                               | R ✓           | R ✓                             | R ✓        | R ✓          | –                | –                     |
| Leads                    | C R U D ✓; S: status, convert                | C R U ✓; S ✓  | C R U ✓; S ✓                    | R ✓        | R ✓          | –                | –                     |
| Clients                  | C R U D ✓                                    | C R U ✓       | C R U ✓                         | R ✓        | R ✓          | – (portal R own) | –                     |
| Events                   | C R U D ✓; S: status, schedule, task, toggle | C R U ✓; S ✓  | R ✓                             | R ✓        | C R U ✓; S ✓ | – (portal R own) | – (portal R assigned) |
| Venues                   | C R U D ✓                                    | R ✓           | R ✓                             | R ✓        | R ✓          | –                | –                     |
| Vendors                  | C R U D ✓                                    | C R U ✓       | R ✓                             | R ✓        | C R U ✓      | –                | – (portal R own)      |
| Quotations               | C R D ✓ (no U endpoint)                      | C R ✓         | C R ✓                           | R ✓        | R ✓          | portal R own     | –                     |
| Invoices                 | C R D ✓; S: record payment                   | R ✓           | R ✓                             | C R ✓; S ✓ | R ✓          | portal R own     | –                     |
| Payments                 | C (via invoice) R ✓                          | R ✓           | R ✓                             | C R ✓      | R ✓          | portal R own     | –                     |
| Expenses                 | C R ✓; S: approve                            | R ✓           | R ✓                             | C R ✓; S ✓ | R ✓          | –                | –                     |
| Guests & RSVP            | C R ✓; S: check-in toggle                    | C R ✓; S ✓    | R ✓                             | R ✓        | C R ✓; S ✓   | –                | –                     |
| Tickets & tiers          | C R ✓; S: verify/scan                        | C R ✓; S ✓    | R ✓; S ✓ (verify is staff-wide) | R ✓        | C R ✓; S ✓   | –                | –                     |
| Client portal            | R ✓ (admins)                                 | –             | –                               | –          | –            | R ✓              | –                     |
| Vendor portal            | R ✓ (admins)                                 | –             | –                               | –          | –            | –                | R ✓                   |
| Users / roles / settings | **No API exists** — gap (Slice 12)           | –             | –                               | –          | –            | –                | –                     |

# 3\. Vertical Functional Slices (Core Modules)

Twelve slices, ordered by dependency. Each slice is self-contained:
goal, stories, screens, workflows, API contracts, ERD notes.

## Slice 1 — Authentication, Session & Tenant Context

### 1\. Slice Goal & Business Value

Every user signs in and lands in their company workspace with the right
role. The frontend learns “who am I, what role, which company/branch”
once, then gates navigation and actions for the whole session.

### 2\. User Stories & Acceptance Criteria

  - *As any user, I want to log in with email and password so that I get
    a token and reach the dashboard.* **AC:** wrong credentials show the
    backend error “The provided credentials do not match our records.”;
    success stores the token and fetches `GET /auth/me`.
  - *As any user, I want to log out so that my token is revoked.*
    **AC:** `POST /auth/logout`, local token cleared, redirect to
    `/login`.
  - *As a demo/eval user, I want one-click demo logins so that I can
    explore each role.* **AC:** login screen shows the seeded demo
    accounts (web equivalent: `GET /demo-login/{id}` — see gap).

### 3\. Frontend Screens & UI Components

  - **Login page** — fields: `email` (email, required), `password`
    (string, required), optional `device_name` (string ≤255, default =
    device/browser label). Demo-account chips (seeded users below).
    States: submitting spinner, inline credential error,
    redirect-if-authenticated.
  - **App shell bootstrap** — on load call `GET /auth/me`; show skeleton
    shell until resolved; on 401 force login.
  - **User menu / signed-in footer** — display `name`, role label,
    company/branch context; “Sign out”.
  - Seeded demo users (all password `password`): `admin@eventflow.test`
    (super\_admin), `manager@eventflow.test` (event\_manager),
    `sales@eventflow.test` (sales\_executive), `finance@eventflow.test`
    (accountant), `client@eventflow.test` (client),
    `vendor@eventflow.test` (vendor).

### 4\. State Transitions & Workflows

`logged out → (POST /auth/login) → token issued → (GET /auth/me) →
authenticated` → any 401 or logout returns to logged out. Web layer
additionally regenerates the session on login/logout (CSRF-based; not
applicable to the SPA).

### 5\. API Contract Mapping

| HTTP Method | Endpoint              | Auth/Role | Request Payload / Params            | Response Shape                                                                         |
| ----------- | --------------------- | --------- | ----------------------------------- | -------------------------------------------------------------------------------------- |
| POST        | `/api/v1/auth/login`  | public    | `{email*, password*, device_name?}` | `201/200 {token, user: UserResource}`                                                  |
| GET         | `/api/v1/auth/me`     | bearer    | —                                   | `{data: {id, name, email, phone, role, status, company_id, branch_id}}` (UserResource) |
| POST        | `/api/v1/auth/logout` | bearer    | —                                   | `200 {message}`; token revoked                                                         |

### 6\. ERD Extraction Notes

  - **users** (`id, company_id?, branch_id?, name, email (unique),
    phone?, role='staff', status='active', email_verified_at?, password,
    timestamps`) — belongsTo **companies**, **branches**; hasOne
    **employees**; hasMany events (as `manager_id`), event\_tasks
    (`assigned_to`), leads (`assigned_to`).
  - Foundation/tenant tables (no auth endpoints, consumed as reference
    data): **companies** (`code unique, currency='INR',
    currency_symbol='₹', tax_number, logo_path, status`), **branches**
    (`company_id FK→companies cascade, is_main_branch, is_active`),
    **departments** (`company_id FK`), **roles** (`company_id? FK
    nullOnDelete, slug`), **permissions** (`slug unique, module`),
    **employees** (`company_id, branch_id?, department_id?, user_id?
    FKs; employee_code, designation, salary(12,2), joining_date,
    status`), **system\_settings** (`company_id? FK cascade, key,
    value`).
  - Password reset table (`password_reset_tokens`) exists but **no
    routes use it**.

> `[Backend Gap / Clarification Needed]` (a) No registration,
> profile-update, password-change or password-reset endpoints — login
> only. (b) `/demo-login/{id}` authenticates any user id **without a
> password**; it must be removed or env-gated before production. (c)
> `roles`/`permissions` tables are inert (no role↔permission or
> user↔role pivots) — authorization is the hardcoded `users.role`
> string. (d) There is no user-management CRUD for admins; adding staff
> is seeder-only today.

## Slice 2 — CRM: Leads, Lead Pipeline & Clients

### 1\. Slice Goal & Business Value

Capture enquiries, work them through a 7-stage sales pipeline, log every
interaction, and convert a won lead into a Client (then straight into an
Event) — the revenue front door.

### 2\. User Stories & Acceptance Criteria

  - *As a sales executive, I want to create a lead with contact, source,
    event type/date, guest estimate and budget so that enquiries are
    never lost.* **AC:** required `title`, `contact_name`, `source`; on
    save, status is forced to `new` and a `LeadActivity` note (“Lead
    created in system from source: X”) is auto-written.
  - *As a sales executive, I want to move a lead along the pipeline so
    that the team sees live status.* **AC:** status change posts one of
    `new, contacted, qualified, proposal_sent, negotiating, converted,
    lost`; a `LeadActivity` note “Lead status updated to X” is
    auto-written.
  - *As a sales executive, I want one-click conversion so that a won
    lead becomes a client without retyping.* **AC:** POST convert
    creates a `Client` (`CL-####` code, `client_type=individual`, notes
    referencing the lead), links `leads.client_id`, sets status
    `converted`, and routes the user to event creation.
  - *As a manager, I want to filter leads by status and source and see
    who owns each lead so that I can balance the pipeline.* **AC:**
    filters `status`, `source`; list shows assignee; 15/page, newest
    first.
  - *As an admin, I want full client CRUD with search so that account
    data stays clean.* **AC:** search matches `name, company_name,
    email, phone`; client detail shows related events (with venue),
    proposals, quotations, invoices (with payments).

### 3\. Frontend Screens & UI Components

  - **Leads list** — table/cards: title, contact, source badge, event
    type/date, est. budget (₹), status pill, assignee, created date.
    Filters: status (7 values), source (6 values). Empty state “No leads
    yet”; pagination 15.
  - **Lead create/edit form** — fields with backend rules: `title*`
    (string ≤255), `contact_name*` (≤255), `email` (email), `phone`,
    `source*` (select: website, referral, direct, social\_media, phone,
    exhibition), `event_type`, `event_date` (date), `estimated_guests`
    (int), `estimated_budget` (numeric), `assigned_to` (FK users),
    `requirements` (text), `notes` (text).
  - **Lead detail / pipeline board** — status stepper with the 7 states,
    activity timeline from `lead_activities` (`activity_type`: call,
    email, meeting, note, site\_visit; `scheduled_at`; `status`:
    pending/completed/cancelled), **Convert** button (confirm dialog →
    success toast → navigate to New Event prefilled).
  - **Clients list** — search box + 12/page; columns: code, name,
    company, type badge (individual, corporate, wedding, agency),
    contact, city/state, counts (events, invoices), status.
  - **Client create/edit form** — `name*`, `company_name`, `email`
    (email), `phone`, `alternate_phone`, `address`, `city`, `state`,
    `postal_code`, `tax_number` (GSTIN), `client_type*` (select),
    `notes`; edit adds `status*` (active/inactive). Client code
    auto-generated.
  - **Client 360 detail** — tabs: Events, Proposals, Quotations,
    Invoices & Payments, Notes.
  - Key UI states: status pills per pipeline stage; loading skeletons on
    tabs; 422 field errors inline.

### 4\. State Transitions & Workflows

Lead: `new → contacted → qualified → proposal_sent → negotiating →
converted | lost` (free-form transitions allowed — backend accepts any
status string; the UI should present the canonical set). Conversion
workflow: `lead (any status) → POST convert → new client +
lead.status=converted → New Event form`. Every status change and
creation appends a `lead_activities` row (audit trail the UI renders as
the timeline).

### 5\. API Contract Mapping

| HTTP Method | Endpoint                     | Auth/Role | Request Payload / Params                       | Response Shape                                             |
| ----------- | ---------------------------- | --------- | ---------------------------------------------- | ---------------------------------------------------------- |
| GET         | `/api/v1/leads`              | staff     | `?status, ?search` (title/contact\_name), page | paginated LeadResource\[\] (15/pg)                         |
| POST        | `/api/v1/leads`              | sales     | lead fields (above)                            | `201 {data: LeadResource}` (status forced `new`)           |
| GET         | `/api/v1/leads/{id}`         | staff     | —                                              | `{data: LeadResource}` incl. `assigned_user`               |
| PUT/PATCH   | `/api/v1/leads/{id}`         | sales     | partial lead fields                            | `{data: LeadResource}`                                     |
| DELETE      | `/api/v1/leads/{id}`         | admins    | —                                              | `200/204`                                                  |
| POST        | `/api/v1/leads/{id}/status`  | sales     | `{status*}`                                    | updated lead; activity logged                              |
| POST        | `/api/v1/leads/{id}/convert` | sales     | —                                              | created client + lead `converted`; UI then opens New Event |
| GET         | `/api/v1/clients`            | staff     | `?search`                                      | paginated ClientResource\[\] (12/pg), with counts          |
| POST        | `/api/v1/clients`            | sales     | client fields (above)                          | `201` (code `CL-####` auto)                                |
| GET         | `/api/v1/clients/{id}`       | staff     | —                                              | client + events/proposals/quotations/invoices              |
| PUT/PATCH   | `/api/v1/clients/{id}`       | sales     | partial + `status`                             | `{data: ClientResource}`                                   |
| DELETE      | `/api/v1/clients/{id}`       | admins    | —                                              | `200/204`                                                  |

### 6\. ERD Extraction Notes

  - **leads** (`company_id FK→companies cascade, branch_id?, client_id?
    FK→clients nullOnDelete, assigned_to? FK→users, title, contact_name,
    email?, phone?, source='website', event_type?, event_date?,
    estimated_guests?, estimated_budget(12,2)=0, status='new',
    requirements?, notes?`).
  - **lead\_activities** (`lead_id FK→leads cascade, user_id? FK→users,
    activity_type='note', scheduled_at?, details?, status='completed'`).
    1 lead : N activities.
  - **clients** (`company_id FK, branch_id?, user_id? FK→users (portal
    login link — currently unused, see Slice 12), client_code?, name,
    company_name?, email?, phone?, alternate_phone?, address?, city?,
    state?, postal_code?, tax_number?, client_type='individual',
    status='active', notes?`). 1 client : N events, proposals,
    quotations, invoices.

> `[Backend Gap / Clarification Needed]` (a) `POST convert` **always
> creates a new Client** even when `leads.client_id` is already set —
> possible duplicates; confirm desired merge behavior. (b) No endpoint
> to add `lead_activities` manually (calls, meetings, follow-up
> scheduling) although the table supports `scheduled_at` and pending
> status — the timeline is currently write-only by the system. (c) Lead
> status accepts any string; enforce enum server-side if strict
> pipelines matter. (d) No lead edit/delete in the legacy web UI
> (index/create/status/convert only) — the API adds update/destroy.

## Slice 3 — Event Lifecycle, Run of Show & Production Tasks

### 1\. Slice Goal & Business Value

The operational core: register an event, track its lifecycle from
enquiry to completion, plan minute-by-minute run-of-show schedules per
event day, and drive production tasks with priorities and completion
toggles.

### 2\. User Stories & Acceptance Criteria

  - *As an event manager, I want to create an event with client,
    category, type, venue, manager, dates, guests and budgets so that
    production can start.* **AC:** `end_date ≥ start_date` enforced;
    system generates code `EVT-YYYY-XXXXX` and auto-creates “Day 1 Main
    Proceedings” event day.
  - *As an event manager, I want to change event status so that the
    portfolio view stays true.* **AC:** statuses `enquiry, proposed,
    confirmed, in_planning, active, completed, cancelled`; change posts
    and shows a toast.
  - *As an event manager, I want to add run-of-show items with time,
    title, performer, location-in-venue and in-charge so that show flow
    is exact.* **AC:** `start_time` required; item appears under its
    event day, ordered by time.
  - *As operations staff, I want to add and toggle tasks so that
    checklists clear live.* **AC:** priorities `low, medium, high,
    urgent`; toggle flips `pending ↔ completed` and re-renders
    immediately.
  - *As any staff user, I want to search/filter the event portfolio so
    that I can find any production fast.* **AC:** search matches title,
    event code, client name; filters: status, category; 12/page,
    `start_date` desc.

### 3\. Frontend Screens & UI Components

  - **Events portfolio (list)** — cards/table: code, title, client,
    category, venue, dates, status pill, contract value. Filters:
    status, `category_id`, search. “New Event” CTA (ops roles).
  - **Event create/edit form** — fields: `title*`, `client_id*` (FK
    picker), `event_category_id*` (FK), `event_type*` (select: wedding,
    conference, birthday, concert, festival, exhibition,
    product\_launch, corporate, seminar, private\_party), `venue_id`
    (FK; create form lists only `status=available` venues), `manager_id`
    (FK users), `start_date*` (datetime), `end_date*` (datetime, ≥
    start), `expected_guests` (int ≥0), `confirmed_guests` (edit only),
    `estimated_budget`, `actual_budget` (edit), `contract_value` (₹),
    `status*` (select), `theme_concept`, `description`,
    `special_instructions` (edit).
  - **Event detail (tabbed)** — Overview (financials: contract vs
    received vs outstanding; guest counts), **Run of Show** (days →
    schedules with `start_time, end_time, title, location_within_venue,
    speaker_performer, in_charge_person, status:
    scheduled/in_progress/completed/delayed`; inline add form),
    **Tasks** (priority pills, due dates, assignee, Done/Undo toggle),
    plus cross-links into vendors, budget, expenses, guests, tickets,
    invoices (Slices 4–10 render here as tabs or deep links).
  - Key UI states: status pill colors per lifecycle; task priority
    badges (urgent/high emphasized); optimistic toggle with rollback on
    error; empty states per tab.

### 4\. State Transitions & Workflows

Event: `enquiry → proposed → confirmed → in_planning → active →
completed` (or `cancelled`) — free-form via `POST /events/{id}/status`.
Task: `pending ↔ completed` toggle (schema also allows `in_progress`,
`blocked` — set via data, not the toggle). Schedule item: `scheduled →
in_progress → completed | delayed`. Side effects on create: event day 1
auto-created. Ticket sales increment `events.confirmed_guests`; invoice
payments increment `events.amount_received` (Slices 9–10).

### 5\. API Contract Mapping

| HTTP Method | Endpoint                        | Auth/Role          | Request Payload / Params                                                                                                                | Response Shape                                                                                                                                                                                            |
| ----------- | ------------------------------- | ------------------ | --------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET         | `/api/v1/events`                | staff              | `?status, ?category_id, ?search`                                                                                                        | paginated EventResource\[\] (12/pg)                                                                                                                                                                       |
| POST        | `/api/v1/events`                | ops+event\_manager | event fields (above)                                                                                                                    | `201` + code; day 1 auto-created                                                                                                                                                                          |
| GET         | `/api/v1/events/{id}`           | staff              | —                                                                                                                                       | event with client, category, venue, manager, days.schedules, tasks.assignedUser, vendorAssignments, budget.items, expenses, guests.group, ticketTypes, invoices.payments, cateringMenus, decorationThemes |
| PUT/PATCH   | `/api/v1/events/{id}`           | ops                | partial event fields                                                                                                                    | updated EventResource                                                                                                                                                                                     |
| DELETE      | `/api/v1/events/{id}`           | admins             | —                                                                                                                                       | cascades to days/schedules/tasks/etc.                                                                                                                                                                     |
| POST        | `/api/v1/events/{id}/status`    | ops                | `{status*}`                                                                                                                             | updated event                                                                                                                                                                                             |
| POST        | `/api/v1/events/{id}/schedules` | ops                | `{event_day_id?, start_time*, end_time?, title*, location_within_venue?, speaker_performer?, activity_description?, in_charge_person?}` | created schedule item                                                                                                                                                                                     |
| POST        | `/api/v1/events/{id}/tasks`     | ops                | `{title*, assigned_to?, due_date?, priority*, description?}`                                                                            | created task (status `pending`)                                                                                                                                                                           |
| POST        | `/api/v1/tasks/{id}/toggle`     | ops                | —                                                                                                                                       | task flipped `pending ↔ completed`                                                                                                                                                                        |

### 6\. ERD Extraction Notes

  - **events** (`company_id FK, branch_id?, client_id? FK→clients,
    event_category_id? FK→event_categories, venue_id? FK→venues,
    manager_id? FK→users; event_code unique, title,
    event_type='wedding', start_date/end_date datetime,
    expected_guests=0, confirmed_guests=0,
    estimated_budget/actual_budget/contract_value/amount_received
    (12,2), status='enquiry', theme_concept?, description?,
    special_instructions?`).
  - **event\_categories** (`company_id FK, name, slug, icon?,
    description?`) — 1:N events.
  - **event\_days** (`event_id FK cascade, day_number=1, date, title?,
    description?`) — 1:N **event\_schedules** (`event_id FK cascade,
    event_day_id? FK, start_time, end_time?, title,
    location_within_venue?, speaker_performer?, activity_description?,
    in_charge_person?, status='scheduled'`).
  - **event\_tasks** (`event_id FK cascade, assigned_to? FK→users,
    title, description?, due_date?, priority='medium',
    status='pending'`).

## \> `[Backend Gap / Clarification Needed]` (a) No endpoints to edit/delete schedules or tasks individually, no endpoint to add extra event days (only day 1 auto-created) — multi-day events are schema-supported but not UI/API-manageable yet. (b) No endpoint to update an event schedule’s status (`in_progress`/`delayed` unreachable from UI). (c) No `event_categories` CRUD anywhere — seeded only. (d) `updateStatus` accepts any string; no transition guard (e.g. completed → enquiry is possible).

## Slice 4 — Venues & Vendor Operations

### 1\. Slice Goal & Business Value

Maintain the partner venue directory and vendor network, then tie both
to events: venue bookings with rental fees/advances, and vendor work
orders with scope, contract value and disbursement tracking.

### 2\. User Stories & Acceptance Criteria

  - *As an admin, I want to onboard venues with type, capacity, city and
    day rate so that event planners can pick the right space.* **AC:**
    required `name, type, capacity≥1, rental_cost_per_day≥0, city`; code
    `VEN-###` auto; status starts `available`.
  - *As a planner, I want to filter venues by type and city so that
    shortlisting is fast.* **AC:** filters `type` (banquet\_hall, hotel,
    lawn, resort, convention\_centre, stadium, auditorium), `city`;
    12/page; venue detail shows its events and bookings.
  - *As operations staff, I want to register vendors under categories
    with GST number, payment terms, bank details and services so that
    contracting has the data it needs.* **AC:** `vendor_category_id`
    required; code `VND-###` auto; rating defaults 5.00; statuses
    `active, inactive, blacklisted`.
  - *As an event manager, I want to see a vendor’s assignments and
    expense history so that I can judge reliability.* **AC:** vendor
    detail lists assignments (per event, with `contract_amount` vs
    `paid_amount`) and expenses.

### 3\. Frontend Screens & UI Components

  - **Venues list** — filter bar (type select, city text), card grid:
    name, code, type badge, capacity, city/state, ₹/day, status pill
    (available/booked/maintenance/inactive), events & bookings counts.
    **Venue form:** `name*`, `type*` select, `capacity*` int,
    `rental_cost_per_day*` ₹, `contact_person/phone/email`, `address`,
    `city*`, `state`, `amenities` (text); edit adds `status*`. **Venue
    detail:** profile + upcoming events + bookings table
    (`booking_start/end, rental_fee, advance_paid, status:
    reserved/confirmed/cancelled`).
  - **Vendors list** — filters: category, status; cards: name/business
    name, category, city, rating (3,2), counts of assignments/expenses,
    status pill. **Vendor form:** `name*`, `business_name`,
    `vendor_category_id*` (FK), `email`, `phone`, `alternate_phone`,
    `address`, `city`, `tax_number`, `payment_terms`, `bank_details`
    (text — mask in UI), `services_offered`; edit adds `status*`.
    **Vendor detail:** profile + work orders + expense history.
  - **Event detail → Vendors tab** — assignment cards per vendor:
    service title, scope of work, contract vs paid, status pill
    (assigned/contracted/in\_progress/delivered/completed).

### 4\. State Transitions & Workflows

Venue: `available ↔ booked | maintenance | inactive`. Venue booking:
`reserved → confirmed → cancelled`. Vendor assignment: `assigned →
contracted → in_progress → delivered → completed`. Vendor status:
`active ↔ inactive | blacklisted`. No transition guards server-side
(free-form strings) — the UI should present canonical next steps.

### 5\. API Contract Mapping

| HTTP Method          | Endpoint               | Auth/Role                   | Request Payload / Params           | Response Shape                            |
| -------------------- | ---------------------- | --------------------------- | ---------------------------------- | ----------------------------------------- |
| GET                  | `/api/v1/venues`       | staff                       | `?type, ?city`                     | paginated (12/pg), with counts            |
| POST                 | `/api/v1/venues`       | admins                      | venue fields (above)               | `201` (code auto)                         |
| GET/PUT/PATCH/DELETE | `/api/v1/venues/{id}`  | R: staff; U/D: admins       | venue fields + `status` on update  | VenueResource / `200/204`                 |
| GET                  | `/api/v1/vendors`      | staff                       | `?category_id, ?status`            | paginated (12/pg), category + counts      |
| POST                 | `/api/v1/vendors`      | ops                         | vendor fields (above)              | `201` (code auto, rating 5.0)             |
| GET/PUT/PATCH/DELETE | `/api/v1/vendors/{id}` | R: staff; U: ops; D: admins | vendor fields + `status` on update | VendorResource incl. assignments/expenses |

### 6\. ERD Extraction Notes

  - **venues** (`company_id FK, branch_id?, name, venue_code?,
    type='hotel', capacity=0, contact_person/phone/email?, address?,
    city?, state?, rental_cost_per_day(12,2)=0, amenities?,
    status='available'`) — 1:N events, 1:N **venue\_bookings**
    (`company_id, venue_id FK cascade, event_id? FK nullOnDelete,
    booking_start/end datetime, rental_fee(12,2), advance_paid(12,2),
    status='confirmed', notes?`).
  - **vendor\_categories** (`company_id FK, name, description?`) — 1:N
    **vendors** (`company_id FK, vendor_category_id? FK nullOnDelete,
    vendor_code?, name, business_name?, email?, phone?,
    alternate_phone?, address?, city?, tax_number?, rating(3,2)=5.00,
    payment_terms?, bank_details?, services_offered?, status='active'`).
  - **vendor\_assignments** (`company_id, event_id FK cascade, vendor_id
    FK cascade, service_title, scope_of_work?, contract_amount(12,2)=0,
    paid_amount(12,2)=0, status='assigned'`) — the N:M bridge between
    events and vendors with contract payload.

> `[Backend Gap / Clarification Needed]` (a) **No endpoints exist for
> venue\_bookings or vendor\_assignments** — both tables are populated
> by the seeder and rendered on detail pages, but there is no
> create/update route in web or `/api/v1`. The frontend can display them
> (they come embedded in event/venue/vendor detail payloads) but cannot
> create or edit bookings/work orders until backend routes land. (b) No
> `vendor_categories` CRUD. (c) Venue availability is a manual status
> flag, not derived from `venue_bookings` date overlaps — a “check
> availability for dates” feature needs backend work.

## Slice 5 — Service Catalog, Proposals & Quotations

### 1\. Slice Goal & Business Value

Price the deal: a catalog of services and packages feeds itemized
quotations with automatic subtotal, GST and grand total, printable for
the client. Proposals carry scope-of-work documents upstream of
quotations.

### 2\. User Stories & Acceptance Criteria

  - *As a sales executive, I want to build a quotation with line items
    so that pricing is consistent and tax-correct.* **AC:** at least one
    item required; each item `item_name*, quantity*≥1, unit_price*≥0`;
    server computes `subtotal`, `tax_amount = subtotal × tax_rate/100`,
    `grand_total`; number `QTN-YYYY-####`; status starts `draft`.
  - *As a sales executive, I want to print/share a quotation so that the
    client gets a clean document.* **AC:** print view shows items table,
    totals with ₹ and GST line, client/event references, validity date.
  - *As a manager, I want the quotations list with client, event, total
    and status so that I can chase open quotes.* **AC:** 15/page newest
    first; status pills (draft, sent, accepted, rejected, expired).
  - *As a salesperson, I want to preselect the event when creating a
    quotation from an event page.* **AC:** `?event_id=` deep link
    pre-fills the event picker (legacy web behavior
    `quotations.create?event_id`).

### 3\. Frontend Screens & UI Components

  - **Quotations list** — number, client, event, issue date, valid
    until, grand total, status pill; row → detail.
  - **Quotation builder** — client\* and event pickers, `issue_date*`,
    `valid_until`, `tax_rate*` (default **18.00**, numeric), `notes`;
    dynamic **line-item editor** (add/remove rows: `item_name`,
    `quantity`, `unit_price`, live `total_price`; item types per schema:
    service, package, venue, equipment, other — API stores `service` by
    default); live totals panel (subtotal → GST → grand total) that
    mirrors server math exactly.
  - **Quotation detail** — items table, totals, status, notes, **Print**
    button.
  - **Print view** — clean white GST-style document (Blade equivalent
    `quotations.print`); in the SPA either a print-stylesheet route or
    server PDF (see gap).
  - Reference data (read-only for now): services (`name, code,
    base_price, unit: per_event/per_person/per_hour/per_day/per_sqft,
    is_active`), packages (`package_type:
    standard/premium/luxury/customized, base_price,
    guest_count_included=100, features`).

### 4\. State Transitions & Workflows

Quotation: `draft → sent → accepted | rejected | expired` — **no
endpoint performs these transitions today** (create lands in `draft`;
legacy web has no status route either). Proposal: `draft → sent →
approved | rejected | revised`. Both lifecycles are schema-defined and
seeder-populated; the UI should render the pills and be ready for
transition endpoints.

### 5\. API Contract Mapping

| HTTP Method | Endpoint                  | Auth/Role    | Request Payload / Params                                                                                                    | Response Shape                                   |
| ----------- | ------------------------- | ------------ | --------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------ |
| GET         | `/api/v1/quotations`      | staff        | —                                                                                                                           | paginated (15/pg) with client, event             |
| POST        | `/api/v1/quotations`      | sales        | `{client_id*, event_id?, issue_date*, valid_until?, tax_rate*, notes?, items*: [{item_name*, quantity*≥1, unit_price*≥0}]}` | `201` (totals computed server-side, number auto) |
| GET         | `/api/v1/quotations/{id}` | staff        | —                                                                                                                           | quotation + items + client + event               |
| DELETE      | `/api/v1/quotations/{id}` | admins       | —                                                                                                                           | `200/204`                                        |
| GET (web)   | `/quotations/{id}/print`  | session auth | —                                                                                                                           | print HTML (legacy; SPA needs equivalent)        |

### 6\. ERD Extraction Notes

  - **service\_categories** 1:N **services** (`company_id,
    service_category_id? FK, name, code?, base_price(12,2),
    unit='per_event', description?, is_active=true`).
  - **event\_packages** (`company_id, event_category_id?
    FK→event_categories, name, package_type='standard',
    base_price(12,2), guest_count_included=100, features?, description?,
    is_active=true`).
  - **proposals** (`company_id, branch_id?, client_id FK cascade,
    event_id? FK, proposal_number unique, title, total_amount(12,2),
    valid_until?, status='draft', scope_of_work?, deliverables?,
    terms_and_conditions?`).
  - **quotations** (`company_id, branch_id?, client_id FK cascade,
    event_id? FK, proposal_id? FK→proposals nullOnDelete,
    quotation_number unique, issue_date, valid_until?,
    subtotal/discount/tax_amount/grand_total (12,2),
    tax_rate(5,2)=18.00, status='draft', notes?`) — 1:N
    **quotation\_items** (`quotation_id FK cascade, item_name,
    item_type='service', description?, quantity(10,2)=1, unit='unit',
    unit_price(12,2), total_price(12,2)`).

> `[Backend Gap / Clarification Needed]` (a) **No proposal endpoints at
> all** (web or API) — model/table only; proposals render inside client
> detail. (b) No quotation update/edit, no status-transition endpoint,
> no “convert accepted quotation → invoice” action. (c) No
> services/packages CRUD — quotation items are free-text, not
> catalog-linked. (d) `discount` column exists but is hardcoded 0 on
> create — no UI input reaches it. (e) Print is a Blade view; for the
> SPA, either add a PDF download endpoint (dompdf is installed but
> unused) or build a client-side print stylesheet.

## Slice 6 — Budgets, Expenses & Financial Control

### 1\. Slice Goal & Business Value

Per-event budgets with category line items and contingency, plus
company-wide expense recording with approval — giving finance
estimated-vs-actual variance control.

### 2\. User Stories & Acceptance Criteria

  - *As an accountant, I want to record an expense against an
    event/category/vendor with amount, date, method and receipt ref so
    that spend is auditable.* **AC:** `title*, amount*≥1,
    incurred_date*, payment_method*` required; number `EXP-YYYY-####`
    auto.
  - *As an accountant, I want to approve pending expenses so that only
    sanctioned spend counts.* **AC:** approve sets `status=approved` and
    `approved_by=current user`.
  - *As any staff user, I want to filter expenses by event and status so
    that reviews are quick.* **AC:** filters `event_id`, `status`;
    15/page, `incurred_date` desc.
  - *As an event manager, I want the event’s budget with per-category
    estimated vs actual and contingency so that I can spot overruns.*
    **AC:** event detail budget tab renders `event_budgets` + items with
    variance and `contingency_percent` (default 5%).

### 3\. Frontend Screens & UI Components

  - **Expenses list** — number, title, event, category, vendor, amount,
    method badge (cash, bank\_transfer, card, upi, cheque), receipt no.,
    incurred date, status pill (pending, approved, paid, rejected),
    approver. **Record Expense** modal/form with the fields above +
    `expense_category_id`, `vendor_id`, `notes`. **Approve** action
    button on pending rows (finance roles).
  - **Event detail → Budget tab** — budget summary card (estimated
    income, estimated cost, actual cost, contingency % / amount, status:
    draft/approved/active/locked) + category items table (`category`:
    venue, catering, decoration, audio\_visual, photography,
    entertainment, staff, logistics; `estimated_cost` vs `actual_cost`,
    variance column, notes).

### 4\. State Transitions & Workflows

Expense: `pending → approved → paid` (or `rejected`). Note: the current
store action sets new expenses straight to `paid` with `approved_by =
creator` — the pending→approved flow only applies to rows seeded as
pending. Budget: `draft → approved → active → locked` (schema-defined;
no endpoints).

### 5\. API Contract Mapping

| HTTP Method | Endpoint                        | Auth/Role | Request Payload / Params                                                                                                     | Response Shape                                            |
| ----------- | ------------------------------- | --------- | ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| GET         | `/api/v1/expenses`              | staff     | `?event_id, ?status`                                                                                                         | paginated (15/pg) incl. event, category, vendor, approver |
| POST        | `/api/v1/expenses`              | finance   | `{event_id?, expense_category_id?, vendor_id?, title*, amount*≥1, incurred_date*, payment_method*, receipt_number?, notes?}` | `201` (number auto)                                       |
| POST        | `/api/v1/expenses/{id}/approve` | finance   | —                                                                                                                            | expense `approved` + approver set                         |

### 6\. ERD Extraction Notes

  - **event\_budgets** (`company_id, event_id FK cascade,
    total_estimated_income/total_estimated_cost/total_actual_cost
    (12,2), contingency_percent(5,2)=5.00, contingency_amount(12,2),
    status='draft'`) — 1:N **event\_budget\_items** (`event_budget_id FK
    cascade, category, item_name, estimated_cost, actual_cost, notes?`).
  - **expense\_categories** (`company_id, name`) — 1:N
    **event\_expenses** (`company_id, event_id? FK nullOnDelete,
    expense_category_id? FK, vendor_id? FK, approved_by? FK→users,
    expense_number?, title, amount(12,2), incurred_date,
    payment_method='bank_transfer', receipt_number?, receipt_path?,
    status='approved', notes?`).

## \> `[Backend Gap / Clarification Needed]` (a) **No endpoints for budgets or budget items** — read-only via the event detail payload; creating/editing a budget is seeder-only. (b) Expense store skips the pending state (auto-`paid`), so the approve endpoint currently only affects seeded pending rows — confirm intended flow. (c) `receipt_path` exists but there is **no file-upload endpoint** for receipts. (d) No expense update/delete/reject endpoints. (e) No `expense_categories` CRUD.

## Slice 7 — Inventory, Equipment & Event Resources

### 1\. Slice Goal & Business Value

Track owned/rentable equipment (sound, lighting, decor, furniture), its
quantities, condition and rental rates, and allocate stock to events
with checkout/return lifecycle.

### 2\. User Stories & Acceptance Criteria

  - *As operations staff, I want to add inventory items with category,
    quantity, unit, rental rate and condition so that the catalog stays
    current.* **AC:** `inventory_category_id*, name*, total_quantity*≥1,
    unit*, rental_rate*≥0, condition*` required; `available_quantity`
    initialized to total; code `INV-###` auto.
  - *As operations staff, I want to filter items by category and see how
    many units are allocated so that I know what’s free.* **AC:** filter
    `category_id`; list shows `allocations_count`, available vs total,
    condition pill (good/fair/maintenance\_required); 15/page.

### 3\. Frontend Screens & UI Components

  - **Inventory list** — item code, name, category, available/total
    quantity (warn when available=0), unit (pcs/sets/meters/boxes),
    rental rate ₹, replacement cost, condition pill, allocations count.
    **Add item** form with the fields above.
  - **Event detail → Resources tab** (read from event payload):
    allocations (`quantity_allocated, checkout_time, return_time,
    status: allocated/checked_out/returned/damaged`), catering menus
    (`menu_name, meal_type: breakfast/lunch/high_tea/dinner/cocktail,
    per_plate_cost, expected_plates, menu_items, status`), decoration
    themes (`theme_name, color_palette, floral_requirements,
    lighting_concept, estimated_cost, status`), staff shifts
    (`role_title, shift_date, start/end_time, attendance_status:
    scheduled/present/late/absent, allowance`).

### 4\. State Transitions & Workflows

Allocation: `allocated → checked_out → returned | damaged`. Staff shift
attendance: `scheduled → present | late | absent`. Item condition: `good
| fair | maintenance_required`. All schema-defined; only item create has
an endpoint.

### 5\. API Contract Mapping

| HTTP Method | Endpoint     | Auth/Role    | Request Payload / Params | Response Shape                 |
| ----------- | ------------ | ------------ | ------------------------ | ------------------------------ |
| GET (web)   | `/inventory` | session auth | `?category_id`           | paginated (15/pg) + categories |
| POST (web)  | `/inventory` | session auth | item fields (above)      | redirect + flash               |

> The `/api/v1` layer has **no inventory routes** — this slice is the
> clearest API gap.

### 6\. ERD Extraction Notes

  - **inventory\_categories** 1:N **inventory\_items** (`company_id,
    inventory_category_id? FK, item_code?, name, total_quantity=0,
    available_quantity=0, unit='pcs', rental_rate(12,2),
    replacement_cost(12,2), condition='good'`).
  - **inventory\_allocations** (`company_id, event_id FK cascade,
    inventory_item_id FK cascade, quantity_allocated=1, checkout_time?,
    return_time?, status='allocated'`) — N:M bridge events ↔ items.
  - **catering\_menus** (`company_id, event_id FK cascade, menu_name,
    meal_type='dinner', per_plate_cost(10,2), expected_plates=0,
    menu_items? longText, status='confirmed'`).
  - **decoration\_themes** (`company_id, event_id FK cascade,
    theme_name, color_palette?, floral_requirements?, lighting_concept?,
    estimated_cost(12,2), status='approved'`).
  - **staff\_shifts** (`company_id, event_id FK cascade, user_id?
    FK→users, employee_id? FK→employees, role_title, shift_date,
    start_time, end_time, attendance_status='scheduled',
    allowance(10,2)=0`).

> `[Backend Gap / Clarification Needed]` Entire slice is read-only from
> the frontend except “add item” on the legacy web UI: no endpoints for
> allocations, catering menus, decoration themes, staff shifts, item
> update/delete, or quantity adjustments (`available_quantity` is set
> once at creation and never decremented by allocations — there’s no
> code linking the two). Phase this slice after the backend adds routes.

## Slice 8 — Guest Logistics, RSVP & Check-in

### 1\. Slice Goal & Business Value

Build per-event guest lists organized in groups (VIP, Family, Media,
Corporate Delegates), auto-issue unique invitation codes, track RSVP and
dietary needs, and run door check-in.

### 2\. User Stories & Acceptance Criteria

  - *As hospitality staff, I want to add a guest to an event (optionally
    a group) so that invitations can go out.* **AC:** `event_id*, name*,
    dietary_preference*` required; system generates unique
    `invitation_code` (`GUEST-XXXXXX`), RSVP starts `pending`,
    `checked_in=false`.
  - *As hospitality staff, I want to filter guests by event and RSVP
    status and search by name/email/code so that door and follow-up
    lists are instant.* **AC:** filters `event_id`, `rsvp_status`;
    search on name/email/invitation\_code; 20/page A→Z.
  - *As door staff, I want one-tap check-in (and revert) so that
    attendance is live.* **AC:** toggle flips `checked_in`,
    stamps/clears `checked_in_at`, toast confirms by guest name.

### 3\. Frontend Screens & UI Components

  - **Guests list** — event filter, RSVP filter (pending, attending,
    declined, tentative), search; columns: name, group badge, contact,
    invitation code (mono), plus-ones, table no., dietary badge
    (standard, veg, non\_veg, vegan, jain, gluten\_free), RSVP pill,
    checked-in state. **Add Guest** form: `event_id*`, `guest_group_id`
    (FK), `name*`, `email`, `phone`, `plus_ones` (int ≥0),
    `table_number`, `dietary_preference*`.
  - **Check-in action** — per-row toggle button with loading state;
    optimistic update acceptable with rollback.
  - **Event detail → Guests tab** — group-sectioned guest list with the
    same actions.

### 4\. State Transitions & Workflows

RSVP: `pending → attending | declined | tentative` (schema + filters; no
status endpoint exists — set via data/seeder). Check-in: `checked_in
false ↔ true` with `checked_in_at` timestamp set/cleared.

### 5\. API Contract Mapping

| HTTP Method | Endpoint                      | Auth/Role | Request Payload / Params           | Response Shape                                   |
| ----------- | ----------------------------- | --------- | ---------------------------------- | ------------------------------------------------ |
| GET         | `/api/v1/guests`              | staff     | `?event_id, ?rsvp_status, ?search` | paginated (20/pg) incl. event, group             |
| POST        | `/api/v1/guests`              | ops       | guest fields (above)               | `201` (invitation code auto)                     |
| POST        | `/api/v1/guests/{id}/checkin` | ops       | —                                  | guest with flipped `checked_in`, `checked_in_at` |

### 6\. ERD Extraction Notes

  - **guest\_groups** (`company_id, event_id FK cascade, name,
    description?`) — 1:N **event\_guests** (`company_id, event_id FK
    cascade, guest_group_id? FK nullOnDelete, name, email?, phone?,
    invitation_code unique, rsvp_status='pending', plus_ones=0,
    table_number?, dietary_preference='standard', checked_in=false,
    checked_in_at?, notes?`).

> `[Backend Gap / Clarification Needed]` (a) No `guest_groups` CRUD —
> groups are seeder-created; the add-guest form depends on existing
> groups. (b) No guest update/delete, no RSVP-status endpoint (can’t
> record a client’s RSVP answer from the UI). (c) No bulk import (CSV)
> despite wedding-scale lists. (d) `checked_in` is a plain toggle with
> no audit of who checked the guest in (tickets track `checked_in_by`;
> guests do not).

## Slice 9 — Ticketing, QR Passes & Gate Scanning

### 1\. Slice Goal & Business Value

Define ticket tiers per event, issue tickets with unique QR tokens,
print passes, and run a live gate scanner that admits, rejects or flags
duplicates instantly.

### 2\. User Stories & Acceptance Criteria

  - *As operations staff, I want to create ticket tiers (name, price,
    capacity, benefits) for an event so that sales can start.* **AC:**
    `event_id*, name*, price*≥0, total_capacity*≥1` required;
    `sold_count=0`, `is_active=true`.
  - *As box-office staff, I want to issue 1–20 tickets for a holder in
    one action so that group bookings are fast.* **AC:** each ticket
    gets unique `ticket_number` (`TCK-YYYY-XXXX-####`) and `qr_token`
    (`QR-XXXX-XXXX-####`); `price_paid` defaults to tier price;
    `sold_count` and the event’s `confirmed_guests` increment by
    quantity; multi-ticket holders suffixed `(#1)`, `(#2)`…
  - *As gate staff, I want to scan/type a token and get an instant
    admit/deny so that queues move.* **AC:** unknown token → 404 error;
    already-checked-in → 409 warning naming time and operator; valid →
    ticket marked `checked_in` with timestamp and operator name, success
    message names holder, event and tier.
  - *As a manager, I want the ticket register with filters and print so
    that I can reconcile sales.* **AC:** filters `event_id`,
    `ticket_status`, search across number/token/holder name/email/phone;
    20/page; per-ticket print view.

### 3\. Frontend Screens & UI Components

  - **Tickets register** — filter bar (event picker, status:
    valid/checked\_in/cancelled, search); columns: ticket number,
    holder, event (with venue), tier, price paid, payment badge (paid,
    refunded, complimentary, pending), ticket status pill, checked-in
    at/by. **Print pass** per row.
  - **Issue Tickets form** — event*, tier* (filtered to active tiers of
    the event), `holder_name*`, email, phone, `quantity` (1–20),
    `price_paid` (defaults to tier price), `payment_status*` select.
  - **New Tier form** — `event_id*`, `name*`, `price*`,
    `total_capacity*`, `benefits`.
  - **Gate Scanner screen** — full-screen mode: big token input
    (autofocus, works with laser barcode readers as keyboard wedges),
    optional device-camera QR decode library, and three result states
    with color + sound: **green** admitted, **amber** already admitted
    (shows when and by whom), **red** invalid. Recent-scan strip.
  - **Ticket print** — pass layout with QR/barcode, holder, event,
    venue, tier, token.

### 4\. State Transitions & Workflows

Ticket: `valid → checked_in` (via verify) or `cancelled`; payment: `paid
| refunded | complimentary | pending`. Verify workflow is atomic per
token and idempotent-safe at the UI level (re-scan returns the 409
duplicate state, never double-admits).

### 5\. API Contract Mapping

| HTTP Method | Endpoint                 | Auth/Role    | Request Payload / Params                                                                                               | Response Shape                                                                                                          |
| ----------- | ------------------------ | ------------ | ---------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| GET         | `/api/v1/tickets`        | staff        | `?event_id, ?ticket_status, ?search`                                                                                   | paginated (20/pg) incl. event.venue, ticketType                                                                         |
| POST        | `/api/v1/tickets`        | ops          | `{event_id*, ticket_type_id*, holder_name*, holder_email?, holder_phone?, quantity?≤20, price_paid?, payment_status*}` | created ticket(s); counters incremented                                                                                 |
| POST        | `/api/v1/tickets/types`  | ops          | `{event_id*, name*, price*, total_capacity*, benefits?}`                                                               | created tier                                                                                                            |
| POST        | `/api/v1/tickets/verify` | staff (any)  | `{token*}` (qr\_token or ticket\_number)                                                                               | `200 {status:'success', message, ticket}` / `409 {status:'warning', message, ticket}` / `404 {status:'error', message}` |
| GET (web)   | `/tickets/{id}/print`    | session auth | —                                                                                                                      | pass HTML                                                                                                               |

### 6\. ERD Extraction Notes

  - **ticket\_types** (`company_id, event_id FK cascade, name,
    price(10,2), total_capacity=100, sold_count=0, benefits?,
    sales_start?, sales_end?, is_active=true`) — 1:N **tickets**
    (`company_id, event_id FK cascade, ticket_type_id FK cascade,
    ticket_number unique, qr_token unique, holder_name, holder_email?,
    holder_phone?, price_paid(10,2), payment_status='paid',
    ticket_status='valid', checked_in_at?, checked_in_by?`).

> `[Backend Gap / Clarification Needed]` (a) No public/self-serve
> purchase flow — tickets are staff-issued only; `sales_start/sales_end`
> windows are unused by code. (b) No QR image generation endpoint
> (picqer barcode package is installed but unused; the print view relies
> on the token text) — the SPA should render QR client-side from
> `qr_token`. (c) Verify is authenticated staff-only; a dedicated
> low-privilege “gate” role/device token would be an improvement. (d) No
> ticket cancel/refund endpoints despite `cancelled`/`refunded` states.
> (e) `checked_in_by` stores a free-text name, not a user FK.

## Slice 10 — Invoicing, GST Billing & Payments

### 1\. Slice Goal & Business Value

Turn delivered work into cash: itemized GST invoices with due dates,
partial payment recording, automatic balance/status recalculation, and
printable tax documents.

### 2\. User Stories & Acceptance Criteria

  - *As an accountant, I want to raise an itemized invoice so that
    billing is tax-correct.* **AC:** `client_id*, invoice_date*,
    due_date*, tax_rate*`, ≥1 item (`description*, quantity*≥1,
    unit_price*≥0`); server computes subtotal/tax/grand\_total, sets
    `paid_amount=0`, `balance_due=grand_total`, `status=unpaid`, number
    `INV-YYYY-####`.
  - *As an accountant, I want to record partial payments so that
    balances stay exact.* **AC:** amount validated `1 … balance_due`;
    payment gets `PAY-YYYY-####`; invoice recomputes to `partially_paid`
    or `paid` (balance 0); the linked event’s `amount_received`
    increments by the same amount.
  - *As a manager, I want the invoice register filterable by status so
    that overdue money is visible.* **AC:** filter `status` (draft,
    sent, unpaid, partially\_paid, paid, overdue, cancelled); 15/page by
    invoice date desc; totals column with ₹.
  - *As an accountant, I want a printable GST invoice so that clients
    get compliant documents.* **AC:** print view shows items, subtotal,
    GST rate/amount, grand total, payments history, balance due, terms.

### 3\. Frontend Screens & UI Components

  - **Invoices list** — number, client, event, invoice/due dates, grand
    total, paid, balance due, status pill; “New Invoice” CTA (finance);
    `?event_id=` deep-link preselect.
  - **Invoice builder** — same line-item editor pattern as quotations:
    client*, event, dates*, tax\_rate\* (default 18), notes; live totals
    panel.
  - **Invoice detail** — items, totals, terms/notes, payments table
    (`payment_number, payment_date, amount, method:
    bank_transfer/credit_card/debit_card/cash/upi/cheque,
    transaction_reference, status`), **Record Payment** modal (`amount*`
    capped at balance due, `payment_date*`, `payment_method*`,
    `transaction_reference`, `notes`), **Print** button.
  - Key states: balance-due emphasis (amber/red when overdue),
    paid-in-full success state, payment form’s amount max enforced
    client-side and server-side.

### 4\. State Transitions & Workflows

Invoice: created `unpaid → partially_paid → paid` driven by payments;
`overdue` is a schema state but **no code sets it** (needs a scheduled
job — gap); `draft/sent/cancelled` exist in schema with no transitions.
Payment: `completed` on record; `pending/failed/refunded` schema states
unused.

### 5\. API Contract Mapping

| HTTP Method | Endpoint                         | Auth/Role    | Request Payload / Params                                                                                                     | Response Shape                                 |
| ----------- | -------------------------------- | ------------ | ---------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- |
| GET         | `/api/v1/invoices`               | staff        | `?status`                                                                                                                    | paginated (15/pg) incl. client, event          |
| POST        | `/api/v1/invoices`               | finance      | `{client_id*, event_id?, invoice_date*, due_date*, tax_rate*, notes?, items*: [{description*, quantity*≥1, unit_price*≥0}]}` | `201` (totals computed, number auto)           |
| GET         | `/api/v1/invoices/{id}`          | staff        | —                                                                                                                            | invoice + items + payments                     |
| DELETE      | `/api/v1/invoices/{id}`          | admins       | —                                                                                                                            | `200/204`                                      |
| POST        | `/api/v1/invoices/{id}/payments` | finance      | `{amount* (≤ balance_due), payment_date*, payment_method*, transaction_reference?, notes?}`                                  | created PaymentResource + invoice recalculated |
| GET (web)   | `/invoices/{id}/print`           | session auth | —                                                                                                                            | print HTML                                     |

### 6\. ERD Extraction Notes

  - **invoices** (`company_id, branch_id?, client_id FK cascade,
    event_id? FK, invoice_number unique, invoice_date, due_date,
    subtotal/discount/tax_amount/grand_total/paid_amount/balance_due
    (12,2), tax_rate(5,2)=18.00, status='unpaid', notes?, terms?`) — 1:N
    **invoice\_items** (`invoice_id FK cascade, description,
    quantity(10,2)=1, unit_price, total_price`) and 1:N **payments**
    (`company_id, branch_id?, invoice_id? FK nullOnDelete, client_id?
    FK, event_id? FK, payment_number unique, payment_date, amount(12,2),
    payment_method='bank_transfer', transaction_reference?,
    status='completed', notes?`).

## \> `[Backend Gap / Clarification Needed]` (a) No invoice update/edit, no status transitions (`draft → sent`, cancel), no `overdue` automation (no scheduled jobs exist). (b) `discount` is hardcoded 0. (c) Payments cannot be edited, refunded or voided; `payments.invoice_id` uses `nullOnDelete` — deleting an invoice orphans its payment records. (d) No invoice↔quotation link (schema has none) — converting an accepted quotation into an invoice is manual re-entry. (e) Print is Blade-only; PDF generation package installed but unused.

## Slice 11 — Dashboard & Reports / Analytics

### 1\. Slice Goal & Business Value

The morning-coffee screen and the month-end screen: a KPI dashboard for
daily operations, and a reports module for revenue, cost and margin
analysis by category, month, client and vendor.

### 2\. User Stories & Acceptance Criteria

  - *As any staff user, I want a dashboard of headline KPIs so that I
    know where the business stands in 10 seconds.* **AC:** cards for
    total/active events (active = confirmed+in\_planning+active),
    revenue booked (sum of `contract_value` on confirmed-or-later
    events), cash received (completed payments), total expenses,
    outstanding (balance due on unpaid/partially\_paid/overdue
    invoices), clients, leads (total + converted), guests (total +
    checked-in).
  - *As a manager, I want upcoming events and pending tasks on the
    dashboard so that nothing slips.* **AC:** upcoming events =
    `start_date ≥ now−1 day`, ascending, top 6 with
    client/venue/manager; pending tasks = status pending/in\_progress by
    due date, top 6, each linking to its event; events-by-category
    breakdown.
  - *As leadership, I want revenue and margin reports so that I can
    steer the company.* **AC:** revenue by event category (contract vs
    received, event counts), monthly events/revenue (last 12 months),
    top 5 clients by contract value, top 5 vendors by spend, headline
    gross profit and margin % (`(revenue−cost)/revenue`).

### 3\. Frontend Screens & UI Components

  - **Dashboard** — 5-stat card row (Active events, Revenue booked, Cash
    received, Outstanding, Leads in pipeline) with compact Indian
    formatting (₹1.93 Cr / ₹25 L); upcoming-events table (event, client,
    date, venue, status pill); urgent-tasks table with priority pills
    and working Done toggles (calls Slice 3’s toggle endpoint);
    events-by-category chart/list.
  - **Reports page** — KPI strip (total revenue, total cost, gross
    profit, margin %); revenue-by-category table; monthly events
    bar/line chart (12 months); top-5 clients and top-5 vendors tables.
  - Key states: skeleton cards while loading; zero-state formatting (₹0,
    “—” for margin when revenue is 0).

### 4\. State Transitions & Workflows

Read-only aggregates; task Done toggle mutates via Slice 3. No workflow
states of its own.

### 5\. API Contract Mapping

| HTTP Method | Endpoint            | Auth/Role    | Request Payload / Params | Response Shape                                                                                                           |
| ----------- | ------------------- | ------------ | ------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| GET         | `/api/v1/dashboard` | staff        | —                        | `{data: {totals…, upcoming_events[], pending_tasks[], events_by_category[]}}` (mirrors `DashboardController` aggregates) |
| GET (web)   | `/reports`          | session auth | —                        | report aggregates (revenue by category, monthly, top clients/vendors, P\&L)                                              |

> `[Backend Gap / Clarification Needed]` There is **no**
> `/api/v1/reports` **endpoint** — reports exist only in the legacy
> Blade controller (which also uses MySQL-only `DATE_FORMAT`, so the
> queries are not portable). Add an API route before building the
> Reports screen; until then the SPA dashboard is the only analytics
> surface.

### 6\. ERD Extraction Notes

Read-only slice over **events**, **payments**, **invoices**,
**event\_expenses**, **leads**, **clients**, **event\_guests**,
**event\_tasks**, **event\_categories**, **vendors** — no new entities.
Aggregations to mirror in the ERD’s derived/ reporting layer:
`SUM(events.contract_value)`, `SUM(payments.amount) WHERE
status='completed'`, `SUM(invoices.balance_due) WHERE status IN (unpaid,
partially_paid, overdue)`, `SUM(event_expenses.amount)`.

## Slice 12 — Client & Vendor Portals + Tenant Administration

### 1\. Slice Goal & Business Value

Give clients and vendors a self-serve, read-only window into their slice
of the business — itineraries, run of show, quotations and invoices for
clients; work orders and disbursements for vendors — plus the admin
screens for tenant master data.

### 2\. User Stories & Acceptance Criteria

  - *As a client, I want to see my events with venues, day-wise run of
    show, quotations and invoices (with payments) so that I stop calling
    the agency for updates.* **AC:** portal shows only my records;
    read-only; printable invoice/quotation links.
  - *As a vendor, I want to see my assignments (event, scope, contract
    vs paid) and expense history so that I know what I owe and what I’m
    owed.* **AC:** portal shows only my records; read-only.
  - *As a company admin, I want to manage branches, departments,
    categories, roles and settings so that the tenant configures
    itself.* **AC:** — **currently impossible; see gap.**

### 3\. Frontend Screens & UI Components

  - **Client portal** (`/portal/client`) — profile header; event cards
    with venue and dates; per-event day/schedule timeline; quotations
    table (number, total, status, print); invoices table (number,
    totals, paid/balance, status, print).
  - **Vendor portal** (`/portal/vendor`) — profile header with category
    and rating; assignment cards (event title/date, service title, scope
    of work, contract amount, paid amount, status); expense/payment
    history.
  - **Administration** (design-ready, blocked on backend):
    companies/branches/departments, employees, roles & permissions,
    event/expense/vendor/inventory/service categories, system settings
    (currency, tax rate, logo).

### 4\. State Transitions & Workflows

Read-only portals. Role-based routing: a `client` token lands on
`/portal/client`, a `vendor` token on `/portal/vendor`, staff on
`/dashboard`.

### 5\. API Contract Mapping

| HTTP Method | Endpoint                | Auth/Role          | Request Payload / Params | Response Shape                                                                 |
| ----------- | ----------------------- | ------------------ | ------------------------ | ------------------------------------------------------------------------------ |
| GET         | `/api/v1/portal/client` | client (or admins) | —                        | client + events.venue + events.days.schedules + quotations + invoices.payments |
| GET         | `/api/v1/portal/vendor` | vendor (or admins) | —                        | vendor + assignments.event + expenses.event + category                         |

### 6\. ERD Extraction Notes

No new entities; reads across **clients**, **events**, **event\_days**,
**event\_schedules**, **quotations**, **invoices**, **payments**,
**vendors**, **vendor\_assignments**, **event\_expenses**. Admin
entities: **companies, branches, departments, employees, roles,
permissions, system\_settings** (Slice 1 ERD).

> `[Backend Gap / Clarification Needed]` (a) **Critical:** the legacy
> portal controllers load `Client::first()` / `Vendor::first()` — the
> *first record in the table*, not the record of the logged-in user. The
> link exists in schema (`clients.user_id`) but is unused. Portals must
> scope by the authenticated user’s linked client/vendor before any real
> customer sees this. (b) No admin CRUD endpoints for any tenant master
> data (companies, branches, departments, employees, roles, permissions,
> settings, all category tables). (c) No client/vendor account
> provisioning flow (no registration or invite endpoints).

# 4\. Cross-Cutting Frontend Requirements

## 4.1 Global Navigation & Layout

  - **App shell:** left sidebar (grouped by slice) + topbar with user
    menu; mobile hamburger. Suggested grouping, gated by the Section 2
    matrix:
      - *Main:* Dashboard
      - *Sales:* Leads, Clients, Quotations
      - *Operations:* Events, Venues, Vendors, Guests, Tickets (+ Gate
        Scanner), Inventory
      - *Finance:* Invoices, Expenses, Reports
      - *Portals:* Client Portal / Vendor Portal (visible only to those
        roles; admins for preview)
  - **Role visibility:** hide, don’t just disable, nav items the role
    cannot read; every route also guarded client-side; backend 403
    remains the enforcer.
  - **Entity codes everywhere:** lists show the human codes (`EVT-…`,
    `QTN-…`, `INV-…`, `PAY-…`, `EXP-…`, `TCK-…`, `GUEST-…`) as copyable
    mono chips.
  - **Status pill system:** one shared component mapping every enum in
    this PRD to a color (e.g. money states: paid=green,
    partially\_paid=amber, overdue=red; lifecycle: enquiry=slate,
    confirmed=blue, active=green, cancelled=red).
  - **Flash/Toast mapping:** legacy web flows return flash messages
    (“Event successfully created with code EVT-…”); API flows return
    resources — standardize on success/error toasts with the returned
    code included.

## 4.2 Error Handling & API Responses

| Situation                | Backend behavior                                                         | UI requirement                                       |
| ------------------------ | ------------------------------------------------------------------------ | ---------------------------------------------------- |
| Validation failure       | `422` with Laravel shape `{message, errors: {field: [msg…]}}`            | inline field errors + summary toast                  |
| Unauthenticated          | `401`                                                                    | drop token, redirect to `/login`                     |
| Forbidden (role)         | `403 {message: "Forbidden. This endpoint requires one of the roles: …"}` | toast + navigate away; hide affordance next render   |
| Not found / cross-tenant | `404` (tenant scoping returns 404, not 403)                              | “not found” empty state                              |
| Gate scan outcomes       | `200/409/404` with `{status: success\|warning\|error, message}`          | full-screen color state per Slice 9                  |
| Success writes           | `201` on create; resource JSON on update                                 | optimistic update where safe (toggles), else refetch |

## 4.3 File Uploads, Exports & Real-Time Events

  - **Uploads:** none exist. `receipt_path` (expenses) and `logo_path`
    (companies) are schema-only — no upload endpoint, no `Storage`
    usage. Any receipt/logo upload UI is blocked on backend work.
  - **Exports/PDF:** no CSV/Excel export anywhere. Print = Blade print
    views for quotations, invoices, tickets; `laravel-dompdf` +
    `picqer/php-barcode-generator` are installed but unused — the
    frontend should render QR client-side and use print stylesheets
    until PDF endpoints exist.
  - **Real-time:** no WebSockets, broadcasting, queues, mail or
    notification code in the repository (`jobs`/`cache` tables only).
    Gate scanner, dashboard and check-in screens rely on
    **polling/refetch after mutations**; if live multi-device gate state
    is required, that is new backend infrastructure.
  - **Background jobs:** none. Overdue-invoice detection, RSVP reminders
    and follow-up scheduling (all implied by schema fields like
    `lead_activities.scheduled_at`, `invoices.status='overdue'`) need a
    scheduler the backend does not have yet.

# 5\. Phased Frontend Delivery Roadmap

Dependency rule applied: tenant/auth context and master data (clients,
venues, vendors, categories) must exist before transactional slices;
money slices depend on events; gate features depend on events + ticket
types.

**Phase 0 — Foundation (week 0–1).** Design system (status pills,
currency/date formatters, paginated table, form kit with Laravel-error
mapping), API client with token handling, route guards, app shell.
*Backend dependency:* `/api/v1` *auth endpoints (exist).*

**Phase 1 — MVP (weeks 1–4).** Slice 1 (auth), Slice 11 dashboard (API
exists), Slice 2 CRM (leads → convert → clients), Slice 3 events core
(list, create/edit, detail with run of show + tasks). *Backend gaps to
close in parallel: lead activity logging endpoint,
event-day/schedule/task edit endpoints.*

**Phase 2 — Revenue & ops (weeks 5–8).** Slice 4 venues/vendors
(list+forms; read-only assignments), Slice 5 quotations (builder + print
stylesheet), Slice 10 invoices & payments, Slice 8 guests + check-in.
*Backend gaps: proposal endpoints, quotation status transitions +
quotation→invoice conversion, budget CRUD, guest groups CRUD + RSVP
endpoint, CSV guest import.*

**Phase 3 — Gate & scale (weeks 9–12).** Slice 9 ticketing + gate
scanner (client-side QR), Slice 6 expenses + budget variance UI, Slice
12 portals (after per-user scoping fix), Reports screen (after
`/api/v1/reports` exists), Slice 7 inventory/resources once its
endpoints land. *Backend gaps: portal scoping (critical), reports API,
inventory/allocation routes, overdue automation, receipt/logo uploads,
PDF generation.*

**Explicitly out of frontend scope until backend lands:** admin
master-data CRUD (all category/settings screens), user management,
public ticket sales, notifications/reminders.

*End of PRD. All entities, enums, endpoints and rules above were
extracted from the repository’s migrations
(*`database/migrations/2024_01_01_*`*), models (*`app/Models/*`*),
controllers (*`app/Http/Controllers/*`*), routes (*`routes/web.php`*)
and seeder (*`database/seeders/DatabaseSeeder.php`*), plus the*
`/api/v1` *contract layer (*`routes/api.php`*,*
`app/Http/Controllers/Api/V1/*`*,* `app/Http/Requests/V1/*`*) built on
this codebase.*
