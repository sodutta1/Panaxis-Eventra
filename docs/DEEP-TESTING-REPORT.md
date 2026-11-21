# Panaxis Eventra: deeper testing and fixes

21 November 2026

## Result

The earlier green report covered normal flows but missed real edge-case and authorization bugs. The starting revision was `f5a33df`. Its 33 backend tests and 176 assertions passed again, but 11 of the first 12 new adversarial tests failed. Those failures were reproduced and fixed, and the checks were extended further.

The final code passes **68 backend tests / 669 assertions**, **17 frontend unit tests**, and **15 browser E2E tests** against a production frontend build and a running local database. Six live concurrency scenarios, each with 12 simultaneous HTTP requests, also pass. This is stronger coverage, not a claim that every possible bug has been eliminated.

Code revisions: `7e565db` (backend security and data consistency), `2a1c654` (scanner, demo visibility and PDFs), and `8feee7c` (bulk-holder label boundary).

## Findings by severity

### Critical and high

| Finding | What was wrong | Fix and evidence |
|---|---|---|
| Cross-company relationship injection | A valid ID from another company could be attached to an invoice, guest, event or other record. Invoice responses could then load the foreign client. | Tenant ownership is checked before relationship writes. Direct create probes and all 11 supported tenant-reference fields reject foreign IDs. Cross-company read, patch, delete, PDF and QR probes also pass. |
| Legacy authorization bypass | The old Blade ERP routes remained reachable and used controllers without the API's company/role checks. | Legacy routes now return 404 by default. Opt-in requires both a local environment and `ENABLE_LEGACY_ERP=true`; production cannot enable them. The React API is unaffected. |
| Unsafe vendor portal | A vendor login received the company's first vendor profile, rather than a profile linked to that login. | Vendor-role requests now return 403 with a clear account-linking message. Admin previews remain. Real vendor login linking is still a missing feature. |
| Inactive or unassigned accounts | Inactive users could still log in. A staff account without a company inherited company 1 through a fallback. | Login and staff/portal guards reject inactive or unassigned accounts. The company-1 fallback was removed. |
| Invalid gate admissions | Cancelled and refunded passes could be admitted. Check-then-update admission was also vulnerable to concurrent scans. | Invalid statuses are rejected without mutation. Admission is protected by a transaction and row lock. Twelve simultaneous scans produce exactly one 200 and eleven 409 responses. |
| Oversold or mismatched tickets | Capacity was not enforced; a ticket type from another event could be used. | Type/event consistency, active status and capacity are checked under locks. Twelve requests for the last place produce one 201 and eleven 422 responses. Deleting a ticket releases its counters exactly once. |
| Concurrent financial updates | Invoice balance checks could become stale between requests. Vendor-payment updates could be lost; the live probe recorded only 90 after twelve payments of 10 against a 100 contract. | Invoice and work-order updates are serialized. The same probes now preserve an 80 payment with 20 remaining on a 100 invoice, and a capped 100 work-order total. |

### Medium

| Finding | Fix and evidence |
|---|---|
| Invalid values caused HTTP 500 | Added storage-size, integer-ID, money-precision, time-format and text limits. Covered nulls for non-null columns, array IDs/passwords, malformed times, huge numbers, overlong strings, Unicode and bcrypt's byte limit. Invalid requests return 422. |
| Incorrect financial boundaries | Negative tax, reversed document dates and overflow totals are rejected. Line amounts and tax are rounded consistently to two places. Small invoice balances below 1 can now be paid in full. Document creation is transactional. |
| Duplicate lead conversion | Conversion is transactional and idempotent. Twelve simultaneous conversions return one client ID instead of creating duplicate clients. |
| Budget overflow and update consistency | Budget writes are serialized, totals are checked and failed inserts roll back. Twelve concurrent additions of 10 increase the total by exactly 120. |
| Cross-module mismatches | Invoice/quotation clients must match their selected event. Guest groups and schedule days must belong to the selected event. Moving a guest cannot silently retain a group from the old event. Partial event-date edits compare against the persisted other date. |
| PDF clipping | A valid 255-character unbroken description pushed numeric columns outside the page. Invoice and quotation tables now have fixed column widths and wrapping. A five-page invoice and three-page quotation have no off-page text; sampled first, middle and final pages were visually inspected. |
| Camera lifecycle and dropped scans | Blocked overlapping starts, cleaned up cameras that finish starting after navigation, displayed string-valued camera errors and stopped debouncing a second token that arrived while the first request was busy. Four new component tests cover these states. |
| Demo mode ignored | Seeded accounts and default credentials were always shown. They now require explicit `VITE_DEMO_MODE=true`. The example configuration defaults to false. Two regression tests cover both modes. |

Random four-digit invoice, quotation and payment numbers were also replaced with collision-resistant identifiers. The PHPUnit configuration warning about a missing environment value was corrected.

## Verification

| Check | Result |
|---|---|
| Backend PHPUnit | 68 passed, 669 assertions, no runner warnings |
| Role denial matrix | 322 forbidden-route probes across all nine roles passed |
| Frontend Vitest | 17 passed, including six new scanner/login tests |
| Production build | Passed; existing large-bundle warning remains |
| Browser E2E | 15 passed, no retries needed in the final run |
| Concurrency | 72 simultaneous HTTP operations across six scenarios passed |
| PDF bounds | All text stayed on-page across the five-page invoice and three-page quotation |
| Composer audit | No advisories or abandoned packages reported |
| npm audit | Zero reported vulnerabilities |
| Frontend lint | Zero errors, four existing warnings |
| PHP style | Changed PHP files pass Laravel Pint |

The role matrix verifies the declared route allowlists, which were checked against the repository's documented role groups. It does not replace a future business-policy review of those groups.

Browser testing included a 360px scanner viewport, with no horizontal page overflow. The scanner's non-native decoder read a fresh QR from Chromium's synthetic webcam, received admission from the real API, rejected a duplicate, and released the media track on Stop. A separate no-camera run showed the error and successfully used manual entry. Camera-start race tests use a mocked camera library to control timing; the QR decode check uses the real library.

## Environment and limits

- PHP 8.5.13, Laravel 12.73.0, MariaDB 10.6.23, Node 22 and Chromium. This run did not use MySQL 8 itself.
- Physical webcams, actual device permission prompts, Safari and Firefox remain unverified.
- The Unicode checks cover accented text, currency symbols and multi-byte boundary validation. They do not certify every script or font-shaping combination.
- Tests used isolated local seed data. No user installation was updated and nothing was deployed.
- Historical Blade rendering tests now opt into local-only legacy mode explicitly. The default application and production-denial tests assert the new secure behavior.
- Vendor E2E expectations now assert safe denial instead of accepting an unrelated vendor profile.

## Design questions left unchanged

- Quotations and invoices still do not have edit endpoints/UI.
- Vendor/user linking needs a schema and account-management workflow. Vendor logins are blocked until that exists.
- Pending-payment ticket admission and capacity release on cancellation/refund need explicit business rules. Existing behavior is unchanged; deletion releases capacity.
- Operations users can explicitly change ticket status, including revalidating a used pass. Decide whether that needs a narrower override permission or an audit trail.
- Hard deletes retain the schema's existing cascade/null behavior. Financial retention, soft deletion and audit-history requirements were not invented in this pass.
- Work-order overpayments retain the existing cap behavior rather than rejecting the whole request.

## Reproduce

Use a disposable, seeded local database. Never point the mutation probes at real customer data.

```sh
cd backend
php vendor/bin/phpunit
# With the local API running on 127.0.0.1:8000:
python tests/probes/concurrency.py
# Optional PDF geometry checks after the suite exports its stress PDFs:
python tests/probes/pdf_bounds.py /tmp/eventra-edge-invoice.pdf
python tests/probes/pdf_bounds.py /tmp/eventra-edge-quotation.pdf

cd ../frontend
npm ci
npx vitest run
npm run build
# Start npm run preview on port 5173 before the next command:
npx playwright test --workers=1
```

Optional synthetic-camera check, with the API and frontend preview running:

```sh
# Requires Python qrcode/Pillow, ffmpeg and installed Playwright Chromium.
cd backend
python tests/probes/prepare_camera.py
cd ../frontend
node scripts/deep-camera-check.mjs
```

Camera evidence is written under the system temporary directory's `eventra-evidence` folder, or `EVENTRA_EVIDENCE_DIR` if supplied. It contains local demo data only.

## Updating a local checkout

Pull the latest main branch, run `npm ci` in `frontend`, and restart the frontend and backend processes. Run `php artisan optimize:clear` in `backend` if configuration/routes were cached. No database migration or new production PHP dependency is needed for these fixes. Keep `ENABLE_LEGACY_ERP=false`. Only enable frontend demo shortcuts deliberately in a throwaway local environment.
