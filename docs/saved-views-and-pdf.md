# Saved views sync and PDF access

- Saved list views are stored per user in `saved_views` (GET/POST /api/v1/saved-views, DELETE /api/v1/saved-views/{id}). The UI merges them with browser-local views, so views follow the user across devices.
- The legacy bearer-auth PDF routes (`/invoices/{id}/pdf`, `/quotations/{id}/pdf`, `/tickets/{id}/pdf`, `/work-orders/{id}/pdf`, `/events/{id}/closeout-pdf`) are removed. PDFs are only available through signed `/documents/{type}/{id}/link` URLs.
