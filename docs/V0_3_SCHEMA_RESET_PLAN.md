# OpsFortress v0.3 Schema Reset Plan

> Status: architecture decision record / implementation planning note  
> Date: 2026-05-17  
> Scope: OpsFortress platform + WHSAPP MVP database reset

## 1. Decision Summary

The current `opsfortress-demo` repository is a useful Laravel technical scaffold, but its original database model is no longer the authoritative product schema.

The v0.3 database design supersedes the existing demo migrations based on:

- `tenants`
- `businesses`
- `task_packs`
- `activities`
- `submissions`
- `file_uploads`
- `generated_documents`

The next production-oriented step should be a fresh v0.3 migration set using the latest ERD / Database Spec / Column Mapping / Importer Source Index as the authority.

Recommended implementation direction:

```text
v0.3 Schema Reset + Importer-first P0
```

This repository should keep its useful Laravel infrastructure, but the database schema and business entities should be rebuilt around v0.3.

---

## 2. Why a Reset Is Needed

The existing demo schema was built for an early scaffold. It proved that Laravel, PostgreSQL, Inertia, tenancy, seed data, and a basic admin slice can work.

However, it does not fully represent Kevin's current business model:

- OpsFortress is the underlying software engine / platform.
- WHSAPP is one branded WHS application running on that engine.
- Customer accounts, legal business entities, identifiers, workplaces, contractor relationships, users, content, runtime evidence, and audit records must be separated cleanly.
- The importer is not optional; it is the bridge between Kevin's workbook production pipeline and PostgreSQL.

The current model risks becoming expensive technical debt if new workflows continue to be built on `tenants/businesses/task_packs/submissions`.

---

## 3. Two-Layer Business Architecture

### OpsFortress

OpsFortress is the reusable core platform. It should own:

- customer accounts;
- legal business entities;
- account-to-business relationships;
- business identifiers by country;
- workplaces;
- users and access boundaries;
- occupations and industries;
- contractor relationships;
- assets, suppliers, chemicals, and future registers;
- importer tracking;
- audit, evidence, and file storage;
- cross-application platform services.

### WHSAPP

WHSAPP is the WHS-specific product layer. It should own or use:

- SWMS;
- SOPs;
- worker app view;
- pre-start;
- post-task;
- training;
- signatures;
- photo evidence;
- WHS reports;
- PDFs;
- dashboards;
- alerts;
- compliance workflows.

Important business implication: WHSAPP should be sellable or brandable as a product, while OpsFortress remains the reusable software engine and licence layer.

---

## 4. Authoritative v0.3 Table Direction

### 4.1 Core Platform / Onboarding P0

Recommended tables:

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
users
user_business_access
user_workplace_access or workplace_user_assignments
contractor_relationships
```

Key changes from the old demo:

- `customer_accounts` replaces the old `tenants` concept.
- `business_entities` replaces the old single `businesses` table.
- `account_businesses` allows one customer account to control multiple legal business entities.
- `business_identifiers` replaces direct `abn` columns and supports ABN / ACN / NZBN / EIN / other country-specific identifiers.
- `contractor_relationships` becomes first-class P0 data rather than being inferred only from mismatched user/business assignments.
- `user_business_access` is required for parent / child / sibling business visibility.

### 4.2 WHS Content P0

Recommended tables:

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

Key changes from the old demo:

- `task_packs` should not remain the long-term content root.
- `tasks` should represent canonical task master data.
- `swms_versions` should store the full formal SWMS content as `jsonb`.
- `swms_activity_steps` should store the worker-facing 10-step view from `WHSAPP_Worker_App_View_Map`.
- `prestart_questions` should store the 15-question pre-start checklist.

### 4.3 Runtime / Evidence P0

Recommended tables:

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

Notes:

- `prestart_submissions` is recommended as a parent summary table over raw responses.
- Worker runtime should not rely on one generic `submissions.payload` JSON blob for all business logic.
- Runtime records must be append-only. Corrections should be new rows using `supersedes_id` or equivalent.
- Audit events must support hash-chain verification.

### 4.4 P1 Tables

P1 tables should be separated clearly in migration files:

```text
posttask_questions
posttask_submissions
posttask_responses
training_questions
training_attempts
training_responses
worker_training_completions
```

Reasoning:

- Post-task is important but can be staged after the first SWMS + pre-start proof.
- Training is periodic, not every task execution. It requires `worker_training_completions.completed_at` and `expires_at`.

### 4.5 Importer Tables

Importer tracking is required early:

```text
import_batches
import_source_files
import_validation_results
```

Why:

- Kevin's content pipeline may involve hundreds to thousands of workbooks.
- Import failures must be traceable by source file, sheet, row, column, rule, and severity.
- The importer should be able to validate source workbook schema before committing records.

---

## 5. Important Design Corrections

### 5.1 Do Not Put Per-Workplace Task Settings on Global Tasks

Pre-start frequency, post-task frequency, training refresh interval, and minimum read time should be configurable per workplace/business/task, not globally on `tasks`.

Recommended table:

```text
workplace_task_settings
- account_id
- business_entity_id
- workplace_id
- task_id
- active_swms_version_id
- prestart_frequency: daily | as_needed | off
- posttask_frequency: daily | as_needed | off
- training_refresh_interval
- minimum_read_seconds
- configured_by_user_id
- configured_at
```

### 5.2 Separate Permission Role from Person Identity Type

Do not mix these two concepts:

```text
Permission role: worker | supervisor | manager | admin | platform_admin
Person identity type: employee | labour_hire | contractor | visitor | regulator | supplier | other
```

`person_type` and `contractor_type` can live on the user/profile layer, but access permissions should be controlled by access tables and policies.

### 5.3 Formalise Contractor Relationships

Do not rely only on `user.business_id != assignment.business_id` to infer contractor access.

Use `contractor_relationships` to capture:

- host business entity;
- contractor business entity;
- relationship status;
- start/end dates;
- access scope;
- insurance/compliance metadata later.

### 5.4 Preserve Full SWMS Content and Worker View Separately

Use two layers:

1. `swms_versions.full_swms_content jsonb` for the complete formal SWMS/PDF source.
2. `swms_activity_steps` for worker-facing mobile app steps.

Do not over-normalise the formal SWMS wide source into dozens of fragile columns.

### 5.5 Keep Runtime Data Append-Only

Submitted runtime records, evidence, signatures, and audit events should not be edited or deleted. Corrections should be new records linked to prior records.

---

## 6. Migration Grouping Proposal

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

Use PostgreSQL `pgcrypto` / `gen_random_uuid()` for database-generated UUIDs where possible.

---

## 7. What to Preserve from the Current Repo

Preserve and port these ideas:

- domain folder organisation;
- `TenantContext` / scoped context pattern, adapted to `customer_account_id` / `account_id` naming;
- tenant/account isolation tests;
- `AuditService` canonical JSON + SHA-256 + `previous_hash` + `lockForUpdate()`;
- FormRequest validation style;
- Laravel Policy style;
- Inertia vertical slice delivery;
- partial unique index approach;
- public registration disabled by default.

---

## 8. What to Avoid

Avoid:

- extending `task_packs` as the long-term task content model;
- putting ABN directly on the main business table;
- treating AppSheet app names as Laravel modules;
- creating one table per spreadsheet tab without abstraction;
- relying on one generic `payload` JSON field for scoring and dashboards;
- putting business rules inside React screens;
- continuing UI work before schema/importer proof is aligned.

---

## 9. First P0 Proof Flow

The first meaningful P0 proof should be:

```text
Import approved workbook tabs
  -> create tasks / swms_versions / swms_activity_steps / prestart_questions
  -> assign task visibility via occupation / industry
  -> worker opens mobile worker view
  -> worker reads SWMS steps with minimum read time
  -> worker signs SWMS acknowledgement
  -> signature writes signatures + audit_events
  -> worker completes pre-start questions
  -> responses write prestart_submissions + prestart_responses
  -> critical fail triggers alert
  -> manager/supervisor can see evidence and status
```

This proves architecture, importer, runtime, evidence, and audit together.

---

## 10. Open Questions

Still to confirm with Kevin / Damon:

1. Exact P0/P1 boundary for post-task and training in the first working demo.
2. Whether `workplace_task_settings` should be P0 or early P1. Recommended: P0.
3. Exact minimum read time default: 3, 5, 10, or 15 seconds.
4. Alert escalation default timeout: suggested 2 hours unless Kevin specifies otherwise.
5. Whether GPS geofencing is P0, or whether QR/manual workplace check-in is enough for P0.
6. Whether globalisation/jurisdiction replacement belongs in P0 as schema only, not UI.
7. Whether the `.dbml` file can be converted to `.txt` so it can be read directly as authoritative text.

---

## 11. Final Position

The repository should move forward by resetting the schema to v0.3 and proving the importer-backed worker flow. The existing codebase remains valuable as a Laravel implementation foundation, but the old table model should not be treated as production truth.