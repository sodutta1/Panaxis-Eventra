# Mobile decisions - Panaxis Eventra

How the CRM was made phone-first, and why. Written November 2026 alongside
`mobile-test-cases.md`. "Phone" below means the 390x844 portrait viewport the
test suite runs (iPhone 13 class); the layout breakpoints are 767px/768px.

## 1. One breakpoint, shared components

Everything hangs off the existing 767px breakpoint from the design system.
No page-by-page patches: the two changes that transformed every module were
made once, in shared code:

- `DataTable` (frontend/src/components/ui.tsx) renders each row twice: the
  desktop table (`.dt-table`) and a **record card** (`.record-card`). Below
  768px the table hides and cards show; above, the reverse. Every module that
  lists records (leads, clients, quotations, events, venues, vendors, budgets,
  work orders, guests, tickets, invoices, expenses, team, both portals) gets
  the phone layout for free, including future modules.
- The mobile CSS block in `design-tokens.css` owns tap targets, panels and
  small-screen overrides in one place.

Why cards over horizontal-scroll tables: on a 390px phone a 6-8 column table
shows 1.5 columns; finding a value means swiping every row. A card shows the
whole record: first column as the title, every other column as a labelled
line (labels come from the table head, so no meaning is lost), status badges
unchanged, and the row actions (view, edit, PDF, record payment, delete...)
in a footer with 44px targets. Sorting stays available through the existing
"Filters and sorting" panel; column-header sorting is a desktop affordance.

## 2. Tap targets and inputs

- 44px minimum for every interactive element on phones (Apple HIG / WCAG 2.5.8
  AAA): buttons, icon actions, pagination numbers, filter chips, the saved
  views select, sidebar pin buttons, sort controls. The audit found pins at
  29px, tool buttons at 36px and pagination at 32px.
- Text inputs and selects are 16px on phones. Below 16px iOS Safari auto-zooms
  on focus and leaves the page zoomed in afterwards.
- Date inputs keep the native phone picker.

## 3. Navigation pattern

Kept the existing pattern from the UI overhaul because it already fits phone
conventions: a hamburger opens the sidebar as a drawer over a backdrop, the
drawer closes itself after a destination is chosen, Escape/backdrop closes it.
The rail/collapse mode is desktop-only (the toggle is hidden on phones; a
phone always gets the full drawer). Pins, the open accordion group and theme
persist per user in localStorage on the device - acceptable on a phone because
people rarely switch browsers mid-task, and they never sync across devices.

Quick find (Ctrl+K on desktop, button on phones) becomes a full-screen sheet:
on a phone a floating modal with results under the keyboard is unusable.

## 4. Panels and forms

- Record detail panels and quick find are full-screen sheets below 768px
  (`100dvh`, no rounded corners, no backdrop padding). dvh keeps them stable
  when the phone URL bar collapses.
- Forms are single-column, full-width fields; the quotation/invoice line-item
  editor keeps its desktop grid but its cells are inputs that already wrap;
  verified no sideways scroll at 390px.
- The leads kanban: columns stack vertically full-width on phones instead of
  a sideways-scrolling board. Drag-and-drop is pointer-based, so on touch the
  per-card Convert/move buttons are the path; both exist.

## 5. The daily phone workflows

Mapped the requested workflows to what was verified (tests in
`mobile-test-cases.md`):

- Calling and logging an outcome -> lead card: phone/email visible without
  swiping, Edit in the card footer, stage badge readable.
- Answering the inbox / quick find -> full-screen quick-find sheet, live
  results, tap to navigate.
- Site visit / check-in -> gate scanner: camera start button 44px, manual
  token entry fits, verify button thumb-reachable; guests module shows
  checked-in state on cards.
- Checking inventory and sharing a quotation -> quotation card: client,
  total, valid-until and status on one card; Download PDF in the footer;
  bulk-select + Download PDFs above the list.
- Approving things -> expenses/work orders: status badges on cards, approve /
  reverse actions in the card footer (role-gated exactly as desktop).
- Recording a payment -> invoice card shows total / paid / balance and the
  Record payment button directly on the card.
- Owner's morning numbers -> dashboard KPI cards two-up, then analytics;
  both verified no-overflow at 390px.

## 6. Security parity

No client-side security decisions were relaxed for phones; there is no
phone-only data or phone-only action:

- Role checks stay server-side; the UI only hides what the API would refuse.
  Tests assert a sales executive sees no Edit/Delete on quotation cards, a
  client gets a refusal page on staff routes, and client/vendor land on their
  portals with no staff navigation.
- Session handling, token storage and logout are unchanged from desktop.
- Gate scanner and camera run over the same token-verified API.

## 7. Offline handling - decided against caching CRM data

The app requires connectivity, and that is deliberate: quotations, invoices,
payments and gate scans are live financial records. Caching them in a service
worker or localStorage would show stale money figures and duplicate sensitive
data on a shared or lost device. What we did instead:

- Failed loads show the plain-language error state and keep the shell (menu,
  navigation) usable - covered by an automated offline test.
- Slow networks get skeleton placeholders instead of blank screens - covered
  by an automated throttled test.
- The gate scanner already tolerates a retry: the same scan within 3 seconds
  is ignored server-side, so a tapped-twice Verify on a flaky network cannot
  double-check-in a guest.

## 8. Installability

No PWA manifest or service worker. Reason: installability's real value here is
offline caching and push, and section 7 rules the first out; the second needs
a server push pipeline that does not exist yet. Adding a manifest without a
service worker buys only a home-screen icon, which the user did not ask for.
Recorded as a deliberate gap; if push reminders (event-day, payment-due) get
built later, add the manifest + push subscription then.

## 9. Performance targets

Targets for a mid-range phone on a weak network:

- No page-level horizontal overflow at 390px (automated on every screen).
- Lists paginate server-side (existing, 25/page default) - no thousand-row
  renders on a phone.
- Skeletons within a second on a slow network, records when the API answers
  (automated with a 1.5s delayed route).
- Bundle: single Vite bundle, fonts via @fontsource (self-hosted, subset by
  the bundler). Full Lighthouse numbers live in docs/lighthouse/ from the UI
  overhaul; the mobile pass did not regress them (no new dependencies, no new
  blocking assets - the changes are CSS plus one render-path branch).

## 10. What was deliberately not done

- No offline caching / PWA (sections 7-8).
- No separate "mobile app" shell, bottom tab bar or gestures: the drawer +
  cards pattern is consistent and testable; a second navigation pattern would
  double the test surface for marginal gain.
- No per-page responsive rewrites: everything went through the shared table,
  filter bar, panel and CSS block so behavior stays uniform.
- Column-header sorting on phones: available via the sort controls in
  "Filters and sorting" instead.
