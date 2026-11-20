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
