# Codex Prompt — Generate OpsFortress MVP v0.3 Laravel Migrations

Use this prompt when asking Codex / Claude Code / another coding agent to generate the fresh v0.3 migration set.

---

## Prompt

Read the v0.3 design documents carefully before writing code:

- `Originals/OpsFortress_MVP_ERD_v0_3_Updated.dbml` — authoritative schema if available as text
- `Originals/OpsFortress_MVP_ERD_v0_3_Readable.pdf` — visual ERD
- `Originals/OpsFortress_MVP_Database_Spec_v0_3_Clean.xlsx` — P0/P1 scope and table meaning
- `Originals/OpsFortress_MVP_Column_Level_Mapping_v0_3_Clean.xlsx` — source column to DB column mapping
- `Originals/OpsFortress_MVP_Importer_Source_File_Index_for_Yiming_v0_1_Clean.xlsx` — importer allow-list and source workbook scope
- `WHS_Architecture_Record.md` and `WHS架构分析记录.md` — architecture decisions and meeting notes
- `Role_Architecture_Notes.md` — permission role vs person identity type
- existing `opsfortress-demo` migrations and models only for Laravel style, not for schema authority

The new v0.3 schema supersedes the old demo migrations. Do not extend the old schema as production truth.

Old demo tables such as `tenants`, `businesses`, `task_packs`, `activities`, and `submissions` were useful scaffold tables but are not the final v0.3 model.

Generate a complete set of fresh Laravel migrations for the v0.3 schema.

---

## Core Requirements

- Use PostgreSQL.
- Use UUID primary keys throughout v0.3 domain tables.
- Prefer database-generated UUIDs using `pgcrypto` and `gen_random_uuid()`.
- Use Laravel `Blueprint` methods.
- Use `jsonb` for PostgreSQL JSON fields where appropriate.
- Use explicit foreign keys.
- Use indexes for common query paths.
- Use `timestamps()` and `softDeletes()` where appropriate.
- Keep P1 migrations clearly separate from P0 migrations.
- Do not generate models, controllers, seeders, pages, or services in this step. Migrations only.

---

## Migration Grouping

Create migrations in dependency order:

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

Use actual Laravel timestamped migration filenames.

---

## P0 Core Platform Tables

Create these tables or the nearest equivalent from the DBML/spec:

```text
customer_accounts
countries
business_identifier_types
business_entities
account_businesses
business_identifiers
workplaces
workplace_environments
business_industries
users adjustments if needed
user_business_access
user_workplace_access or strengthened workplace_user_assignments
contractor_relationships
```

Key modelling rules:

1. `customer_accounts` is the top-level paying account / tenant concept.
2. `business_entities` are legal entities, not tenants.
3. `account_businesses` links customer accounts to one or more business entities.
4. Do not store ABN directly as the only business identifier on the main business table.
5. Use `business_identifiers` with country-specific `business_identifier_types`.
6. `contractor_relationships` is P0 and must model host business entity to contractor business entity access.
7. `user_business_access` is P0 and controls per-user business visibility / permission boundaries.
8. Add workplace-level access if needed for supervisor/worker scoping.

Recommended `business_identifiers` uniqueness:

```text
unique(identifier_type_id, normalised_identifier_value)
```

Allow nulls where onboarding may be incomplete, but avoid nullable unique traps in PostgreSQL. Use partial indexes when necessary.

---

## P0 WHS Content Tables

Create:

```text
tasks
occupations
industries
task_occupation_access
task_industry_access
swms_versions
swms_activity_steps
prestart_questions
```

Key modelling rules:

1. `tasks` is canonical task master data.
2. `swms_versions` stores full formal SWMS content as `jsonb`, usually from `WHSAPP_SWMS_Data`.
3. Do not over-normalise all wide SWMS formal columns into fragile relational columns.
4. `swms_activity_steps` stores the worker-facing 10-step view, usually from `WHSAPP_Worker_App_View_Map`.
5. Preserve source traceability: source file, source sheet, source row, external IDs, task IDs, version label, source hash where useful.
6. `prestart_questions` stores the 15-question pre-start checklist.
7. Occupation and industry access are separate; do not merge them into one generic tag table unless the DBML explicitly requires it.

---

## P0 Workplace Task Settings

Add `workplace_task_settings` unless DBML already has an equivalent.

Purpose: per-workplace/per-business task behaviour.

Suggested fields:

```text
id
customer_account_id / account_id
business_entity_id
workplace_id
task_id
active_swms_version_id
prestart_frequency: daily | as_needed | off
posttask_frequency: daily | as_needed | off
training_refresh_interval
minimum_read_seconds
configured_by_user_id
configured_at
timestamps
softDeletes
```

Do not place these settings only on global `tasks`, because the same task can be used by multiple businesses/workplaces with different rules.

---

## P0 Import Tracking Tables

Create:

```text
import_batches
import_source_files
import_validation_results
```

These are required because the importer is a critical P0 path.

Suggested concepts:

- `import_batches`: one import run.
- `import_source_files`: files/workbooks included in a batch, with file hash and source metadata.
- `import_validation_results`: rule failures/warnings by sheet, row, column, severity.

The importer must support staged import:

1. countries / business identifier types;
2. tasks / occupations / industries / access maps;
3. SWMS workbook content;
4. worker view / pre-start / post-task / training where in scope.

---

## P0 Runtime / Evidence Tables

Create:

```text
worker_task_sessions
swms_step_events
signatures
prestart_submissions
prestart_responses
evidence_files
audit_events
alerts
```

Key modelling rules:

1. Worker runtime should be explicit, not only generic `payload` JSON.
2. Use `worker_task_sessions` as the parent execution record.
3. Use `swms_step_events` to prove the worker viewed each step and met minimum read time.
4. Use `signatures` for SWMS acknowledgement / closeout / manager review signatures.
5. Use `prestart_submissions` as a parent summary record.
6. Use `prestart_responses` for individual question responses.
7. Use `evidence_files` for photos, PDFs, signatures, and attachments.
8. Use `alerts` for critical fail / training fail / missing evidence / escalation workflows.
9. Runtime/evidence records should be append-only. Add `supersedes_id` where corrections may be needed.

---

## Audit Hash-Chain

Create or update `audit_events` to support tamper-evident audit trails.

Required concepts:

```text
id
customer_account_id / account_id
business_entity_id nullable
workplace_id nullable
user_id nullable
subject_type
subject_id
event_type / event_name
anchor
previous_hash
event_hash
hash_algorithm
hash_sequence
event_payload jsonb
occurred_at
created_at
```

Important:

- The current repo's `AuditService` implementation is a good reference.
- Keep hash-chain fields clear and verifiable.
- Do not rely only on comments saying records are append-only.
- Consider later DB triggers for no UPDATE / DELETE on submitted evidence/audit tables.

---

## P1 Post-Task Tables

Create separate migration file for:

```text
posttask_questions
posttask_submissions
posttask_responses
```

Post-task is important, but it should be clearly labelled P1 unless the current MVP scope decides otherwise.

---

## P1 Training Tables

Create separate migration file for:

```text
training_questions
training_attempts
training_responses
worker_training_completions
```

Training is periodic, not every task execution.

`worker_training_completions` should support:

```text
completed_at
expires_at
score_percent
status
critical_fail flag / count
```

---

## Role and Identity Rules

Separate permission role from person identity type.

Permission role examples:

```text
worker
supervisor
manager
admin
platform_admin
```

Person identity type examples:

```text
employee
labour_hire
contractor
visitor
regulator
supplier
other
```

Do not model detailed contractor/employee taxonomy as permission roles.

---

## What Not To Do

Do not:

- extend old `task_packs` as the final task model;
- keep ABN as a direct business-only field in the final model;
- rely on generic `submissions.payload` for scoring and dashboard queries;
- build one table per workbook tab without abstraction;
- mix OpsFortress platform data and WHSAPP-specific workflow data without boundaries;
- bury business rules inside React pages;
- generate models/controllers in this migration-only task.

---

## Deliverables

Return:

1. All migration files.
2. A short migration order explanation.
3. Any assumptions made where DBML/spec was ambiguous.
4. Any questions that must be confirmed with Kevin before implementation.

No models, controllers, seeders, or UI files in this task.