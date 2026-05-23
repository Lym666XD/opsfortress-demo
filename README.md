# OpsFortress Demo

## Overview

This repository contains the Laravel / PostgreSQL / React / Inertia technical scaffold for the OpsFortress / WHSAPP rebuild.

The current codebase is a **v0.3 schema-reset prototype**, not a finished WHS workflow app. As of the 2026-05-23 refactor, the Laravel migrations and regenerated DBML are the co-authoritative schema reference.

## Current Source of Truth

Use these sources in this order:

1. `database/migrations/2026_05_18_*` and `database/migrations/2026_05_23_*`
2. `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml`
3. `tests/Feature/Database/V03SchemaContractTest.php`
4. `database/seeders/V03DemoSeeder.php`
5. `docs/CHANGELOG_2026_05_23_REFACTOR.md`

Older architecture notes and archived prompts are useful context only. They are not schema authority.

## Current Implementation

- Laravel 13 with React + TypeScript via Inertia.js
- PostgreSQL as the active database target
- Local database queue/cache drivers and local filesystem storage for development
- Laravel Fortify starter auth, profile, password reset, email verification, and 2FA flows
- Domain-oriented backend folders under `app/Domain/...`
- `AccountContext`, `AccountScope`, and `BelongsToAccount` for account-scoped row isolation
- Hash-chained `AuditService` with canonical JSON, SHA-256 hashes, row locking, and `worker_task_session_id` linkage
- Append-only database triggers for `audit_events`, `signatures`, and `evidence_files`
- Importer framework and first SRC-001 slices for industries, occupations, and tasks

## v0.3 Direction

The current technical direction is:

```text
v0.3 schema baseline -> importer-first content loading -> worker runtime proof
```

This means:

1. Keep the fresh v0.3 Laravel migrations as the production schema direction.
2. Stop extending the old `tenants/businesses/task_packs/activities/submissions` model.
3. Build importer coverage early because Kevin's workbook pipeline is the real business asset.
4. Prove worker runtime from imported workbook data, not only from hand-written seed rows.
5. Resume larger frontend/admin work only after imported data can drive the v0.3 backend reliably.

## Legacy Schema Warning

The old scaffold tables and models for tenants, businesses, task packs, activities, submissions, file uploads, generated documents, roles, and workplace assignments have been superseded by the v0.3 account/business/workplace/task/runtime model.

Do not build new production workflows on top of the old scaffold concepts. If a historical document mentions `tenant_id`, `BelongsToTenant`, `task_packs`, or generic `submissions.payload`, treat that section as superseded unless a newer 2026-05-23 note says otherwise.

## Demo Login

After running `php artisan migrate:fresh --seed`, the v0.3 demo seeder creates:

- account: `Acme Construction`
- admin login: `admin@acme.test`
- worker login: `worker@acme.test`
- password: `password`

Run the app with:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000/dashboard`.

## Routes

Current notable routes:

- `/` -> starter Laravel welcome page
- `/preview` -> WHS module preview home
- `/preview/{slug}` -> static placeholder module page
- `/dashboard` -> authenticated starter dashboard

The legacy v0.2 `/admin/workplaces` slice was removed in M16. v0.3 admin UI should be rebuilt after importer-backed data can populate the real account/business/workplace/task model.

## Local Run

Assumptions:

- PostgreSQL is running on `127.0.0.1:5432`
- PHP / Composer are installed
- Bun is installed for frontend tooling
- Node is `20.19+` or `22.12+` for Vite

Typical commands:

```powershell
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Expected database settings:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=opsfortress_demo
```

## Verification

Latest 2026-05-23 verification:

- `php artisan migrate:fresh --seed` passed against PostgreSQL.
- `php artisan test` passed: 71 tests / 470 assertions / 8 skipped.
- `php vendor/bin/pint --test` passed.
- `php artisan route:list` passed with 36 routes.

Targeted database/importer checks also passed:

- `V03SchemaContractTest`
- `V03DevSeederTest`
- `AppendOnlyEnforcementTest`
- Industries, occupations, and tasks importer tests

## Documentation Map

Start with:

- `docs/README.md` for the documentation index
- `MILESTONE.md` for current build status and next actions
- `IMPLEMENTATION_ROADMAP.md` for delivery order
- `TARGET_ARCHITECTURE.md` for architecture direction
- `IMPORTER_INTAKE_NOTES.md` for importer-specific decisions and source-file notes
- `docs/CHANGELOG_2026_05_23_REFACTOR.md` for this refactor's audit trail

Historical prompt/review/plan files live under `docs/archive/`.
