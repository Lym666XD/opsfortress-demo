# DBML Final Review — OpsFortress MVP ERD v0.3

> Date: 2026-05-17  
> Source reviewed: `OpsFortress_MVP_ERD_v0_3_Updated.txt`  
> Purpose: final schema review before asking Codex / Claude Code to generate migrations.

## 1. Result

The uploaded DBML confirms that the v0.3 schema direction is valid and materially different from the old demo schema.

2026-05-18 update: the first Laravel/PostgreSQL migration pass based on this review has been generated and verified locally. The recommended implementation add-ons in section 4 were included, and `V03SchemaContractTest` now checks the migrated PostgreSQL schema.

The DBML defines 31 tables across four groups:

1. Core Platform / Onboarding P0
2. WHS Content / Menu P0
3. Runtime / Evidence P0
4. P1 Expansion

The DBML is suitable as the base authority for migration generation, with a small number of recommended implementation add-ons listed below.

## 2. Tables Confirmed in DBML

### 2.1 Core Platform / Onboarding P0

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
```

This confirms the shift away from the old `tenants` / `businesses` model.

Important confirmations:

- `customer_accounts` is the account / tenant equivalent.
- `business_entities` are legal entities.
- `account_businesses` supports account-to-legal-entity relationships.
- `business_identifiers` replaces direct ABN-only modelling.
- `contractor_relationships` is P0.
- `user_business_access` is P0.

### 2.2 WHS Content / Menu P0

```text
tasks
swms_versions
swms_activity_steps
prestart_questions
occupations
industries
task_occupation_access
task_industry_access
```

This confirms that the old `task_packs` model should be superseded.

### 2.3 Runtime / Evidence P0

```text
worker_task_sessions
swms_step_events
prestart_responses
signatures
evidence_files
audit_events
```

This confirms the move away from generic `activities` / `submissions` as the only runtime layer.

### 2.4 P1 Expansion

```text
posttask_questions
posttask_responses
training_questions
training_attempts
training_responses
```

This confirms post-task and training are separate P1 expansion tables in the DBML.

## 3. DBML Strengths

The DBML is strong in these areas:

1. It separates account, business entity, identifier, workplace, and user access.
2. It supports multi-country business identifiers.
3. It treats contractor relationships as first-class P0 data.
4. It separates WHS content from runtime evidence.
5. It separates occupation access from industry access.
6. It models SWMS formal content and worker activity steps separately.
7. It keeps post-task and training in a clearly labelled P1 group.

## 4. Recommended Add-ons Before Migration Generation

The DBML is a visual ERD and not PostgreSQL DDL. It should be implemented with a few additions that are necessary for a production-quality Laravel/PostgreSQL system.

### 4.1 Add Import Tracking Tables

Not currently in DBML, but strongly recommended for P0:

```text
import_batches
import_source_files
import_validation_results
```

Reason: importer is a critical path for Kevin's workbook pipeline and should track file hash, source workbook, validation errors, sheet names, row numbers, and import status.

### 4.2 Add `prestart_submissions`

The DBML includes `prestart_responses` but not a parent summary table.

Recommended add:

```text
prestart_submissions
```

Reason: dashboards, scoring, critical-fail status, and daily deduplication are easier and cleaner with a parent submission record.

`prestart_responses` should reference `prestart_submissions` as well as or instead of `worker_task_sessions`, depending on final implementation preference.

### 4.3 Add `workplace_task_settings`

Recommended add:

```text
workplace_task_settings
```

Purpose: store per-workplace/per-business task configuration:

- active SWMS version;
- pre-start frequency;
- post-task frequency;
- training refresh interval;
- minimum read seconds;
- configured by / configured at.

Reason: these are configuration settings, not global task attributes.

### 4.4 Add Hash-Chain Fields to `audit_events`

Current DBML `audit_events` is minimal:

```text
event_type
event_payload
event_at
```

Recommended add:

```text
subject_type
subject_id
anchor
previous_hash
event_hash
hash_algorithm
hash_sequence
```

Reason: current repo already has a good AuditService pattern and Kevin's evidence requirement needs hash-chain support.

### 4.5 Add `alerts`

Recommended add:

```text
alerts
```

Reason: meeting notes confirm worker -> supervisor -> manager escalation logic for critical failures, training failures, missing evidence, and unresolved issues.

### 4.6 Add Training Completion Cache

The DBML includes training attempts and responses, but not a completion/expiry cache.

Recommended add:

```text
worker_training_completions
```

Reason: training is periodic and needs `completed_at` + `expires_at` to know when the worker must retake training.

### 4.7 Consider `user_workplace_access`

The DBML includes `user_business_access`, but supervisor/worker scoping may also require workplace-level access.

Options:

1. add `user_workplace_access`; or
2. implement this through a strengthened `workplace_user_assignments` table.

Recommended: add `user_workplace_access` if the team wants explicit access control separate from assignment history.

### 4.8 Add Timestamps / Soft Deletes Consistently

Several DBML tables omit timestamps. Laravel migrations should add them where operationally useful.

Suggested:

- add `timestamps()` to most mutable/configuration tables;
- add `softDeletes()` to business entities, workplaces, access records, content versions, and configuration records where deletion should be reversible;
- do not soft-delete append-only evidence/audit rows unless there is a strong legal reason.

## 5. Migration Implementation Notes

### UUIDs

Use UUID primary keys for v0.3 tables.

Recommended PostgreSQL default:

```php
$table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
```

Add a migration enabling `pgcrypto`:

```php
DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');
```

### JSONB

Use `jsonb` for:

- `swms_versions.full_swms_content`
- `audit_events.event_payload`
- importer metadata
- evidence metadata
- configuration metadata where needed

### Unique Constraints

Recommended unique indexes:

```text
business_identifier_types(country_id, identifier_code)
business_identifiers(identifier_type_id, normalised_identifier_value)
account_businesses(account_id, business_entity_id)
business_industries(business_entity_id, industry_id)
user_business_access(user_id, business_entity_id)
task_occupation_access(task_id, occupation_id)
task_industry_access(task_id, industry_id)
swms_versions(task_id, external_swms_version_id)
swms_activity_steps(swms_version_id, step_number)
prestart_questions(task_id, question_number)
```

Use partial unique indexes if nullable columns are part of uniqueness rules.

### Foreign Key Order

Generate migrations in this broad order:

1. platform lookup tables;
2. customer accounts and business entities;
3. users and access;
4. workplaces and contractor relationships;
5. occupations, industries, tasks;
6. SWMS content;
7. importer tracking;
8. runtime/evidence/audit;
9. P1 post-task/training.

## 6. Go / No-Go Assessment

### Ready to proceed

The schema is ready for first-pass migration generation if Codex / Claude Code is instructed to:

- use the DBML as base authority;
- include the implementation add-ons above;
- avoid extending the old demo schema;
- generate migrations only in the first pass.

### Not ready for production yet

The codebase is not ready for worker workflow implementation until the v0.3 migrations are generated, reviewed, migrated fresh, and tested.

## 7. Final Recommendation

Proceed with a migration-only implementation task first.

Do not ask Codex / Claude Code to build controllers, UI, importer services, or worker flow in the same first task.

Recommended first instruction:

```text
Read docs/CODEX_PROMPT_V0_3_MIGRATIONS.md and docs/DBML_FINAL_REVIEW_2026_05_17.md. Generate only the fresh v0.3 Laravel migration files. Do not generate models, controllers, seeders, services, or UI files yet.
```
> Status: SUPERSEDED 2026-05-23.
> The add-ons in section 4 have been implemented. The DBML in this folder
> was regenerated against the live migrations. Use that file plus the
> migration files as authority going forward.
