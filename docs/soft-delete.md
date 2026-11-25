# Soft delete and Undo

Deleting a lead, client, venue, vendor, guest, event or quotation no longer erases the row. The
record is hidden everywhere (`deleted_at` is set) and an admin can bring it back for as long as it
is kept. After any delete the app shows "<Record> deleted. Undo" for 10 seconds.

- Migration `2026_11_25_000002_add_soft_deletes`: adds `deleted_at` to leads, clients, venues, vendors, event_guests, events, quotations.
- Undo endpoint: `POST /api/v1/restore/{type}/{id}` (admin roles). `type` is one of leads, clients, venues, vendors, guests, events, quotations. Company scoped; 404 if the record is not deleted or belongs to another company.
- Existing delete guards are unchanged (clients with invoices or quotations, events with guests/tickets/work orders, vendors with paid work orders still return 409).
- Money records (invoices, payments, expenses, tickets) are never deleted; they keep their reverse/cancel flows.
- Draft-only delete is now enforced by the API: `DELETE /quotations/{id}` returns 409 unless the quotation is a draft (the UI already hid the button).
- Not built: a screen to browse deleted records, and automatic purging. Admins undo from the toast; older deletions can be restored by id through the API.
