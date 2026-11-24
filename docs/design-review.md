# Design review, 24 Nov 2026

## Result and evidence

Audit was committed before implementation in `fcce4ea`. This change preserves Eventra's event-management domain. It does not add real-estate modules that do not exist in this product.

Final verification:
- Backend: 152 tests, 1,281 assertions passed.
- Frontend: 23 unit tests passed.
- Playwright: 20 tests passed, one worker, retries disabled. This includes the original 15 flows and five UI acceptance flows.
- Production build passes. The existing large-chunk advisory remains.
- Every registered screen has desktop (1440 px) and phone (390 px) evidence below. Extra evidence covers dark theme, quotation details and expense creation.

The acceptance suite checks name/reference filtering, numeric high-to-low sorting, module search highlighting, URL-preserving pagination, sidebar accordion/rail/persistence/drawer, quick-find, signed PDF and bulk ZIP downloads, document actions and plain-language page scans. Its PDF tests exercise the existing seeded document types, not hypothetical modules.

## Critique and corrections

- The first phone capture showed a partially animated drawer. Captures now wait for sidebar motion to finish. The closed drawer does not consume page width.
- Wide tables remain compact and scroll inside their table region on phones. The page itself does not scroll sideways. The action menu lives at the end of the row; reaching it can require horizontal table scrolling.
- Dark-theme subtitle and input colours were too faint. Increased muted-text contrast and replaced residual white inputs. Final dark quotation evidence was inspected again.
- Quotation details originally showed raw foreign keys, machine dates and an empty line amount. Details now use relation names, Indian currency/date formatting and the line total. The eye opens this details panel, not a PDF preview.
- A later phone check found the details dialog inherited visibility from its desktop action wrapper. It now renders outside the action wrapper, so the same details panel stays visible on phones. Rechecked the actual phone panel and reran all 20 browser tests.
- The expense vendor field was an ID box. Replaced it with a vendor selector. Expense records retain maker/checker separation and reversal reasons.
- Fast consecutive filter/sort changes exposed a router-state race. Each control now updates the current URL, preventing a later change from dropping an earlier filter. List responses also follow the newest request instead of overwriting it with stale rows.
- Ticket cancellation originally left capacity booked. Cancellation now updates capacity and confirmed guest counts atomically while keeping the ticket record.
- Related selectors previously stopped at one page. They now fetch all option pages.
- Client portal embedded lists were unbounded and lacked consistent document actions. Replaced them with server-paged sections. Vendor sign-in fails closed when no vendor identity is linked.

## Screen-by-screen review

| Screen | Critique and final treatment | Desktop | Phone |
|---|---|---|---|
| Dashboard | Summary layout retained; green charts, readable Indian numbers. Preview tables are not full list screens. | [1440 px](screenshots/dashboard-1440.png) | [390 px](screenshots/dashboard-390.png) |
| Analytics | Restrained charts and theme tokens. Ranked summaries remain summaries, not shared filtered tables. | [1440 px](screenshots/analytics-1440.png) | [390 px](screenshots/analytics-390.png) |
| Events | Shared search, filters, export, signed closeout documents and compact rows. | [1440 px](screenshots/events-1440.png) | [390 px](screenshots/events-390.png) |
| Event detail | Lazy tabs; tasks and schedules load in 20-row streams instead of one long page. | [1440 px](screenshots/events-1-1440.png) | [390 px](screenshots/events-1-390.png) |
| Leads / CRM | Shared table by default; optional board. URL search, name/reference filters and numeric sort verified. | [1440 px](screenshots/leads-1440.png) | [390 px](screenshots/leads-390.png) |
| Clients | Shared table, related counts and Indian formatting. | [1440 px](screenshots/clients-1440.png) | [390 px](screenshots/clients-390.png) |
| Venues | Compact inventory table, capacity/rent sorting and city/type filters. | [1440 px](screenshots/venues-1440.png) | [390 px](screenshots/venues-390.png) |
| Vendors | Shared table, rating/category filtering and role-aware actions. | [1440 px](screenshots/vendors-1440.png) | [390 px](screenshots/vendors-390.png) |
| Quotations | Sales placement, icon actions, details panel, signed PDF and bulk ZIP. | [1440 px](screenshots/quotations-1440.png) | [390 px](screenshots/quotations-390.png) |
| Invoices | Money records retained, signed PDF and bulk ZIP, shared controls. | [1440 px](screenshots/invoices-1440.png) | [390 px](screenshots/invoices-390.png) |
| Expenses | Insights placement, GST/campaign/bill fields, pending creation, separate checker and reversal. | [1440 px](screenshots/expenses-1440.png) | [390 px](screenshots/expenses-390.png) |
| Guests | Server paging, scoped search, human RSVP labels and safe check-in. | [1440 px](screenshots/guests-1440.png) | [390 px](screenshots/guests-390.png) |
| Tickets | Signed PDF, retained financial history and capacity-safe cancellation. | [1440 px](screenshots/tickets-1440.png) | [390 px](screenshots/tickets-390.png) |
| Gate scanner | Plain error copy, responsive scanner controls and retained verification flow. | [1440 px](screenshots/gate-1440.png) | [390 px](screenshots/gate-390.png) |
| Budgets | Event selection with paged rows, server variance sorting and export. | [1440 px](screenshots/budgets-1440.png) | [390 px](screenshots/budgets-390.png) |
| Work orders | Event-scoped server table, balance sort, documents and payment guard. | [1440 px](screenshots/work-orders-1440.png) | [390 px](screenshots/work-orders-390.png) |
| Client portal | Paged role-scoped sections with document downloads. | [1440 px](screenshots/portal-client-1440.png) | [390 px](screenshots/portal-client-390.png) |
| Vendor portal preview | Admin preview preserved; actual vendor access requires linked identity. | [1440 px](screenshots/portal-vendor-1440.png) | [390 px](screenshots/portal-vendor-390.png) |
| Reports | Readable server totals. Summary page remains outside the CRUD FilterBar contract. | [1440 px](screenshots/reports-1440.png) | [390 px](screenshots/reports-390.png) |
| Admin | Role-aware user list with shared server filtering and exports. | [1440 px](screenshots/admin-1440.png) | [390 px](screenshots/admin-390.png) |
| Login | Restrained brand treatment, compact sign-in form. | [1440 px](screenshots/login-1440.png) | [390 px](screenshots/login-390.png) |
| Public booking | Phone-safe booking layout and Indian date/currency display. | [1440 px](screenshots/public-booking-1440.png) | [390 px](screenshots/public-booking-390.png) |

## Additional inspected evidence

- [Dark quotations, desktop](screenshots/dark-quotations-1440.png) and [phone](screenshots/dark-quotations-390.png).
- [Quotation details, desktop](screenshots/quotation-details-1440.png) and [phone](screenshots/quotation-details-390.png).
- [Expense creation, desktop](screenshots/expenses-form-1440.png) and [phone](screenshots/expenses-form-390.png).

## Strict-spec gaps and boundaries

This is a substantial upgrade, not a claim of literal compliance with every sentence of the brief:

- Saved views are per user in this browser, not synchronized across devices. Queries, filters, sorting, pagination and exports run on the server.
- Dashboard/analytics/report summary collections do not all have module search, FilterBar or CSV/Excel export. Main CRUD lists do.
- Owner filtering applies only where the underlying module has an owner field. No artificial owners were added.
- Demand letters, receipts, credit notes, agreements and visit certificates are not existing Eventra modules. They were not invented. Existing quotation, invoice, ticket, work-order, closeout and expense-voucher document flows are covered.
- UI downloads use short-lived signed links and audit entries. Legacy bearer-authenticated PDF endpoints remain available for backward compatibility and do not all enforce that new path.
- Accessibility checks include labels, focus/Escape handling, responsive widths, dark-theme readability and basic keyboard use. A complete WCAG AA audit has not been performed. Some older create/edit forms still use placeholder-led visual labels.
- No soft-delete operation was introduced, so no eight-second soft-delete Undo toast was added. Financial records are retained, with expense reversal and ticket cancellation instead of deletion.
- The new expense voucher template is covered by response tests but was not separately visually inspected as a rendered PDF. Document UI and representative details were inspected.
- No deployment was requested or performed. Apply the migration and rebuild the frontend when deploying. Use the project's existing runbook and normal production backup procedure.
