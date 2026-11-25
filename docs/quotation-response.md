# Quotation send, accept and decline

The only client-facing action in Eventra. Everything else in the portal stays read-only.

Flow: staff create a draft, press **Send to client** (draft to sent), the client opens
Client portal > Quotations and presses **Accept** or **Decline** (optional note, 500 chars).

| Endpoint | Who | Notes |
| --- | --- | --- |
| `POST /api/v1/quotations/{id}/send` | admin, event manager, sales executive | draft only, 409 otherwise; notifies the client user |
| `POST /api/v1/portal/quotations/{id}/respond` | client role only | body `decision` = `accepted` or `rejected`, optional `note` |

Rules: the quotation must belong to the caller's own client record (otherwise 404), be in `sent`
status (409 if draft or already answered), and not past `valid_until` (409). The status change is
row-locked so two taps cannot both win. Sales roles get an in-app notification
("Quotation QTN-... accepted" with the rupee total and the client's note).

Migration `2026_11_25_000002`: adds `sent_at`, `responded_at`, `responded_by`, `response_note` to `quotations`.
Not built: auto-creating an event or invoice on acceptance (staff do that from the accepted quotation).

## Staff-recorded response (clients without digital access)

The client portal answer is optional. Staff (admin, event manager, sales executive) can press
**Record client response** on a draft or sent quotation and log what the client told them:
accepted or declined, how (phone, in person, verbal, written), plus an optional note.
`POST /api/v1/quotations/{id}/record-response` (migration `2026_11_25_000003`).

The trail never looks like a client action: `response_source` is `staff_recorded`, the list shows
"Recorded by <staff name> on the client's behalf (phone)", and other sales staff are notified with
"<name> recorded that <client> accepted it". A portal answer is stored as `client_portal`. Staff may
record after `valid_until` has passed, since they heard the answer directly. Answered quotations
(accepted or declined) cannot be recorded again (409).
