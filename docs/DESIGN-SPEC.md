# Panaxis Eventra - Design Spec (from docs/ui-samples/)

Measured from the two supplied screenshots (dashboard.jpg,
dashboard-leads-events.jpg). Hex values below were extracted from the
actual pixels, not guessed.

## Color palette

| Token | Hex | Use |
|---|---|---|
| brand-700 | #105830 | primary deep green: sidebar active icon, donut main arc, KPI accents |
| brand-600 | #185838 | primary green (buttons, links, highlights) |
| brand-100 | #C8E0D0 | light green tint: donut secondary arc, soft fills |
| brand-050 | #EAF3EE | hover tint / badge bg for success states (derived from tint family) |
| gray-100 | #F3F4F6 | app background |
| white | #FFFFFF | cards, sidebar, table surfaces |
| gray-900 | #111827 | primary text (near-black) |
| gray-500 | #6B7280 | secondary text ("<2 this month", "contract value") |
| amber-100/#92400E | badge warning | "In planning" pill: pale yellow bg, amber text |
| green badge | brand-050 bg + brand-700 text | "Confirmed" / "Active" pills |

## Typography
- Sans-serif system stack (Inter-like); headings ~18-20px semibold
  ("Quick Actions", "Upcoming events"); KPI numerals ~28-30px bold;
  KPI labels 13px gray-500; table header 11-12px uppercase gray-500
  letter-spaced ("EVENT / CLIENT / DATE / VENUE / STATUS").

## Layout
- Fixed left icon sidebar ~64-72px wide, white bg, icons stacked with tiny
  labels (Dashboard, Leads, Clients, Events, Venues, Vendors, Quotes,
  Budgets, RSVP...). Active item: brand-700 icon + left edge indicator.
- Content area on gray-100, max width fluid, 3-column card grid at desktop,
  cards: white, radius ~12-16px, border 1px #EEF0F2, no heavy shadow.
- KPI cards: big numeral left, small gray caption under; some with a thin
  brand-700 left border (Leads in Pipeline card).
- Tables: header row uppercase small gray; row dividers #F1F2F4; status as
  rounded-full pills; no vertical gridlines.
- Charts: donut (brand-700 arc on brand-100 track, % centered), line chart
  with brand stroke and soft brand-050 area fill.

## Components
- Buttons: primary = brand-600 bg, white text, radius 8; ghost = brand-700
  text with icon (Quick Actions grid uses ghost icon+label pairs).
- Badges/pills: rounded-full, 12px, medium weight (Confirmed green,
  In planning amber, Active green).
- Forms: white inputs on gray-100 bg, 1px #E5E7EB border, focus ring brand-600.
- Modals: centered white card, radius 16, overlay rgba(17,24,39,.4).
- Icons: thin stroke (lucide-style), 20px in sidebar, 16px inline.

## Screen-by-screen map (sample -> PRD module)
1. dashboard.jpg -> Dashboard (PRD 4.2): KPI row (Active Events, Revenue
   Booked with Rs. crore format, Days to Event), Quick Actions grid
   (Guests & RSVP, Promote, Vendors, Event Website, Agenda, Reports,
   Tickets, Budgets), Registration Trend line chart, Guest RSVP donut,
   Attendance donut, Upcoming Events table, Event Numbers stats.
2. dashboard-leads-events.jpg -> CRM/Dashboard variant (PRD 4.2/4.3):
   Leads in Pipeline KPI with left brand border, Upcoming events table
   (Event, Client, Date, Venue, Status pills), Urgent tasks list.
   Sidebar also shows Tickets & Gate, Invoices & GST, Reports entries.

## PRD screens with no sample (will be designed consistently with the above)
Login, Leads kanban board, Lead detail, Client list/detail, Event detail +
Run of Show editor, Tasks board, Venue/Vendor lists + work orders,
Budget/Expense tables, Guest list + RSVP manager, Ticket types + Gate
Scanner (mobile-first camera view), Quotation/Invoice builders + PDF print,
Client Portal, Vendor Portal, Admin/tenant settings. All will reuse these
tokens, card/table/pill patterns, and the same sidebar.

## Tailwind tokens (Phase 4 will encode)
colors.brand {50..900} anchored on #105830/#185838 with #C8E0D0 tint;
font-sans system stack; borderRadius lg=12px xl=16px; status pill variants
confirmed/planning/active as component classes.
