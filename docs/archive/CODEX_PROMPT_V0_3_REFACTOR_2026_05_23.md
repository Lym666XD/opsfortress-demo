# Codex Prompt — OpsFortress v0.3 Schema + Docs Refactor

> Created: 2026-05-23
> Author: Yiming (BA), drafted with Claude
> Inputs: existing v0.3 migration set + DBML + V03DemoSeeder + the 2026-05-23
>   `OpsFortress_v0.3_Database_Review_Report_Clean.docx` DA/BA validation report.
> Audience: Codex / Claude Code in implementation mode.
>
> READ THIS WHOLE DOCUMENT BEFORE WRITING CODE. Do not start with the
> "easy" cleanup tasks. Several of the P0 items below depend on
> decisions that must be confirmed first — flag those clearly and stop
> for confirmation rather than guessing.

---

## 0. Context

The repository is at v0.3 schema reset stage. Migrations
`database/migrations/2026_05_18_000000_*` through
`2026_05_18_000011_*` already created 48 PostgreSQL tables. The
DA/BA review confirmed that P0 coverage is good and master/content
relationships are valid, but it also surfaced open design points
and the runtime workflow has not been seeded or tested end to end.

This refactor task addresses three things in one consistent pass:

1. Schema corrections (semantics, FK directions, append-only enforcement, missing links).
2. Code hygiene (orphan models, duplicate tenancy stack, dead migrations).
3. Documentation reconciliation (DBML, roadmap, architecture records, role notes) so the docs match the implemented schema.

Do **not** introduce controllers, UI screens, or importer business logic
in this task. Stay focused on schema, models, seeders, tests, and docs.

---

## 1. Authoritative sources (single source of truth ranking)

When two sources disagree, prefer in this order:

1. The decisions tagged `[CONFIRMED]` inside this prompt.
2. The Laravel migration files under `database/migrations/2026_05_18_*`.
3. `docs/V0_3_SCHEMA_RESET_PLAN.md`.
4. `docs/DBML_FINAL_REVIEW_2026_05_17.md`.
5. `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml`.
6. `docs/WHS_Architecture_Record.md` (treat as historical business context, not schema authority).

After this refactor, the README must be updated so the rank is:
**Laravel migrations + the regenerated DBML are co-authoritative. Older
docs are background.** See §6.7.

---

## 2. Open decisions that block implementation

These need Kevin / Yiming sign-off before code changes. Codex must
NOT silently choose. Output a `docs/OPEN_DECISIONS_2026_05_23.md`
file listing each open question, the options, the impact, and the
recommended default. Do this BEFORE editing migrations.

### D1. `workplace_environments` — lookup table OR per-workplace child?

- Option A (DBML semantics): global lookup table
  `(id, environment_code, environment_name)` with values like
  Construction / Federal / Mine / Petroleum / Other, referenced from
  `workplaces.environment_id`.
- Option B (current migration semantics): per-workplace child table
  `(id, workplace_id, environment_code, name, ...)` letting one
  workplace declare multiple environment zones.

Recommended default: **Option A**, because Kevin's meeting notes
treat environment as a top-level jurisdiction classifier. If
multi-zone per workplace is also needed, add a second table
`workplace_zones` rather than overloading `workplace_environments`.

### D2. Per-user occupation — add `user_occupations` join table?

The current schema cannot say "Alex is a welder" with FK integrity.
Without this, `task_occupation_access` is unreachable from the worker
side.

Recommended default: add
`user_occupations (id, account_id, user_id, occupation_id, is_primary, starts_at, ends_at, metadata, timestamps, softDeletes)`
with a partial unique index on `(user_id, occupation_id) WHERE deleted_at IS NULL`.

### D3. `business_entities.blockchain_id`

DBML requires `blockchain_id uuid [unique, not null]` on
`business_entities`. The current migration drops it. The old `users`
table still carries a `blockchain_id` column from earlier scaffold.

Recommended default: move `blockchain_id` to `business_entities` as
`uuid not null unique default gen_random_uuid()`, and remove the
leftover users column.

### D4. SWMS — which of the 25 extra worker-view columns get promoted?

`IMPORTER_INTAKE_NOTES.md` Question C shows the v4 workbook has 34
worker-view columns but `swms_activity_steps` only maps 9.

Recommended default: promote these as dedicated columns (others stay
in `metadata` JSONB):

- `initial_risk_level` (string)
- `residual_risk_level` (string)
- `residual_risk_reason` (text)
- `stop_work_trigger` (boolean)
- `evidence_required` (boolean)
- `evidence_prompt` (text)
- `quick_view_summary` (text)
- `primary_task_performer` (string)
- `supervisory_verification` (string)

Reason: these are queried in dashboards or used as branching logic.

### D5. `contractor_relationships.workplace_id`

DBML has it, migration removed it.

Recommended default: keep contract relationship at
host-business ↔ contractor-business level (current migration), but
add a separate `contractor_workplace_scope` table for per-workplace
overrides only when Kevin confirms that contract terms differ per
workplace. Until then, leave it out.

### D6. `audit_events` subject linkage — polymorphic only OR also FK?

DA/BA report explicitly flags this. Current schema uses
`subject_type` + `subject_id` strings, no FK.

Recommended default: keep polymorphic for generality, but also add
nullable `worker_task_session_id` FK with restrictOnDelete, because
the worker-session chain is the most frequent subject and needs FK
integrity. Document that one of the two must be present.

### D7. `audit_events` cascade behaviour

Current migration: `account_id` `cascadeOnDelete`. This destroys the
forensic record when an account is deleted.

Recommended default: change to `restrictOnDelete`. Account deletion
becomes a soft-delete-only operation. Update
`customer_accounts` documentation to enforce this.

---

## 3. Schema changes (in dependency order)

Once D1–D7 are decided, create migrations dated 2026-05-23 in this
order. Do not modify the 2026-05-18 migrations in place — they are
already applied locally. Add new migrations on top.

### 3.1 Reset `workplace_environments` (depends on D1)

If Option A wins:

- Drop the existing `workplace_environments` migration's table
  (in a new reset migration).
- Re-create `workplace_environments` as a flat lookup:
  `(id, environment_code unique, environment_name, active boolean, metadata, timestamps, softDeletes)`.
- Add `workplaces.environment_id` nullable FK with `restrictOnDelete`.
- Seed the 5 known values (Construction / Federal / Mine / Petroleum / Other) in `V03DemoSeeder`.

### 3.2 Add `user_occupations` (depends on D2)

Migration `2026_05_23_000001_create_user_occupations_table.php`.

```php
Schema::create('user_occupations', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('occupation_id')->constrained('occupations')->restrictOnDelete();
    $table->boolean('is_primary')->default(false);
    $table->date('starts_on')->nullable();
    $table->date('ends_on')->nullable();
    $table->foreignUuid('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->jsonb('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['account_id', 'user_id']);
    $table->index('occupation_id');
});
DB::statement(
    'CREATE UNIQUE INDEX user_occupations_active_unique '.
    'ON user_occupations (user_id, occupation_id) '.
    'WHERE deleted_at IS NULL',
);
```

Add the model `App\Domain\OpsFortress\People\Models\UserOccupation`
(replace the existing orphan model file — see §4.2).

### 3.3 Move `blockchain_id` onto `business_entities` (depends on D3)

Migration `2026_05_23_000002_move_blockchain_id_to_business_entities.php`.

- Add `business_entities.blockchain_id uuid unique default gen_random_uuid()`.
- Backfill existing rows.
- After backfill, set NOT NULL.
- Drop `users.blockchain_id` (legacy from scaffold).

### 3.4 Promote SWMS worker-view fields (depends on D4)

Migration `2026_05_23_000003_promote_swms_step_fields.php`.

Add columns to `swms_activity_steps` per D4 default. All nullable
(content existed before this promotion).

If Kevin confirms different fields than the default list, adjust
accordingly. The importer mapping notes
(`IMPORTER_INTAKE_NOTES.md` §C) should be updated in the same commit.

### 3.5 audit_events forensic hardening (depends on D6, D7)

Migration `2026_05_23_000004_harden_audit_events.php`.

- Change `account_id` FK from `cascadeOnDelete` to `restrictOnDelete`.
- Add nullable `worker_task_session_id` FK with `restrictOnDelete`.
- Add a CHECK constraint: at least one of
  `worker_task_session_id` or (`subject_type` AND `subject_id`) is non-null.
- Add a PostgreSQL trigger:

```sql
CREATE OR REPLACE FUNCTION audit_events_block_update_delete()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  RAISE EXCEPTION 'audit_events is append-only';
END;
$$;

CREATE TRIGGER audit_events_no_update
BEFORE UPDATE OR DELETE ON audit_events
FOR EACH ROW EXECUTE FUNCTION audit_events_block_update_delete();
```

Apply the same trigger pattern to `signatures` and `evidence_files`
(WHS legal evidence, must be append-only at DB layer).

### 3.6 Drop `tasks.trade_industry` free-text column

Migration `2026_05_23_000005_normalise_tasks_taxonomy.php`.

- Drop `tasks.trade_industry` (redundant — `task_industry_access` is the FK source of truth).
- Keep `task_group / task_sub_group / task_leaf` (Kevin's content taxonomy, not a relational FK).

### 3.7 Reverse signature/evidence relationship if needed

The DBML has `signatures.evidence_file_id`, migration has
`evidence_files.signature_id`. The migration direction (evidence
points to signature) is more correct because a signature is a kind
of evidence event that may produce one signature image file. Do not
change the migration. Instead update the DBML in §6.

### 3.8 Make `audit_events.account_id` and `signatures.account_id` not cascadeOnDelete

Already covered in 3.5 for audit_events. Apply the same restrict
treatment for signatures and evidence_files.

---

## 4. Code hygiene

### 4.1 Squash 2026_04_28 and 2026_05_12/14 migrations

Goal: a fresh `migrate:fresh` should only run the 2026-05-18 reset
plus the new 2026-05-23 migrations, plus framework tables (cache,
jobs, sessions). The old scaffold migrations currently create then
drop tables.

Approach:

- Delete migration files:
  - `2026_04_28_212300_create_core_platform_tables.php`
  - `2026_04_28_212400_add_platform_columns_to_users_table.php`
  - `2026_04_28_212500_create_workflow_tables.php`
  - `2026_05_12_100000_create_audit_events_table.php`
  - `2026_05_12_100100_fix_nullable_unique_assignment_indexes.php`
  - `2026_05_12_100200_fix_file_uploads_disk_default.php`
  - `2026_05_12_110000_add_person_type_to_users_table.php`
  - `2026_05_14_100000_fix_task_packs_code_uniqueness.php`
  - `2026_05_14_100100_widen_blockchain_id_and_abn_unique.php`
- Fold the still-needed columns into the 2026-05-18 reset migrations:
  - `users.person_type`, `users.contractor_type` go into
    `0001_01_01_000000_create_users_table.php` (or a small new
    `2026_05_18_000003_*` patch — pick the cleaner option).
- Delete the v0.3 reset migration's table drops that referred to
  the now-deleted scaffold tables (the drop statements become
  no-ops on fresh, but keep `Schema::dropIfExists` to be safe
  against partial states).
- Verify `php artisan migrate:fresh --seed` still passes.

If squashing is too risky for a single PR, ship the legacy
migrations as `database/migrations/legacy/` and update
`config/database.php` to ignore that directory in fresh runs — but
make the deletion the goal.

### 4.2 Delete or replace orphan Eloquent models

These reference tables that no longer exist. Remove the files
unless they are about to be replaced by new models with the same
class name pointing at v0.3 tables:

- `app/Domain/OpsFortress/Tenancy/Models/Tenant.php`
- `app/Domain/OpsFortress/Businesses/Models/Business.php`
- `app/Domain/OpsFortress/Permissions/Models/Role.php`
- `app/Domain/OpsFortress/Permissions/Models/UserRole.php`
- `app/Domain/OpsFortress/People/Models/UserOccupation.php` — replace with new v0.3 version per §3.2
- `app/Domain/OpsFortress/Workplaces/Models/WorkplaceUserAssignment.php`
- `app/Domain/Whs/Activities/Models/Activity.php`
- `app/Domain/Whs/Submissions/Models/Submission.php`
- `app/Domain/Whs/TaskPacks/Models/TaskPack.php`
- `app/Domain/Whs/TaskPacks/Models/TaskPackIndustry.php`
- `app/Domain/Whs/TaskPacks/Models/TaskPackOccupation.php`
- `app/Domain/Whs/Files/Models/FileUpload.php`
- `app/Domain/Whs/Files/Models/GeneratedDocument.php`

Also delete the now-empty namespaces / folders.

### 4.3 Collapse the two tenancy/context stacks

Both `app/Domain/Shared/Tenancy/*` (old) and
`app/Domain/Shared/Context/*` (new) exist. Remove the Tenancy
folder entirely and ensure all consumers use Context. Search the
codebase for `BelongsToTenant`, `TenantContext`, `TenantScope` —
replace usages with `BelongsToAccount`, `AccountContext`,
`AccountScope`.

### 4.4 Verify no references to dropped tables

Run a grep for: `tenants`, `businesses` (without `business_entities`),
`task_packs`, `activities`, `submissions`, `file_uploads`,
`generated_documents`, `workplace_user_assignments`, `user_roles`,
`user_occupations` (old version) — anywhere in `app/`, `routes/`,
`config/`, `database/seeders/`, `tests/`. Each match should be
either removed or migrated to the v0.3 equivalent.

---

## 5. Runtime demo seed + importer validation upgrades

### 5.1 Extend `V03DemoSeeder` with a runtime journey

Add seed data exercising the worker chain end to end:

- 1 demo worker user with `person_type = employee`, with a
  `user_occupations` row pointing at the demo occupation.
- 1 `worker_task_sessions` row (status: completed).
- 1–3 `swms_step_events` rows showing read events meeting
  `minimum_read_seconds`.
- 1 `signatures` row tied to the session.
- 1 `prestart_submissions` row + 1 `prestart_responses` row.
- 1 `evidence_files` row attached to the prestart submission.
- 2–3 `audit_events` forming a small hash chain (use the existing
  `AuditService` so `previous_hash` / `event_hash` / `hash_sequence`
  are correctly set).
- 1 `alerts` row (warning severity, status open) tied to the
  prestart submission.

This validates the relationship chain the DA/BA report flagged as
"schema pass; data not yet validated".

### 5.2 Categorise `import_validation_results.rule_code`

To support the AI → JSON Schema → Python → Laravel importer gate
chain in the DA/BA report's Part 6, adopt this naming convention
inside the importer:

| Prefix | Meaning | Example |
|---|---|---|
| `schema:*` | JSON Schema failure (AI output stage) | `schema:missing_required_field` |
| `structure:*` | Python / workbook structure failure | `structure:unknown_sheet`, `structure:column_order` |
| `fk:*` | Foreign key cannot be resolved at importer | `fk:occupation_not_found` |
| `business:*` | Business-rule violation, schema is fine | `business:duplicate_task_external_id` |
| `dup:*` | Idempotency / dedup violation | `dup:source_file_hash_already_imported` |

Document this in `IMPORTER_INTAKE_NOTES.md` and add a check
constraint or model-level validator on the rule_code prefix.

### 5.3 Add append-only assertion test

Add `tests/Feature/Database/AppendOnlyEnforcementTest.php` that
attempts to UPDATE and DELETE rows in `audit_events`, `signatures`,
`evidence_files` after they are inserted, and asserts the database
raises an exception (or update count is zero).

---

## 6. Documentation reconciliation

After the schema changes land, update markdown files in this order:

### 6.1 Regenerate `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml`

It must reflect the implemented schema, not the May 17 version.
Specifically:

- Add: `user_workplace_access`, `user_occupations`,
  `workplace_task_settings`, `prestart_submissions`,
  `posttask_submissions`, `worker_training_completions`,
  `import_batches`, `import_source_files`,
  `import_validation_results`, `alerts`.
- Change: `workplace_environments` per D1 final decision,
  `signatures` / `evidence_files` direction, `prestart_responses`
  parent FK, `audit_events` fields and FKs, `business_entities`
  blockchain_id, `swms_activity_steps` promoted columns,
  `tasks` taxonomy.
- Remove: `users.role` (replaced by access-table permission_role).
- Add the same TableGroup tags so dbdiagram.io renders cleanly.

### 6.2 Mark `docs/DBML_FINAL_REVIEW_2026_05_17.md` as historical

Add a banner at the top:

```text
> Status: SUPERSEDED 2026-05-23.
> The add-ons in §4 have been implemented. The DBML in this folder
> was regenerated against the live migrations. Use that file plus
> the migration files as authority going forward.
```

### 6.3 Rewrite `IMPLEMENTATION_ROADMAP.md` §"Locked Decisions (2026-05-12)"

That section still talks about `tenant_id`, `activities.payload`,
`submissions.payload`, and the `WHS_Architecture_Record §3.18` table
list — all obsolete. Replace with a new "Locked Decisions (2026-05-23)"
section that lists:

- Account-scoped row isolation via `AccountContext` / `AccountScope`.
- v0.3 schema is the production schema; scaffold tables are removed.
- Audit/signature/evidence are append-only at DB layer (trigger-enforced).
- Importer rule_code namespacing (per §5.2).
- AI → JSON Schema → Python → Laravel importer → PostgreSQL is the
  content delivery pipeline.
- Person identity (employee / contractor / labour_hire / other) is
  on `users.person_type`; permission role lives in
  `user_business_access` / `user_workplace_access`.

Keep the old "Locked Decisions (2026-05-12)" block as a sub-section
labelled "Superseded — kept for traceability".

### 6.4 Rewrite `docs/Role_Architecture_Notes.md`

The "Current DB Gap" and "Recommended MVP Approach" sections
describe a problem that has been solved. Restructure as:

- Keep §"Core Finding" (the conceptual distinction is still useful).
- Keep §"Dimension 1" / §"Dimension 2" content.
- Replace "Current DB Gap" with "How v0.3 Implements This":
  - person_type / contractor_type on users — done.
  - permission_role on user_business_access and
    user_workplace_access — done.
  - contractor_relationships table — done.
  - user_occupations — done per §3.2.
- Replace "Questions for Kevin" with "Resolved" / "Still Open"
  buckets (the cross-business contractor access decision is now
  encoded in contractor_relationships + user_workplace_access).

### 6.5 Frame `docs/WHS_Architecture_Record.md` and `docs/WHS架构分析记录.md` as background

Add a banner at the top of both files (English + Chinese):

```text
> Status: business and product background. Last full update 2026-05-06.
> Schema details predate the v0.3 reset (2026-05-17). For schema truth
> see the migration files plus the regenerated DBML.
```

Do NOT rewrite these 1900-line files. They contain valuable
business research. Just frame them correctly.

### 6.6 Refresh `TARGET_ARCHITECTURE.md`

- §"Database Strategy → Master Data Layer": specify
  `workplace_environments` is the global lookup (per D1).
- §"Recommended Implementation Order": note that Phase 1 is done,
  Phase 2 (importer + minimal worker flow) is in progress.
- Confirm the `app/Domain/...` folder list still matches reality;
  remove `Tenancy/` if §4.3 removed it.

### 6.7 Update `README.md` authority statement

Replace the "Authoritative Design Sources" section. Make it explicit
that:

1. The Laravel migrations under `database/migrations/2026_05_18_*`
   and `2026_05_23_*` are the schema source of truth.
2. `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml` is regenerated to
   match those migrations and is the visual reference.
3. All other docs (DBML_FINAL_REVIEW, ROADMAP locked decisions,
   Role_Architecture_Notes, WHS_Architecture_Record) are
   historical / business context.

### 6.8 Add `docs/CHANGELOG_2026_05_23_REFACTOR.md`

One-page changelog summarising:

- schema deltas (what migration files were added)
- decisions resolved (link to OPEN_DECISIONS_2026_05_23.md)
- removed files (legacy migrations, orphan models, Tenancy stack)
- doc files updated
- verification commands run and their results

---

## 7. Verification gate (must pass before merge)

```bash
php artisan migrate:fresh --seed
php artisan test --filter=V03SchemaContractTest
php artisan test --filter=V03DevSeederTest
php artisan test --filter=AppendOnlyEnforcementTest
php artisan test                          # full suite
./vendor/bin/pint --test
php artisan route:list                    # no fatal model load errors
```

Also manually verify:

- `psql opsfortress_demo -c "\\dt"` lists no `tenants`,
  `businesses`, `task_packs`, `activities`, `submissions`,
  `file_uploads`, `generated_documents`, `workplace_user_assignments`,
  `roles`, `user_roles`.
- `grep -rn "BelongsToTenant\|TenantContext\|TenantScope" app/ routes/ config/ tests/` returns no matches.
- DBML file opens cleanly in dbdiagram.io with no syntax errors.

---

## 8. Output structure expected from Codex

When Codex completes this task, the PR / branch should contain:

1. `docs/OPEN_DECISIONS_2026_05_23.md` — created early, used to
   gate D1–D7 confirmation.
2. New migrations under `database/migrations/2026_05_23_*` for each
   schema change in §3.
3. Deleted legacy migration files per §4.1.
4. Deleted orphan models / Tenancy stack per §4.2 and §4.3.
5. Updated `database/seeders/V03DemoSeeder.php` with runtime journey.
6. New `tests/Feature/Database/AppendOnlyEnforcementTest.php`.
7. Updated `tests/Feature/Database/V03SchemaContractTest.php` to
   cover new columns / tables / constraints.
8. Regenerated `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml`.
9. Banner / refresh edits to the seven markdown files in §6.
10. `docs/CHANGELOG_2026_05_23_REFACTOR.md`.

---

## 9. Anti-patterns to avoid

- Do NOT silently pick a side for any D1–D7 decision. Stop and
  surface them as `docs/OPEN_DECISIONS_2026_05_23.md` first.
- Do NOT modify the 2026-05-18 migration files in place. Add new
  2026-05-23 migrations on top.
- Do NOT add controllers, FormRequests, Policies, or Inertia pages
  in this task. Schema + models + seeders + docs only.
- Do NOT remove softDeletes from configuration tables. Append-only
  enforcement applies to runtime/evidence/audit only.
- Do NOT delete `WHS_Architecture_Record.md` content; only banner it.
- Do NOT collapse `prestart_submissions` and `prestart_responses`
  back into a single table — the parent/child split is intentional.
- Do NOT change UUID strategy or remove `gen_random_uuid()` defaults.

---

## 10. Final reminder

The goal of this task is to make the schema, the code, and the
docs tell the same story. Right now they tell three slightly
different stories, and that confusion will compound as soon as the
first importer slice tries to load real Kevin workbook data into
PostgreSQL. Fix the foundation now so the importer work in M17 can
proceed against a coherent model.
