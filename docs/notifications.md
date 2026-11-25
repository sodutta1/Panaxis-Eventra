# In-app notifications

A bell in the top bar (all roles, desktop and phone) with an unread count, a feed panel, and a full page at `/notifications`.

## What triggers a notification
| Event | Who is told | Opens |
|---|---|---|
| Expense created (needs a second approver) | Admins and accountants, except the creator | /expenses?status=pending |
| Expense approved | The person who created it | /expenses |
| Payment received on an invoice (notes if fully paid) | Admins and accountants, except the person who recorded it | /invoices |
| Lead created with an owner | The assigned user | /leads |
| Lead created without an owner | Admins, managers, sales execs | /leads |
| Lead reassigned | The new owner | /leads |
| Lead stage changed | The lead owner (unless they made the change) | /leads |
| Task due soon (existing reminder command) | The task assignee | the event |

Nobody is notified of their own action. Recipients are picked by role, so a notification never points at a screen the person cannot open on desktop. Clients and vendors only receive notifications addressed to them (none are generated yet).

## API (all need a login; every query is pinned to the caller's user and company)
- `GET /api/v1/notifications?per_page=&page=&filter=unread` returns `data`, `unread_count`, `meta`
- `GET /api/v1/notifications/unread-count`
- `POST /api/v1/notifications/{id}/read` (another user's id returns 404)
- `POST /api/v1/notifications/read-all`

Table `app_notifications` (migration 2026_11_25_000001). Fan-out lives in `App\Services\Notifier`; add a new trigger with one `Notifier::user(...)` or `Notifier::roles(...)` call.

## Phone behaviour
Panel is a full-screen sheet, rows are at least 64px tall, bell is 44px. The count refreshes every 60 seconds while the tab is visible and when the app regains focus.

## Not built (and why)
- Lock-screen push: needs PWA install, a service worker and a push provider (FCM / Web Push keys). Not in scope yet.
- Quotation accepted/rejected: the app has no client accept/reject action, so there is no event to hook.
- Per-user mute settings and email/WhatsApp copies of every item: the existing email/WhatsApp stubs are still log-only.
