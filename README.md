# OpsFortress Demo

Multi-tenant WHS (Work Health & Safety) platform — Laravel + PostgreSQL backend with React/TypeScript frontend served via Inertia.js. Single codebase, modular monolith.

> Current state (2026-05-14): Phase 0 architecture foundations done. M9 (core domain models + seeders) done. M10 Slice 1 "Add Workplace" end-to-end done. 72 tests passing. See `MILESTONE.md` for the full roadmap.

## Stack

- **PHP 8.3+ / Laravel 13** — modular monolith with domain folders under `app/Domain/{OpsFortress, Whs, Shared}/`
- **PostgreSQL 14+** — the only supported database in production (SQLite is used in tests only)
- **React 19 + TypeScript** — pages rendered via Inertia.js, so there is **no separate REST API and no separate frontend server**
- **Bun** — frontend package manager / dev server, runs Vite
- **Laravel Herd** — local PHP + nginx (handles the `.test` domain on Windows / macOS)
- **Laravel Fortify** — auth, password reset, 2FA, email verification (public registration intentionally disabled — admin invite only)

## Architecture in 30 seconds

```
Browser  →  Herd (http://opsfortress-demo.test)  →  Laravel
                                                       ↓
                                            Inertia returns JSON props
                                                       ↓
                                       React (loaded from Vite dev server) renders
```

There is **no separate "frontend app"** to log into — the React pages are served by Laravel as part of the same request. Login goes to `http://opsfortress-demo.test/login`, not to the Vite dev server port.

Key invariants enforced in code (don't break these):

- **Multi-tenant isolation** — every domain row carries `tenant_id`; the `BelongsToTenant` trait auto-stamps and auto-filters. See `app/Domain/Shared/Tenancy/`.
- **Audit hash-chain** — material events (signatures, closeouts, admin config changes) write SHA-256 chained records via `AuditService`. See `app/Domain/Shared/Audit/`.
- **Append-only payloads** — `submissions.payload` and `activities.payload` cannot be updated after creation (model `booted()` observer throws).
- **Internal-only `blockchain_id`** — ULID, auto-generated on Business creation, immutable, hidden from serialisation. Never shown to customers.

## Prerequisites

| Tool | Version | Why |
|---|---|---|
| **Laravel Herd** | latest | Serves `http://opsfortress-demo.test` and provides PHP / Composer |
| **PostgreSQL** | 14+ | Active dev DB |
| **Bun** | latest | Frontend tooling (`bun run dev`, `bun run build`) |
| **Node** | not required | Bun replaces it |

## First-time setup

```powershell
# 1. Ensure Herd is running (system tray)
# 2. Ensure PostgreSQL is running on 127.0.0.1:5432

# 3. Add Herd + Bun to PATH for this session
$env:PATH = "C:\Users\User\.config\herd\bin;C:\Users\User\.bun\bin;" + $env:PATH

# 4. Install dependencies
Set-Location C:\Users\User\Desktop\WHSAPP\opsfortress-demo
composer install
bun install

# 5. Configure environment
Copy-Item .env.example .env
php artisan key:generate

# Edit .env to set DB_DATABASE / DB_USERNAME / DB_PASSWORD for your local Postgres.

# 6. Migrate + seed the demo data
php artisan migrate
php artisan db:seed

# 7. Link the project in Herd (open Herd → Sites → Link → choose this folder).
#    Herd then serves http://opsfortress-demo.test automatically.
```

## Day-to-day development

```powershell
# Single command that starts Vite + queue listener concurrently
composer run dev
```

Then browse `http://opsfortress-demo.test/login`.

### Demo credentials (created by seeders)

All three users have password `password`:

| Email | Role | Use to test |
|---|---|---|
| `admin@demo.test` | Admin | Workplace management, future admin features |
| `supervisor@demo.test` | Supervisor | (future) team review, assignment workflows |
| `worker@demo.test` | Worker | (future) mobile task flow, SWMS acknowledgement |

### Running tests

```powershell
php artisan test       # 72 passing / 2 intentionally skipped
```

Tests use SQLite in-memory; production uses Postgres. Migrations work on both.

## Common gotchas

### Vite dev server fails with `EACCES on ::1:5173` (Windows-only)

**Cause:** Windows + Hyper-V randomly reserves 50-port blocks at boot. Vite's default port 5173 often falls inside one of these blocks, and binding fails with `permission denied` even though no process is using the port.

**Fix in this repo:** `vite.config.ts` locks Vite to port **4173** (vite's preview default, outside the typical reservation zone).

**Diagnose any port issue:**

```powershell
netsh int ipv4 show excludedportrange protocol=tcp
netsh int ipv6 show excludedportrange protocol=tcp
```

If 4173 ever ends up inside a reserved range on your machine (rare — reservations cluster in 5000–6400), pick another port in `vite.config.ts`. Safe zones: **4000–4999**, **6386+**, **8000+**.

To clear the reservations (requires Administrator PowerShell, temporary fix until next reboot):

```powershell
net stop winnat
net start winnat
```

### `composer run dev` terminal got closed and now `opsfortress-demo.test` won't load

The Vite dev server died with the terminal. Open a fresh PowerShell, `cd` back to the project, run `composer run dev` again. Herd itself runs separately and is fine.

### `php artisan migrate` complains about `doctrine/dbal`

Laravel 13 has native column modification, so this shouldn't happen — but if it does, `composer require doctrine/dbal --dev`.

### Test failures with `Unable to locate file in Vite manifest`

A `.tsx` page that the test renders isn't in the production manifest. Either run `bun run build` once, or — preferred — add `$this->withoutVite()` in the test's `setUp()`. See `tests/Feature/Admin/WorkplaceManagementTest.php` for the pattern.

## Project layout

```
opsfortress-demo/
├── app/
│   ├── Domain/
│   │   ├── OpsFortress/    ← identity & relationships (tenants, businesses, workplaces, users, roles)
│   │   ├── Whs/            ← workflow layer (task packs, activities, submissions, files)
│   │   └── Shared/         ← cross-cutting (tenancy, audit)
│   ├── Http/Controllers/   ← admin/ for backoffice; settings/ for self-service
│   ├── Http/Requests/
│   └── Policies/
├── database/
│   ├── migrations/
│   └── seeders/            ← PlatformCatalogSeeder + DemoTenantSeeder
├── resources/js/
│   ├── pages/              ← Inertia page components (admin/, settings/, auth/, etc.)
│   ├── layouts/
│   └── components/
├── routes/
│   ├── web.php             ← all HTTP routes
│   └── settings.php
└── tests/Feature/          ← phpunit feature tests, organised by domain
```

## Documentation

- **`TARGET_ARCHITECTURE.md`** — authoritative architecture decisions (tenancy, content normalization, authz strategy, audit anchors)
- **`IMPLEMENTATION_ROADMAP.md`** — phased build plan
- **`MILESTONE.md`** — what's done, what's pending, Slice queue, Kevin-driven decisions

## License

Proprietary — internal OpsFortress / WHS App project. Not for public distribution.
