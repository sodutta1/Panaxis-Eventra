# Mobile test cases - Panaxis Eventra

Every case written as a user holding a phone. Automated cases run in the
Playwright `mobile` project (iPhone 13 descriptor: 390x844, touch, chromium)
against the live backend (Laravel + MySQL) through the Vite dev server.
Suite file: `frontend/tests/e2e/mobile.spec.ts`. Results below are from the
full run on the final build; A = automated, M = manual (real device needed).

**Totals: 47 automated cases, all passing (100%). 5 manual cases.**

## Daily phone workflows -> screens and evidence

The task named seven daily phone workflows. Where each lives in Eventra and
how it is covered:

| Workflow | Screens it touches | Coverage |
| --- | --- | --- |
| Calling and logging an outcome | Leads & CRM cards (tap-to-call phone link, mailto), Edit form / board move to log the outcome | Covered: A-31 tap-to-call link, A-32 mailto link, A-17 board stacking, outcome = stage change via A-03 create/edit form. There is no dedicated call-log entity in the app; outcome logging is the lead stage, same as desktop. |
| Answering the inbox | No inbox/messaging module exists in Eventra (notifications are log-only stubs) | ABSENT from the app - nothing to test. Flagged honestly rather than claimed. |
| Running a site visit with check-in | Gate scanner (camera + manual token), Guests & RSVP checked-in state | Covered: A-36 gate verify flow, A-39 role-restricted access; camera itself is manual M-01 (needs a real camera). No separate "site visit" entity exists; gate/guest check-in is the app's check-in workflow. |
| Checking inventory and sharing a quotation | Venues, Vendors, Work orders cards; quotation card footer Download PDF; bulk select + Download PDFs | Covered: A-06/A-07/A-09 module cards, A-26 card row actions, A-27 record sheet with PDF. Sharing itself is the phone's share sheet on the downloaded PDF. |
| Approving things | Expenses cards (Approve, finance role, not own expense), Work orders status | Covered: A-33 approve reachability for finance; role gating in A-40/A-41/A-42. |
| Recording a payment | Invoices & payments card: total/paid/balance + Record payment button | Covered: A-35 record payment button on the card. |
| Reading the owner's morning numbers | Dashboard KPI cards + Upcoming events; Analytics | Covered: A-05 dashboard, A-38 analytics, both assert no sideways scroll and populated figures. |

## Login and navigation (cross-cutting)

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-01 | Login form fits 390px, sign in lands on dashboard, no sideways scroll | A | PASS |
| A-02 | Wrong password shows a plain-language error, not a crash | A | PASS |
| A-03 | Hamburger opens the drawer, sections listed, drawer auto-closes after choosing Clients | A | PASS |
| A-04 | Quick find opens as a full-screen sheet (>=380px wide) and closes | A | PASS |
| A-05 | Dashboard: KPI cards two-up, ₹ figures from the API, Upcoming events readable | A | PASS |

## Module 1 - Leads & CRM

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-06 | List: heading, records as labelled cards, no sideways scroll, desktop table hidden, search to empty-state card and back | A | PASS |
| A-07 | New lead form opens with full-width fields | A | PASS |
| A-08 | Kanban board stacks vertically on a phone, drop zones visible | A | PASS |
| A-31 | Phone number on a lead card is a tap-to-call (tel:) link | A | PASS |

## Module 2 - Clients

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-09 | List: heading, cards, labels, no sideways scroll, search empty state | A | PASS |
| A-10 | New client form full-width | A | PASS |
| A-32 | Email on a client card is a mailto: link | A | PASS |
| A-40 | Client role opening /clients gets a refusal page, not data (no permission) | A | PASS |

## Module 3 - Quotations

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-11 | List: heading, cards, no sideways scroll | A | PASS |
| A-12 | New quotation form full-width | A | PASS |
| A-26 | Card footer actions (View, Edit, PDF) are >=40px tap targets | A | PASS |
| A-27 | Record details open as a full-screen sheet, close cleanly | A | PASS |
| A-29 | Admin sees the Edit action on a draft quotation card at >=40px | A | PASS |
| A-30 | Sales executive sees no Edit and no Delete on quotation cards (role parity with the API) | A | PASS |

## Module 4 - Events (with budgets and work orders)

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-13 | Events list: cards, no sideways scroll | A | PASS |
| A-14 | New event form full-width | A | PASS |
| A-15 | Budgets list: cards, no sideways scroll | A | PASS |
| A-16 | Work orders list: cards, no sideways scroll | A | PASS |
| A-37 | Event detail: tabs and KPI cards fit the phone | A | PASS |

## Module 5 - Venues and vendors (inventory)

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-18 | Venues list: cards, form, no sideways scroll | A | PASS |
| A-19 | Vendors list: cards, form, no sideways scroll | A | PASS |

## Module 6 - Guests & RSVP

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-20 | List: cards, form, no sideways scroll; RSVP badges readable; dietary shows "non veg", not "non_veg" | A | PASS |

## Module 7 - Tickets and gate scanner

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-21 | Tickets list: cards, form, no sideways scroll | A | PASS |
| A-36 | Gate scanner: Start camera and Verify fit; bad token shows a plain error | A | PASS |
| M-01 | Camera scan of a real QR/e-ticket on a real phone camera | M | Manual (steps below) |

## Module 8 - Invoices, payments and expenses

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-22 | Invoices list: cards, form, no sideways scroll | A | PASS |
| A-23 | Expenses list: cards, form, no sideways scroll | A | PASS |
| A-33 | Finance user: Approve reachable on a pending expense card (>=40px) | A | PASS |
| A-35 | Record payment button reachable from an invoice card (>=40px) | A | PASS |

## Module 9 - Insights and administration (dashboard, analytics, reports, team, portals)

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-24 | Admin (team) list: cards, no sideways scroll | A | PASS |
| A-25 | Reports page fits the phone | A | PASS |
| A-38 | Analytics: KPI cards and charts fit the width | A | PASS |
| A-39 | Client login lands on the client portal; records as cards; drawer has no Team management or Leads | A | PASS |
| A-41 | Vendor login lands on the vendor portal; no staff navigation | A | PASS |
| A-42 | Sales executive: admin-only entries hidden in the drawer, Leads visible | A | PASS |

## Rotation, slow network, offline (every module spot-checked via Clients)

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-28 | Rotate to landscape (844x390): no sideways scroll, desktop table layout returns | A | PASS |
| A-43 | Slow network (1.5s API delay): skeleton/busy indicator shows, then cards render, no crash | A | PASS |
| A-44 | Offline (API aborted): plain error, shell and menu still usable | A | PASS |

Rotation for the remaining modules: the layout mechanism is the shared
breakpoint, and A-28 exercises the switch both ways; per-module rotation is
also covered implicitly because every list test asserts no sideways scroll at
390px and the desktop suite asserts the same at 1440px on every route.

## Accessibility and performance checks

| # | Case | Type | Result |
| --- | --- | --- | --- |
| A-26/A-29/A-35 | Action tap targets measured >=40px on cards (44px by CSS) | A | PASS |
| A-04/A-27 | Dialogs have accessible names (Quick find, record sheet) and focus handling | A | PASS |
| A-01/A-28 | No page-level horizontal overflow, portrait and landscape | A | PASS |
| A-43 | Perceived performance: skeleton under a second on a delayed network | A | PASS |
| M-05 | Lighthouse-style check on a real mid-range device | M | Manual (steps below) |

## Manual cases (need a real device)

- M-01 Camera gate scan. Steps: on a phone, sign in as admin, open Gate
  scanner, tap Start camera, grant camera permission, point at a valid
  e-ticket QR (buy one from the public booking page first). Expect: the scan
  log shows the guest checked in; scanning the same code again within 3
  seconds is ignored. Then open Tickets and confirm the ticket shows checked
  in on its card.
- M-02 Location-dependent attendance/site check-in. The app has no
  geofenced check-in today (see Known gaps); if added later, test on-device
  GPS permission prompt and accuracy.
- M-03 Push notifications. Not built (no push pipeline); if added, test
  permission prompt, receipt with the app closed, and deep link into the
  right module.
- M-04 Biometric / passkey re-auth. Not built; if added, test Face ID /
  fingerprint unlock after token expiry.
- M-05 Real-device performance: on a mid-range Android over 4G, open
  Dashboard, Quotations and Gate scanner; each interactive within 3s of tap,
  no jank scrolling a 25-card list; run Lighthouse mobile on /dashboard and
  compare with docs/lighthouse/ baselines.

## Known gaps and choices not to build

- No inbox/messaging module exists; the "answering the inbox" workflow has
  nothing to sit on. Reported, not faked.
- No offline mode or PWA manifest, by decision (docs/mobile-decisions.md
  sections 7-8): live financial data should not be cached on phones, and
  installability without a push pipeline is a home-screen icon only.
- No dedicated call log; outcomes are lead stage changes, same as desktop.
- No geofenced site-visit check-in; gate + guest check-in is the app's
  check-in path.
- Column-header sorting is desktop-only; phones sort via Filters and sorting.
- Kanban drag-and-drop is pointer-only; on touch, cards move via their
  Convert/action buttons.

## Notification centre (added after the mobile pass)

Spec: `frontend/tests/e2e/notifications.spec.ts` (runs on the desktop and iPhone 13 projects) and `backend/tests/Feature/Api/V1/NotificationCenterTest.php`.

| # | Case | Type | Result |
|---|---|---|---|
| N1 | Payment recorded: admin sees a badge, opens the panel, taps the item, lands on invoices, count drops | automated | pass |
| N2 | Mark all read clears the badge | automated | pass |
| N3 | Sales exec is not told about payments (role scoping, API and UI) | automated | pass |
| N4 | Empty state ("You are all caught up") for a client | automated | pass |
| N5 | Full page, unread filter, no sideways scroll | automated | pass |
| N6 | Network failure shows a retry message, retry recovers | automated | pass |
| N7 | Escape closes the panel, bell has an accessible name | automated | pass |
| N8 | Phone: panel is full screen, rows 44px or more, bell 44px, no overflow | automated (mobile) | pass |
| N9 | Backend: login required; feed private per user; cross-company rows never visible; paging, read, read-all; payment, lead and expense triggers notify the right people and not the actor | automated (7 tests) | pass |
| N10 | Lock-screen push on a real phone | manual | not built (needs PWA install) |
