# UI audit, 24 Nov 2026

Baseline: 791c8f69e8691e429a2f8c57be66976a1e4ea0c9

Source inspection covers all registered screens before implementation. Runtime screenshots and findings are recorded in design-review.md.

## Shared violations
- Tables lack server-driven name/reference/owner/date filters, full sorting, URL state, saved views, pagination controls and exports. Lead board is not a replacement for an accessible table.
- No module search highlighting or cross-module quick-find. Related-record selectors fetch only the first page.
- Flat sidebar shows forbidden destinations, lacks sections, pinned favourites, persistent width and phone drawer focus handling.
- Technical endpoint copy is visible in subtitles. Raw field names can leak through validation errors and option labels.
- Rounded cards, gradients, inconsistent typography and spacing, no dark theme or reduced-motion policy.
- Document downloads omit signed URL expiry, download audit, bulk ZIP, expense voucher and consistent detail actions.
- Expense creation incorrectly marks records paid and self-approved. Missing GST, campaign, bill and maker/checker model.
- Detail screens render nested collections in long scrolls, without lazy sections or stream paging.

## Screen inventory
### Admin
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Analytics
- loading/error/empty-state review needed.
- colour-heavy chart cards and unpaginated ranked lists.
### Budgets
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- event selector limited to 50, eager budget rows.
### ClientPortal
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- embedded document lists missing PDF actions.
### Clients
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Dashboard
- loading/error/empty-state review needed.
- dense task streams and decorative chart cards.
### EventDetail
- loading/error/empty-state review needed.
- eager schedule/task collections and long forms.
### Events
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Expenses
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### GateScanner
- technical endpoint copy.
- loading/error/empty-state review needed.
- raw verification errors and unbounded scan history.
### Guests
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Invoices
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Leads
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Login
- split hero layout and unlabelled demo expectations.
### PublicBooking
- loading/error/empty-state review needed.
- inconsistent radii and mobile form layout.
### Quotations
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Reports
- technical endpoint copy.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Tickets
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### VendorPortal
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- embedded work orders lack PDF actions; existing vendor linkage limitation remains.
### Vendors
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### Venues
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- forms need consistent labels, layout, human status text and permission checks.
### WorkOrders
- technical endpoint copy.
- uncontrolled list table, missing shared list tools.
- loading/error/empty-state review needed.
- event selector limited to 50, no detail PDF header.

## Added action requirements (rules 8 and 9)
- Baseline text Edit/Delete links lack a single icon system, tooltip/accessible label policy and phone action menus.
- Download labels vary; a shared spinner/error pattern and exact Download PDF wording are required.
- Financial record deletion conflicts with history retention. Expense reversal needs a reason; maker/checker approval must not allow self-approval.
- Final treatment and unresolved strict-spec gaps are recorded in design-review.md.
