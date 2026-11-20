# Panaxis Eventra - Security Review (Phase 6, complete)

Scope: backend (Laravel), /api/v1, git history, dependencies, frontend
(npm audit, Lighthouse), static analysis (Larastan).

## Findings table

| # | Severity | Area | Finding | Status |
|---|----------|------|---------|--------|
| 1 | Critical | Web auth | GET /demo-login/{id} logged in ANY user by ID with no password (verified live: 302 straight to dashboard) | FIXED - env-gated: only when APP_ENV=local AND DEMO_LOGIN=true; 404 otherwise. Also moved the flag to config('app.demo_login') so it behaves correctly with config caching |
| 2 | High | API auth | Sanctum tokens never expired (expiration: null) | FIXED - 720 min (12h) |
| 3 | High | Web auth | POST /login had no rate limiting | FIXED - throttle:5,1 (API login already throttled, observed "Too Many Attempts" live) |
| 4 | High | Gate scanner | qr_token was QR-XXXX-XXXX-1234 (~8 letters + 4 digits) | FIXED - QR- + Str::random(40); single-use enforced by backend (409 on re-scan, test-covered); verify endpoint throttled 30/min |
| 5 | High | Seeds | Demo seeder creates users with password "password" | FIXED - seeder refuses to run when APP_ENV=production unless ALLOW_DEMO_SEED=true; production admin creation will be a one-time command |
| 6 | Medium | Headers | No security headers | FIXED - SecurityHeaders middleware: X-Content-Type-Options, X-Frame-Options DENY, Referrer-Policy, HSTS on HTTPS, CSP default-src 'none' on /api/* |
| 7 | Medium | Dependencies | composer audit: 5 advisories against laravel/framework v11.57.1 (incl. high-severity CRLF injection in the email rule, CVE-2026-48019; fixes only in 12.60+/13.x) | FIXED - upgraded to laravel/framework v12.73.0; composer audit now reports zero advisories; full suite 28/28 passing after the upgrade |
| 8 | Medium | Legacy web | Client/vendor portal web controllers loaded the FIRST record with no company scoping - cross-tenant data leak (upstream bug) | FIXED - legacy Blade portal routes, controller and views REMOVED; portals are served only by the correctly scoped /api/v1 endpoints + React pages. Legacy layout nav links cleaned up |
| 9 | Info | Secrets | gitleaks scan of full git history | CLEAN (one false positive in generated Scribe cache, removed from history) |
| 10 | Info | SQL injection | No raw queries with user input; selectRaw/DB::raw usages are static strings | VERIFIED CLEAN |
| 11 | Info | XSS | No unescaped Blade output ({!! !!}) anywhere | VERIFIED CLEAN |
| 12 | Info | Tenancy | API scopes every query to company_id; cross-tenant access = 404 | TEST-COVERED (feature tests) |
| 13 | Info | RBAC | Every /api/v1 route behind auth:sanctum; writes gated by role middleware | TEST-COVERED (403 cases in tests + live captures) |
| 14 | Info | Frontend deps | npm audit | CLEAN - 0 vulnerabilities (re-checked after adding vitest, lighthouse) |
| 15 | Info | Static analysis | Larastan (PHPStan) level 5 over app/ | RUN - 396 findings, 387 are dynamic Eloquent attribute/relation noise typical of un-annotated models. 2 real bugs found and FIXED: Ticket model missing datetime cast for checked_in_at (fatal in the web "already admitted" path) and env() call outside config in demo login. Remaining 7 are benign generics/view-string typing |

## Evidence
- docs/api-responses/: real 401/403 captures
- docs/lighthouse/: production-build Lighthouse JSON (login 100/100/100, dashboard 97/100/100)
- gitleaks 8.28.0 over full history: no leaks
- composer audit: 0 advisories (Laravel 12.73.0)
- phpstan.neon committed; re-run with `vendor/bin/phpstan analyse`

## Residual / future hardening (non-blocking)
- CSP for the SPA host and HTTPS/HSTS belong to deploy time (deployment descoped by owner; deploy.yml scaffolding is ready).
- Consider @property annotations or laravel-ide-helper to make static analysis signal sharper.
- Add per-file-type validation if any upload endpoint is introduced.
