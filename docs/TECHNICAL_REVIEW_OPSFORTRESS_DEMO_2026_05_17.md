# Technical Review — Current `opsfortress-demo`

> Date: 2026-05-17  
> Reviewer perspective: professional programmer / architecture audit  
> Purpose: identify what is useful, what is misaligned, and what should change before more production workflows are added.

## 1. Executive Summary

The current `opsfortress-demo` is technically useful but product-schema misaligned.

It has good Laravel foundations:

- Laravel / PostgreSQL / Inertia scaffold works.
- Domain folder direction is reasonable.
- Tenant context and global scope patterns are valuable.
- Audit hash-chain service is strong.
- Policy + FormRequest + Controller vertical slice pattern is good.
- Public registration hardening and partial unique indexes show good engineering discipline.

However, the repository's original database schema is no longer aligned with the v0.3 database documents. The main risk is not code quality. The main risk is **continuing to build production features on the old schema**.

Recommended action:

```text
Stop extending the old scaffold schema for production workflows.
Move to v0.3 schema reset and importer-first P0.
```

2026-05-18 status: the v0.3 migration-only reset has been generated and verified against local PostgreSQL. The old scaffold schema remains useful as code reference, but backend infrastructure now needs to be ported to the new account/business/workplace/task/runtime tables.

---

## 2. What Is Good and Should Be Preserved

### 2.1 Domain-Oriented Structure

The repository already started moving away from flat Laravel MVC toward `app/Domain/...`. This is correct for a project of this size.

Keep the general idea, but update domain names around v0.3 concepts.

Suggested direction:

```text
app/Domain/OpsFortress/Accounts
app/Domain/OpsFortress/BusinessEntities
app/Domain/OpsFortress/Workplaces
app/Domain/OpsFortress/People
app/Domain/OpsFortress/Access
app/Domain/OpsFortress/Contractors
app/Domain/OpsFortress/Industries
app/Domain/OpsFortress/Occupations
app/Domain/Whs/Tasks
app/Domain/Whs/Swms
app/Domain/Whs/Runtime
app/Domain/Whs/Evidence
app/Domain/Shared/Audit
app/Domain/Shared/Importer
app/Domain/Shared/Files
```

### 2.2 Tenant / Account Context Pattern

`TenantContext`, `TenantScope`, and `BelongsToTenant` are good patterns. They prevent forgetting `where tenant_id = ...` in controllers.

For v0.3, rename or adapt this around the new account model:

```text
TenantContext -> AccountContext or PlatformContext
tenant_id -> customer_account_id / account_id depending on canonical schema
```

Important: v0.3 also needs business/workplace-level scoping, not only account-level scoping.

### 2.3 Audit Hash-Chain

The current `AuditService` is one of the best parts of the repo:

- canonical JSON;
- SHA-256;
- previous hash;
- per-subject chain;
- `lockForUpdate()` for concurrent writes;
- tamper detection method.

Port this forward.

Recommended v0.3 field names:

```text
event_hash
previous_hash
hash_algorithm
hash_sequence
event_payload
occurred_at
```

### 2.4 Vertical Slice Delivery Style

The Add Workplace slice pattern is useful:

```text
route
controller
FormRequest
Policy
model
Inertia page
test
audit event
```

Keep this style. But future slices should target v0.3 tables.

### 2.5 Public Registration Disabled

Good decision. WHSAPP / OpsFortress onboarding must be controlled, account-scoped, and billing-aware. Public registration creating unscoped users is unsafe.

---

## 3. Major Problems

## 3.1 Schema Drift from v0.3

Current old scaffold tables:

```text
tenants
businesses
workplaces
industries
occupations
roles
user_roles
user_occupations
workplace_user_assignments
task_packs
task_pack_occupations
task_pack_industries
activities
submissions
file_uploads
generated_documents
```

v0.3 target direction:

```text
customer_accounts
business_entities
account_businesses
business_identifiers
contractor_relationships
user_business_access
tasks
swms_versions
swms_activity_steps
worker_task_sessions
swms_step_events
prestart_submissions
prestart_responses
signatures
evidence_files
audit_events
```

This is a structural mismatch. It should not be solved by renaming a few fields. It requires fresh migrations.

## 3.2 Business Identity Is Too ABN-Centric

Current model stores `abn` directly on `businesses`.

v0.3 requires a country-specific identifier model:

```text
countries
business_identifier_types
business_identifiers
```

This matters because Kevin wants multi-country support and because one legal entity may have different identifiers depending on jurisdiction.

Recommended replacement:

```text
business_entities
business_identifiers(identifier_type_id, identifier_value, normalised_identifier_value)
```

Use a unique index on:

```text
identifier_type_id + normalised_identifier_value
```

rather than `businesses.abn`.

## 3.3 Account vs Legal Entity Is Not Clear Enough

Current `tenant -> business` is too simple.

v0.3 needs:

```text
customer_account -> account_businesses -> business_entities
```

Why:

- one paying customer account can manage multiple legal entities;
- one holding company / group may operate several entities;
- contractor businesses must remain separate legal entities;
- WHSAPP may need account-level billing but entity-level legal documents.

## 3.4 Business-Level Access Is Not Solved

Tenant/account-level isolation is not enough.

A user may be:

- group admin over all businesses;
- manager for one business only;
- supervisor for one workplace only;
- contractor visible only for host-approved scope;
- worker visible only for assigned tasks.

v0.3 requires `user_business_access` as P0. It may also require `user_workplace_access` or a strengthened `workplace_user_assignments` table.

## 3.5 Contractor Model Is Too Implicit

Current approach infers host vs home business by comparing assignment business and user business. This is clever but not enough.

v0.3 should include explicit:

```text
contractor_relationships
```

This table should store the host-contractor relationship, status, scope, and dates.

## 3.6 Task Pack Model Is Outdated

Current `task_packs` worked for early scaffolding, but v0.3 needs:

```text
tasks
swms_versions
swms_activity_steps
prestart_questions
posttask_questions
training_questions
```

The workbook importer should target canonical v0.3 tables, not old workbook table names or old Laravel table names.

## 3.7 Runtime Is Too Generic

Current `activities` and `submissions` are generic payload-driven tables.

WHS compliance needs explicit event and evidence tables:

```text
worker_task_sessions
swms_step_events
signatures
prestart_submissions
prestart_responses
posttask_submissions
posttask_responses
training_attempts
training_responses
evidence_files
audit_events
```

Reason:

- legal evidence must be queryable;
- dashboard cannot rely on arbitrary payload JSON;
- order of operations matters;
- minimum reading time matters;
- digital signatures and critical failures must be auditable.

## 3.8 Append-Only Is Not Enforced Strongly Enough

Current comments say audit records are append-only by convention.

For WHS legal evidence, convention is not enough.

Recommended enforcement:

- model-level update/delete prevention for submitted runtime rows;
- database trigger preventing update/delete for key evidence/audit tables;
- corrections via `supersedes_id` only.

## 3.9 Importer Is Missing

The importer is not optional. It is the bridge between Kevin's content production and the app.

P0 should include importer tracking tables and a minimal importer path for:

```text
countries
business_identifier_types
tasks
occupations
industries
task_occupation_access
task_industry_access
swms_versions
swms_activity_steps
prestart_questions
```

## 3.10 UI Work Risks Running Ahead of the Schema

The current preview UI and admin workplace slice are useful demos, but continuing to build UI over the old schema risks rework.

Recommended rule:

```text
No major worker/admin production workflow should be built until the v0.3 schema reset is accepted.
```

---

## 4. Recommended Refactor Order

### Step 1 — Freeze Old Schema Expansion

Do not add production workflows to:

```text
businesses
task_packs
activities
submissions
```

unless the work is explicitly temporary/demo-only.

### Step 2 — Generate Fresh v0.3 Migrations

Create new migration files in dependency order.

Recommended grouping:

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

### Step 3 — Port Infrastructure

Port or adapt:

- context scoping;
- audit service;
- policies;
- tests;
- seed style;
- vertical slice delivery approach.

### Step 4 — Build Minimal Importer

Importer P0 should validate and load a small approved source set before large-scale batch import.

### Step 5 — Build First Worker Flow

Target flow:

```text
imported task -> worker app view -> step read events -> signature -> pre-start -> alert/evidence/audit
```

---

## 5. Professional Assessment

Current repo quality:

```text
Laravel scaffold quality: good
Architecture documentation quality: good
Audit service quality: strong
Current schema alignment: weak
Runtime compliance modelling: incomplete
Importer readiness: missing
Production readiness: not yet
```

The repository is worth continuing, but the next work should be schema/importer correction rather than more UI on old tables.

---

## 6. Final Recommendation

Treat `opsfortress-demo` as:

```text
technical foundation + useful hardening patterns
```

not as:

```text
final database model
```

The winning path is to refactor forward into v0.3 while preserving the good engineering work already done.
