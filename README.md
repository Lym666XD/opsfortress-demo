# OpsFortress Demo

## Overview

This repository contains the Laravel / PostgreSQL / React / Inertia technical scaffold for the OpsFortress / WHSAPP rebuild.

The current codebase is a **working prototype scaffold**, not the final OpsFortress MVP schema and not a finished WHS workflow app.

As of the 2026-05-17 architecture review, the v0.3 database documents are treated as the authoritative schema direction. The older demo tables in this repository are useful as implementation reference, but they should not be extended as the long-term production data model.

## 2026-05-17 Architecture Decision

The project now has a clearer two-layer business and technical structure:

1. **OpsFortress** — the underlying platform / software engine / database layer. It owns reusable platform data such as customer accounts, legal business entities, workplaces, users, occupations, industries, assets, suppliers, contractors, business identifiers, evidence, audit records, and import pipelines.
2. **WHSAPP** — the customer-facing WHS product / branded application layer. It uses OpsFortress to deliver SWMS, SOP, worker task execution, pre-start, post-task, training, signatures, evidence, PDFs, reporting, and dashboards.

The business intent is that WHSAPP may be one product running on top of OpsFortress, while future apps may reuse the same core platform. If WHSAPP is ever sold separately, OpsFortress remains the reusable platform asset and licence layer.

## Current Implementation

### Platform

- Laravel 13
- React + TypeScript via Inertia.js
- PostgreSQL as the active database target
- Local `database` queue and cache drivers for development
- Local filesystem driver for development uploads and generated files
- Laravel Fortify auth, profile, password reset, email verification, and 2FA starter flows

### Useful Technical Foundations Already Built

These pieces are worth preserving and porting into the v0.3 reset:

- Domain-oriented folder direction under `app/Domain/...`
- `TenantContext`, `TenantScope`, and `BelongsToTenant` ideas for request/job scoped isolation
- Tenant isolation tests and policy-driven access checks
- Hash-chained `AuditService` using SHA-256, canonical JSON, `previous_hash`, and row locking
- Append-only / immutable payload hardening ideas
- Public registration disabled in favour of controlled onboarding / invite flows
- FormRequest + Policy + Controller + Inertia vertical slice pattern
- PostgreSQL partial unique index experience

## Legacy Demo Schema Warning

The original demo migrations created tables such as:

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

These were appropriate for an early scaffold, but they are now superseded by the v0.3 database design. In particular:

- `tenants` becomes conceptually closer to `customer_accounts`.
- `businesses` should be replaced by `business_entities` plus `account_businesses`.
- direct `abn` storage should be replaced by `business_identifiers` with country-specific identifier types.
- `task_packs` should be replaced by `tasks`, `swms_versions`, `swms_activity_steps`, and question tables.
- generic `activities` / `submissions` should be replaced or narrowed into explicit runtime/evidence tables such as `worker_task_sessions`, `swms_step_events`, `signatures`, `prestart_submissions`, `prestart_responses`, `evidence_files`, and `audit_events`.

Do not build major new production workflows on top of the legacy schema without checking the v0.3 reset plan.

## Authoritative Design Sources

The current authoritative design sources are in the Google Drive WHSAPPDOCS folder and related architecture notes:

- `OpsFortress_MVP_ERD_v0_3_Updated.dbml` — authoritative schema when available as text
- `OpsFortress_MVP_ERD_v0_3_Readable.pdf` — visual ERD reference
- `OpsFortress_MVP_Database_Spec_v0_3_Clean.xlsx` — table purpose, P0/P1 scope, business meaning
- `OpsFortress_MVP_Column_Level_Mapping_v0_3_Clean.xlsx` — source column to DB column mapping
- `OpsFortress_MVP_Importer_Source_File_Index_for_Yiming_v0_1_Clean.xlsx` — importer allow-list
- `WHS_Architecture_Record.md` / `WHS架构分析记录.md` — architecture history and meeting decisions
- `Role_Architecture_Notes.md` — role vs person identity separation

## Current Recommended Direction

The next major technical step should be:

```text
v0.3 Schema Reset + Importer-first P0
```

This means:

1. Generate fresh Laravel migrations for the v0.3 schema.
2. Preserve useful infrastructure from this repo, especially tenancy, audit, policies, and vertical-slice style.
3. Stop extending the old `tenants/businesses/task_packs/submissions` model for production features.
4. Build the importer early, because the real business asset is Kevin's workbook/data pipeline.
5. Prove the first worker flow from imported workbook data, not from hand-written demo seed data.

## P0 Proof Target

A useful P0 should prove more than “a worker can view a SWMS”. It should prove:

- database structure supports customer account → legal business entity → workplace → user access;
- country-specific business identifiers work;
- contractor relationships can be modelled;
- importer can load approved workbook tabs into canonical v0.3 tables;
- worker can see the correct task/SWMS based on occupation, industry, workplace, and access rules;
- SWMS worker-view steps are rendered from `swms_activity_steps`;
- worker actions create runtime/evidence/audit records;
- signatures and critical events are hash-chain auditable;
- PDFs and dashboards can be built from stored evidence later.

## Routes

Current notable routes in the scaffold:

- `/` -> starter Laravel welcome page
- `/preview` -> WHS module preview home
- `/preview/{slug}` -> static placeholder module page
- `/dashboard` -> authenticated starter dashboard
- `/admin/workplaces` -> early admin workplace vertical slice

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
$env:PATH="C:\Users\User\.config\herd\bin;C:\Users\User\.bun\bin;" + $env:PATH
Set-Location "C:\Users\User\Desktop\WHSAPP\opsfortress-demo"
composer run dev
```

Open:

- `http://opsfortress-demo.test`

## Verification

See `MILESTONE.md` for the latest local verification status. Previous verification included successful frontend type-check/build, database migration, seed data, and feature tests.

## Related Docs Added 2026-05-17

- `docs/V0_3_SCHEMA_RESET_PLAN.md`
- `docs/TECHNICAL_REVIEW_OPSFORTRESS_DEMO_2026_05_17.md`
- `docs/MEETING_NOTES_2026_05_17_WHSAPP_OPSFORTRESS.md`
- `docs/CODEX_PROMPT_V0_3_MIGRATIONS.md`
- `docs/GOOGLE_DRIVE_MARKDOWN_UPDATE_GUIDE_2026_05_17.md`

## Final Note

The current repository is valuable because it proves the Laravel stack and several hardening patterns. Its main weakness is schema drift. Treat it as a foundation to refactor forward into v0.3, not as the final database model.