# Panaxis Eventra - Security Review (Phase 6, in progress)

Scope: backend (Laravel 11), /api/v1, git history, dependencies. Frontend
checks (npm audit, ESLint security rules, Lighthouse) land with Phase 5.

## Findings table

| # | Severity | Area | Finding | Status |
|---|----------|------|---------|--------|
| 1 | Critical | Web auth | GET /demo-login/{id} logged in ANY user by ID with no password (verified live: 302 straight to dashboard) | FIXED - env-gated: only when APP_ENV=local AND DEMO_LOGIN=true; 404 otherwise |
| 2 | High | API auth | Sanctum tokens never expired (expiration: null) | FIXED - 720 min (12h) |
| 3 | High | Web auth | POST /login had no rate limiting | FIXED - throttle:5,1 (API login already throttled, observed "Too Many Attempts" live) |
| 4 | High | Gate scanner | qr_token was QR-XXXX-XXXX-1234 (~8 letters + 4 digits) | FIXED - QR- + Str::random(40); single-use enforced by backend (409 on re-scan, test-covered); verify endpoint throttled 30/min |
| 5 | High | Seeds | Demo seeder creates users with password "password" | FIXED - seeder refuses to run when APP_ENV=production unless ALLOW_DEMO_SEED=true; production admin creation will be a one-time command (Phase 8) |
| 6 | Medium | Headers | No security headers | FIXED - SecurityHeaders middleware: X-Content-Type-Options, X-Frame-Options DENY, Referrer-Policy, HSTS on HTTPS, CSP default-src 'none' on /api/* |
| 7 | Medium | Dependencies | composer audit: 5 advisories against laravel/framework (e.g. CVE-2026-48019 CRLF email rule, Temporary Signed URL confusion). Most fixes only exist on Laravel 12.60+/13.x | OPEN - recommend framework upgrade 11 -> 12/13 before go-live; decision needed |
| 8 | Medium | Legacy web | Client/vendor portal web controllers load the FIRST record instead of scoping to the logged-in user (upstream bug; /api/v1 portals are correctly scoped) | OPEN - legacy Blade portals to be retired in favor of the SPA; will remove or fix before go-live |
| 9 | Info | Secrets | gitleaks scan of full git history | CLEAN (one false positive in generated Scribe cache, removed from history) |
| 10 | Info | SQL injection | No raw queries with user input; selectRaw/DB::raw usages are static strings | VERIFIED CLEAN |
| 11 | Info | XSS | No unescaped Blade output ({!! !!}) anywhere | VERIFIED CLEAN |
| 12 | Info | Tenancy | API scopes every query to company_id; cross-tenant access = 404 | TEST-COVERED (feature tests) |
| 13 | Info | RBAC | Every /api/v1 route behind auth:sanctum; writes gated by role middleware | TEST-COVERED (403 cases in tests + live captures) |

## Evidence
- docs/api-responses/: real 401/403 captures
- gitleaks 8.28.0 over all 8 commits: no leaks
- composer audit output recorded in docs/PROGRESS.md

## Still to verify (next steps)
- Larastan/PHPStan run, npm audit + ESLint security (frontend), CSP tuning for
  the SPA host, HTTPS/HSTS on the live domain (Phase 8), dependency upgrade
  decision (#7), legacy web portal scoping (#8), file upload checks if any
  upload endpoint is added.
