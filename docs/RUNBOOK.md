# Runbook - Panaxis Eventra (local operation)

## Services

| Piece | Command | Binds |
| --- | --- | --- |
| MySQL 8 (sandbox) | see below | 127.0.0.1:3307, socket /downloads/mysqlsrv/run/mysqld.sock |
| Laravel API | `cd backend && DB_PASSWORD=... php artisan serve --host=127.0.0.1 --port=8000` | 127.0.0.1:8000 |
| Vite dev | `cd frontend && npx vite --host 127.0.0.1 --port 5173` | 127.0.0.1:5173 |
| Vite preview (prod build) | `cd frontend && npm run build && npm run preview` | 127.0.0.1:4173, proxies /api |

Important: start Vite with `--host 127.0.0.1` explicitly - a bare `npm run dev` can bind to ::1 only,
which breaks the e2e tests and any 127.0.0.1 client.

## Sandbox MySQL (this machine)

Datadir: /downloads/mysqlsrv/data. The app DB password lives only in /downloads/mysqlsrv/.eventra_app_pw
(never committed; backend .env reads it via DB_PASSWORD at serve time).

Start:
```
/downloads/mysqlbin/mysqld --datadir=/downloads/mysqlsrv/data --socket=/downloads/mysqlsrv/run/mysqld.sock \
  --port=3307 --bind-address=127.0.0.1 --mysqlx=OFF &
```
Stop: `mysqladmin -S /downloads/mysqlsrv/run/mysqld.sock -u root shutdown` (root socket auth)
or kill the mysqld pid.

## Reset demo data

E2E tests intentionally mutate demo data. To reset:
```
cd backend && DB_PASSWORD=... php artisan migrate:fresh --seed
```

## Health checks

- API: `curl -X POST 127.0.0.1:8000/api/v1/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@eventflow.test","password":"password"}'` -> 200 + token
- DB: `php artisan migrate:status`
- Full regression: `php artisan test && (cd ../frontend && npx vitest run && npx playwright test)`

## Logs

- Laravel: backend/storage/logs/laravel.log
- Vite: terminal output
- MySQL: /downloads/mysqlsrv/data/*.err

## Known operational notes

- Login is rate-limited: 5 attempts/min in production (APP_ENV=production), 120/min otherwise. A 429 means
  the limiter is working - wait a minute.
- artisan serve does not hot-reload PHP code - restart it after pulling backend changes.
