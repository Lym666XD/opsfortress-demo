# OpsFortress Demo

## Overview

This folder contains the Laravel/PostgreSQL/React/Inertia demo scaffold for the WHS platform rebuild.

The current codebase is a **working prototype scaffold**, not a finished end-to-end WHS workflow app yet.

## Current Implementation

### Platform

- Laravel 13
- React + TypeScript via Inertia.js
- PostgreSQL as the active database target
- Local `database` queue and cache drivers for development
- Local filesystem driver for development uploads and generated files
- Laravel Fortify auth, profile, password reset, email verification, and 2FA starter flows

### Custom Demo Work Added

- Multi-tenant-oriented schema migrations for:
  - `tenants`
  - `businesses`
  - `workplaces`
  - `industries`
  - `occupations`
  - `roles`
  - `user_roles`
  - `user_occupations`
  - `workplace_user_assignments`
  - `task_packs`
  - `task_pack_occupations`
  - `task_pack_industries`
  - `activities`
  - `submissions`
  - `file_uploads`
  - `generated_documents`
- A preview navigation flow at `/preview`
- Static placeholder pages for planned WHS modules at `/preview/{slug}`
- Updated dashboard messaging to describe the intended platform direction

## What Is Not Built Yet

These areas are still pending:

- Eloquent models for the new domain tables beyond `User`
- Real admin onboarding flow
- Real worker task flow
- SWMS / SOP data tables and content seeders
- Submission scoring, corrective actions, and audit services
- PDF generation jobs
- PWA/offline support
- Redis-backed runtime
- S3-compatible runtime storage

## Routes

Current notable routes:

- `/` -> starter Laravel welcome page
- `/preview` -> WHS module preview home
- `/preview/{slug}` -> static placeholder module page
- `/dashboard` -> authenticated starter dashboard

## Local Run

### Assumptions

- PostgreSQL 14 is running on `127.0.0.1:5432`
- The app is linked in Laravel Herd as `opsfortress-demo.test`
- PHP / Composer are provided by Herd
- Bun is installed

### Environment

Current local defaults in `.env`:

- `DB_CONNECTION=pgsql`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `FILESYSTEM_DISK=local`

### Start The App

```powershell
# Single command that starts Vite + queue listener concurrently
composer run dev
```

Open:

- `http://opsfortress-demo.test`

### Default Seed User

- Email: `test@example.com`
- Password: `password`

## Verification Status

Verified on the current codebase:

- `bun run types:check` passed
- `bun run build` passed
- `php artisan test` passed (`40` tests)
- PostgreSQL connectivity works with the configured app user

## Current Limitations

- The current WHS UI is a static preview, not a data-backed workflow.
- The schema is ahead of the application layer; migrations exist, but most domain models, services, controllers, and pages do not.
- `composer run dev` assumes Herd is serving the site; it does not start `php artisan serve`.
- Some preview text still contains encoding artifacts and should be cleaned up.

## Review Notes

The latest review surfaced a few concrete issues to fix before building deeper features:

1. Nullable `business_id` columns are part of unique constraints in assignment tables, which allows duplicates for platform-scoped rows in PostgreSQL.
2. Public registration is still enabled, but registration creates unscoped users with no tenant or business assignment.
3. The preview pages contain visible mojibake characters copied from source material.

See [MILESTONE.md](C:/Users/User/Desktop/WHSAPP/opsfortress-demo/MILESTONE.md) for execution status and next steps.
