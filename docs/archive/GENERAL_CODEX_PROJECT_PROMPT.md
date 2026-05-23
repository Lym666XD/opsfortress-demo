# General Codex / Claude Code Project Prompt — OpsFortress / WHSAPP

Use this prompt when asking a local coding agent to help continue the project. Adjust the exact task at the end depending on the current phase.

---

## Project Context

You are working in the `opsfortress-demo` repository.

This repository is a Laravel / PostgreSQL / React / TypeScript / Inertia technical scaffold for the OpsFortress / WHSAPP rebuild.

Important business architecture:

- **OpsFortress** is the reusable platform / software engine / shared database layer.
- **WHSAPP** is the WHS-specific customer-facing application layer built on top of OpsFortress.
- WHSAPP may be one product or brand running on the OpsFortress platform. Future applications may reuse the same platform.
- The project should be treated as a multi-tenant SaaS platform + WHS workflow engine + importer-driven content pipeline + evidence/audit system.

Important technical decision:

- The old demo schema is not the final production schema.
- The v0.3 database documents are now authoritative.
- Do not continue extending old scaffold tables such as `tenants`, `businesses`, `task_packs`, `activities`, and `submissions` as production truth.
- Use them only as implementation-style references where useful.

---

## Required Reading Before Coding

Read these repo docs first:

```text
README.md
TARGET_ARCHITECTURE.md
docs/V0_3_SCHEMA_RESET_PLAN.md
docs/TECHNICAL_REVIEW_OPSFORTRESS_DEMO_2026_05_17.md
docs/DBML_FINAL_REVIEW_2026_05_17.md
docs/CODEX_PROMPT_V0_3_MIGRATIONS.md
docs/MEETING_NOTES_2026_05_17_WHSAPP_OPSFORTRESS.md
```

The DBML final review summarises the uploaded `OpsFortress_MVP_ERD_v0_3_Updated.txt` and the recommended implementation add-ons.

---

## Current Objective

Move the repository from an early scaffold toward the v0.3 schema and importer-first MVP.

The current recommended path is:

```text
v0.3 Schema Reset + Importer-first P0
```

P0 should prove architecture, importer, runtime, evidence, and audit — not just a static UI.

A meaningful P0 proof should eventually show:

```text
import approved workbook tabs
  -> create tasks / swms_versions / swms_activity_steps / prestart_questions
  -> assign visibility by occupation / industry / business / workplace
  -> worker opens imported SWMS worker view
  -> worker reads each step with minimum read-time tracking
  -> worker signs SWMS acknowledgement
  -> worker completes pre-start questions
  -> responses, signature, evidence, alerts, and audit events are stored
```

---

## Engineering Principles

Follow these principles:

1. **Migration-first for schema reset**  
   Generate and verify v0.3 migrations before building controllers, UI, services, or importer logic.

2. **Do not build on obsolete schema**  
   Do not add new production workflow features to `task_packs`, `activities`, or `submissions` unless explicitly marked as temporary compatibility work.

3. **Preserve useful infrastructure**  
   Keep or port good existing patterns:
   - domain folders;
   - account/tenant context scoping;
   - policy + FormRequest + controller vertical slices;
   - audit hash-chain service;
   - tenant/account isolation tests;
   - partial unique index approach;
   - public registration disabled by default.

4. **Use explicit domain boundaries**  
   Keep OpsFortress platform entities separate from WHSAPP-specific workflow entities.

5. **Importer is core infrastructure**  
   Treat importer tracking and validation as P0 platform infrastructure, not a later utility script.

6. **Evidence must be legally credible**  
   Runtime, signatures, files, and audit events should be append-only where appropriate. Corrections should use `supersedes_id` or similar, not destructive edits.

7. **Avoid Australian-only assumptions**  
   Use countries and business identifier types. Do not hard-code ABN as the only business identifier.

8. **Separate role from identity**  
   Permission role answers what a user can do. Person identity type answers what kind of person/entity relationship they have.

---

## v0.3 Core Schema Direction

Use the v0.3 DBML/spec as the base authority.

Core P0 groups include:

```text
customer_accounts
business_entities
account_businesses
countries
business_identifier_types
business_identifiers
workplaces
workplace_environments
business_industries
users
user_business_access
contractor_relationships

tasks
swms_versions
swms_activity_steps
prestart_questions
occupations
industries
task_occupation_access
task_industry_access

worker_task_sessions
swms_step_events
prestart_responses
signatures
evidence_files
audit_events
```

Recommended implementation add-ons:

```text
import_batches
import_source_files
import_validation_results
prestart_submissions
workplace_task_settings
alerts
worker_training_completions
user_workplace_access or strengthened workplace_user_assignments
```

P1 groups include:

```text
posttask_questions
posttask_responses
training_questions
training_attempts
training_responses
```

---

## 2026-05-18 Status

The first two implementation tasks have been completed:

```text
Generate fresh Laravel migrations for the v0.3 schema.
Port backend infrastructure from the old scaffold naming to the v0.3 schema.
```

Local verification completed:

```text
php artisan migrate:fresh --seed
DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres php artisan test --filter=V03SchemaContractTest
DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres php artisan test --filter=V03DevSeederTest
./vendor/bin/pint --test
```

The schema contract test passed with 5 tests and 158 assertions. The dev seeder test passed with 1 test and 11 assertions.

Important caveat: some legacy model files still exist as reference code. Active routes, seeders, and v0.3 tests now use the account/business/workplace/task schema.

## Next Task Recommendation

For the next implementation task, do only this:

```text
Build the first importer service slice against the verified v0.3 backend.
```

Recommended scope:

```text
refactor users.id to UUID now
create v0.3 Eloquent models for core P0 tables
adapt TenantContext -> AccountContext / PlatformContext
adapt BelongsToTenant -> account/business/workplace scoping
port AuditService to v0.3 audit_events fields
create a minimal v0.3 dev seeder/login path
delete old tests that depend on tenants/businesses/task_packs/activities/submissions
```

Do not build:

```text
React pages
worker UI
PDF jobs
full importer UI
new production workflow controllers
```

Those should come after the backend infrastructure can read/write the v0.3 tables reliably.

---

## Migration Requirements

Use PostgreSQL and Laravel migrations.

Requirements:

- UUID primary keys for v0.3 domain tables.
- `users.id` is also UUID in this repo after the 2026-05-18 backend infrastructure pass.
- Prefer `pgcrypto` and `gen_random_uuid()` for database-generated UUIDs.
- Use explicit foreign keys.
- Use `jsonb` for structured JSON fields.
- Add sensible indexes and unique constraints.
- Use partial indexes where nullable uniqueness matters.
- Add timestamps consistently.
- Add soft deletes where deletion should be reversible.
- Keep P1 tables in separate migrations clearly labelled P1.
- Preserve migration dependency order.

Recommended migration grouping:

```text
000_enable_postgres_extensions
001_create_platform_lookup_tables
002_create_customer_account_and_business_tables
003_create_users_and_access_tables
004_create_contractor_relationships
005_create_whs_master_content_tables
006_create_swms_content_tables
007_create_import_tracking_tables
008_create_runtime_tables
009_create_evidence_audit_alert_tables
010_create_p1_posttask_tables
011_create_p1_training_tables
```

---

## Safety Rules

Before editing:

1. Check current branch.
2. Work on `refactor` or a feature branch, not directly on `main`.
3. Inspect existing migrations and docs before writing.
4. Keep changes small and reviewable.
5. After generating migrations, run formatting and tests where possible.

Do not delete historical docs unless explicitly instructed. Old docs contain useful context and should be preserved with dated addenda.

---

## Current Repo Status Addendum — 2026-05-18

The v0.3 migration and backend infrastructure pass is complete locally:

- `php artisan migrate:fresh --seed` passes against PostgreSQL.
- `V03SchemaContractTest` and `V03DevSeederTest` pass against PostgreSQL.
- `AccountContext` / `BelongsToAccount` is the active scoped-context pattern.
- `V03DemoSeeder` creates `admin@acme.test` / `password`.
- Frontend/admin UI development is paused; next work should be importer services and validation tests.

## Quality Bar

After a coding task, report:

1. Files created/modified.
2. Assumptions made.
3. Schema/design deviations from DBML, if any.
4. Commands run.
5. Test/migration results.
6. Any issues that need human confirmation.

---

## Previous Migration Command to Agent

The following prompt was used for the completed first pass and is now historical:

```text
Read README.md, TARGET_ARCHITECTURE.md, docs/V0_3_SCHEMA_RESET_PLAN.md, docs/DBML_FINAL_REVIEW_2026_05_17.md, and docs/CODEX_PROMPT_V0_3_MIGRATIONS.md.

Then generate only the fresh Laravel migration files for the v0.3 schema reset, on the current refactor branch.

Use the DBML/spec direction as authoritative, include the add-ons recommended in docs/DBML_FINAL_REVIEW_2026_05_17.md, and do not extend the old tenants/businesses/task_packs/activities/submissions schema as production truth.

Do not generate models, controllers, seeders, services, importer code, or UI files yet.

After generating migrations, explain the migration order, assumptions, and any places where DBML was extended for implementation quality.
```

## Suggested Immediate Command to Agent

Use this as the next task prompt:

```text
Read README.md, TARGET_ARCHITECTURE.md, MILESTONE.md, docs/V0_3_SCHEMA_RESET_PLAN.md, docs/DBML_FINAL_REVIEW_2026_05_17.md, and docs/CODEX_PROMPT_V0_3_MIGRATIONS.md.

The v0.3 migrations now exist and have passed `php artisan migrate:fresh` against PostgreSQL plus `V03SchemaContractTest`.

Do not build frontend pages or importer UI yet.

Port the backend infrastructure to v0.3:
- refactor `users.id` to UUID now and update all user foreign keys accordingly;
- create only the Eloquent models needed for the P0 core/platform/content/runtime tables;
- adapt tenant/account context naming and scoping to `customer_accounts`, `business_entities`, and `workplaces`;
- port the hash-chain AuditService to the new `audit_events` columns;
- create a minimal v0.3 dev seeder/login path;
- delete obsolete scaffold tests and add/update backend tests for account/business/workplace isolation and audit-chain behavior.

Avoid extending old production truth tables such as `tenants`, `businesses`, `task_packs`, `activities`, and `submissions`.
```
