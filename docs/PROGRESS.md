# Panaxis Eventra - Build Progress Log

Mission: migrate EventFlow ERP into a new repo, build the frontend to match
docs/ui-samples, secure it, deploy end to end. Brief received 2026-11-21
02:29 IST (WhatsApp). Repo name chosen by owner: **Panaxis-Eventra**.

## Phase 0 - License and repo migration (local work; push gated)

Commands run and findings:

1. `git clone -o upstream https://github.com/webotapp-academy/EventFlow-ERP-by-webotapp.git Panaxis-Eventra`
   - OK. Upstream HEAD: 08beb15 "build: configure PHP 8.5 target platform, CI workflow, and automated test suite".
   - Author of all 5 upstream commits: webotapp-academy <zzubizubi@gmail.com>.
2. License check:
   - `ls` shows **no LICENSE / LICENCE / COPYING file** anywhere in the repo.
   - `grep -i license` finds only `"license": "MIT"` in composer.json - that is
     Laravel skeleton boilerplate, not a license grant.
   - Conclusion: **all-rights-reserved**. Owner was warned on 2026-11-19 and
     again before this build; he acknowledged (2026-11-21 02:30 IST) and chose
     to proceed. Commercial use still needs the author's written permission.
   - NOTICE.md added with attribution and this explanation.
3. Monorepo layout applied (git mv, history preserved):
   - backend/    - the full Laravel 11 app (upstream code, unmodified)
   - frontend/   - new React app lands here (Phase 4)
   - deploy/     - Docker/compose config lands here (Phase 7)
   - docs/       - PRD.md (owner's PRD), PRD-frontend-slices.md (implementation
                   PRD built from a full codebase scan on 2026-11-20),
                   ui-samples/ (2 screenshots to match), PROGRESS.md (this file)
   - .github/workflows/ - upstream ci.yml kept; paths updated for backend/
4. Root .gitignore added: **/.env, **/.env.* (except .env.example), vendor,
   node_modules, storage logs, build output.
5. GitHub repo creation + push: GATED - waiting on owner's `gh auth login`.

## Phase 1 - Run backend and database

Toolchain (no root, no Docker in sandbox):
- PHP 8.5.8 static cli (static-php.dev), Composer 2.11.2 phar - matches composer.json `php: ^8.5`.
- MySQL Server 8.0.46 (Ubuntu mysql-server-core-8.0 deb extracted, no root),
  port 3307, socket in /downloads/mysqlsrv/run.

Commands run:
- mysqld --initialize-insecure + start on 127.0.0.1:3307 - OK
- CREATE DATABASE panaxis_eventra (utf8mb4); CREATE USER eventra_app@localhost/127.0.0.1
  with GRANT on panaxis_eventra.* only (least privilege, not root). Password:
  40-hex random, stored only in backend/.env (gitignored).
- composer install - OK (exit 0)
- cp .env.example .env; DB_* set; php artisan key:generate - OK
  (note: .env.example ships DB_* lines commented out; sed of uncommented
  lines is required - gotcha hit and fixed)
- php artisan migrate:fresh --seed - OK: 11 migrations, seed OK
  (6 users, 8 roles, 7 events, 2 leads, 2 invoices)
- php artisan test - PASS: 10 tests, 23 assertions, 0 failures (exit 0)
- php artisan route:list saved to docs/route-list.txt - 82 web routes
- php artisan serve (127.0.0.1:8000): /login 200, / 200, /events 302 (auth redirect) - OK

Changes made:
- config/database.php: replaced deprecated PDO::MYSQL_ATTR_SSL_CA with a
  compat constant (Pdo\Mysql::ATTR_SSL_CA when available). One remaining
  deprecation comes from Laravel's own merged vendor config on PHP 8.5 -
  upstream framework issue, does not fail tests; tracked for Phase 6.
