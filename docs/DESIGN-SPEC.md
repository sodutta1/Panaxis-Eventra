# Panaxis Eventra - Design Spec (from docs/ui-samples/)

The layout notes below retain the original screenshot study. The brand colors
now follow the approved PanAxis logo-aligned deep forest palette.

## Color palette

| Token | Hex | Use |
|---|---|---|
| primary (pa-700) | #22543D | deep forest brand green: active navigation, chart accent, buttons and links |
| dark (pa-900) | #163A2B | darker companion for text and high-contrast details |
| logo light (pa-300) | #91B496 | light end of the PanAxis logo gradient |
| tint (pa-100) | #DCE9DF | soft fills and selected surfaces |
| pale (pa-050) | #F1F6F1 | pale booking background and hover surfaces |
| gray-100 | #F3F4F6 | app background |
| white | #FFFFFF | cards, sidebar, table surfaces |
| gray-900 | #111827 | primary text (near-black) |
| gray-500 | #6B7280 | secondary text ("<2 this month", "contract value") |
| amber-100/#92400E | badge warning | "In planning" pill: pale yellow bg, amber text |
| success badge | pa-050 bg + pa-700 text | "Confirmed" / "Active" pills |

## Typography
- Sans-serif system stack (Inter-like); headings ~18-20px semibold
  ("Quick Actions", "Upcoming events"); KPI numerals ~28-30px bold;
  KPI labels 13px gray-500; table header 11-12px uppercase gray-500
  letter-spaced ("EVENT / CLIENT / DATE / VENUE / STATUS").

## Layout
- Fixed left icon sidebar ~64-72px wide, white bg, icons stacked with tiny
  labels (Dashboard, Leads, Clients, Events, Venues, Vendors, Quotes,
  Budgets, RSVP...). Active item: pa-700 icon + left edge indicator.
- Content area on gray-100, max width fluid, 3-column card grid at desktop,
  cards: white, radius ~12-16px, border 1px #EEF0F2, no heavy shadow.
- KPI cards: big numeral left, small gray caption under; some with a thin
  pa-700 left border (Leads in Pipeline card).
- Tables: header row uppercase small gray; row dividers #F1F2F4; status as
  rounded-full pills; no vertical gridlines.
- Charts: donut (pa-700 arc on pa-100 track, % centered), line chart
  with brand stroke and soft pa-050 area fill.

## Components
- Buttons: primary = pa-600 bg, white text, radius 8; ghost = pa-700
  text with icon (Quick Actions grid uses ghost icon+label pairs).
- Badges/pills: rounded-full, 12px, medium weight (Confirmed green,
  In planning amber, Active green).
- Forms: white inputs on gray-100 bg, 1px #E5E7EB border, focus ring pa-600.
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

## Current theme tokens
The shared theme tokens in `frontend/src/index.css` and the PDF templates use the
logo-aligned deep forest palette above. Semantic status colors remain distinct
from brand colors. Font-sans uses the system stack; borderRadius lg=12px
and xl=16px; status pill variants are component classes.
