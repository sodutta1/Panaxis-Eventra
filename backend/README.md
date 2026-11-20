# EventFlow ERP – Enterprise Event Management Software

An enterprise-grade, multi-tenant **Event Management ERP System** developed with PHP 8.5+, Laravel 11+, MySQL 8+, and Tailwind CSS. Built to power turnkey event management companies, wedding planners, corporate summit producers, live concert organizers, and venue operators.

---

## 🌟 Key Functional Capabilities

### 1. Multi-Tenant Architecture & RBAC
- Multi-company data isolation with branch and department hierarchy (Mumbai HQ, Delhi NCR, Bangalore Tech Hub).
- Role-Based Access Control (RBAC): Super Administrator, Event Director, Sales Lead, Lead Accountant, Operations Staff, Client Portal User, Vendor Partner.

### 2. CRM & Lead Pipeline
- Lifecycle tracking: `New` &rarr; `Contacted` &rarr; `Qualified` &rarr; `Proposal Sent` &rarr; `Negotiating` &rarr; `Converted` &rarr; `Lost`.
- One-click lead conversion into registered Client profiles and confirmed Event productions.
- Lead interaction logging and scheduled follow-ups.

### 3. End-to-End Event Operations
- Supports multiple event types: Luxury Weddings, Corporate Summits, Live Concerts/EDM Festivals, Milestone Galas, College Festivals, Exhibitions, and Product Launches.
- **Minute-by-Minute Run of Show**: Daily schedules with designated in-charge leads, cue notes, and performer tracking.
- **Production Milestones & Checklists**: Urgent/High priority task assignment with real-time completion toggles.

### 4. Partner Venues & Vendor Management
- Venue directory with capacity metrics, day rental rates, availability status, and amenities tracking.
- Vendor database across sound engineering, floral decor, catering, cinematography, and stage fabrication.
- Purchase work orders and contract tracking with disbursement monitoring.

### 5. Budgeting & Financial Control
- Cost category budgets with variance reporting (Estimated vs Actual spend).
- Contingency reserve calculations.
- Direct expense tracking, vendor advances, municipal NOC/licensing fees, and payment receipt logs.

### 6. Guest Logistics, RSVPs & Gate Check-in
- Guest groups (VIPs, Family, Media, Corporate Delegates).
- Unique invitation pass codes, table numbers, dietary preferences, and plus-one tracking.
- One-click gate admission toggle.

### 7. Ticketing & Gate QR Scanner
- Multi-tier ticket types (General Admission, VIP Passes, Early Bird).
- Unique QR tokens and barcodes.
- **Live Gate Scanner**: Instant token verification preventing duplicate check-ins.

### 8. Billing, GST Invoicing & Accounting
- Itemized quotation builder with customizable deliverables and 18% GST calculation.
- Milestone tax invoices with partial payment recording and balance due tracking.
- Printable, GST-compliant invoice templates.

### 9. Dedicated Portals
- **Client Portal**: View production itineraries, run-of-show timelines, approved quotations, and invoices.
- **Vendor Portal**: Review work orders, technical scope of work, and advance disbursement history.

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.5+
- MySQL 8.0+ (running on `127.0.0.1:3306`)
- Composer 2.x

### Quick Installation
```bash
# 1. Clone or navigate to the project directory
cd /Applications/XAMPP/xamppfiles/htdocs/event

# 2. Install Composer dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Migrate and seed the database with realistic demo data
php artisan migrate:fresh --seed

# 5. Run automated test suite
php artisan test

# 6. Launch development server
php artisan serve
```

Access the system at `http://localhost:8000`.

---

## 🔑 Demo Credentials (Development Documentation)

Default password for all demo accounts: `password`

| Role | Name | Email | Access Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | Vikramaditya Singhania | `admin@eventflow.test` | Full ERP System Access |
| **Event Manager** | Priya Sharma | `manager@eventflow.test` | Production Staging & Run of Show |
| **Sales Executive** | Rajesh Verma | `sales@eventflow.test` | CRM Leads & Quotations |
| **Lead Accountant** | Anita Desai | `finance@eventflow.test` | Invoicing, Payments & Expenses |
| **Client Portal** | Rahul Oberoi | `client@eventflow.test` | Client Self-Service Portal |
| **Vendor Partner** | Farhan Qureshi | `vendor@eventflow.test` | Vendor Work Orders & Contracts |

*Quick-switch demo role buttons are also available on both the `/login` screen and top navigation bar.*

---

## 🏛️ Seeded Demo Productions

The database includes realistic demo productions demonstrating the complete event lifecycle:
1. **Royal Heritage Wedding of Aryan & Sanjana** (`EVT-2026-WED-001`) – ₹42L Contract, 3-day Run of Show, 450 guests.
2. **Global Tech Innovators Summit 2026** (`EVT-2026-COR-002`) – ₹27.5L Contract, 800 attendees.
3. **Golden Jubilee Birthday Gala** (`EVT-2026-BDY-003`) – ₹10.5L Contract, 150 guests.
4. **Sunwave Sunset Live Music & EDM Festival** (`EVT-2026-CON-004`) – ₹72L Contract, 4,500 attendees, active ticketing.
5. **Ignite 2026 National Youth & Cultural Fest** (`EVT-2026-CLG-005`) – ₹18.5L Contract.
6. **International Auto & Next-Gen Mobility Expo 2026** (`EVT-2026-EXH-006`) – ₹55L Contract.
7. **Lumina X Smartphone Global Keynote Launch** (`EVT-2026-LCH-007`) – ₹22L Contract, Completed.

---

## 🧪 Automated Test Suite

Run all unit and feature tests:
```bash
php artisan test
```

Test coverage includes authentication, tenant isolation, event creation, status transitions, lead conversions, run of show itineraries, tasks checklists, and gate QR pass validation.

# EventFlow-ERP-by-ewebotapp
