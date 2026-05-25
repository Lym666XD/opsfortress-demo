# Codex Prompt — v0.3 Refactor Follow-up Audit & Fixes

> Created: 2026-05-23 (round 2)
> Audience: Codex / Claude Code starting from a fresh conversation with no
> prior context. Read this whole document and the files it points at
> before changing anything.
>
> Predecessor: `docs/CODEX_PROMPT_V0_3_REFACTOR_2026_05_23.md` (round 1)
> Predecessor changelog: `docs/CHANGELOG_2026_05_23_REFACTOR.md`
> Authoritative decisions: `docs/OPEN_DECISIONS_2026_05_23.md` (D1–D7 confirmed)

---

## 0. Context

Round 1 landed eight 2026-05-23 migrations (`000000`–`000007`),
deleted the scaffold migrations and orphan models, added append-only
triggers on `audit_events` / `signatures` / `evidence_files`, added
cross-account consistency triggers on the same three tables, added
`permission_role` CHECK constraints, added `account_businesses`
one-primary and `business_industries.is_primary` columns, and rebuilt
the schema contract test.

Round 2 (this task) is an audit + fix pass that:

1. Verifies the round-1 work against the original prompt requirements.
2. Patches genuine gaps (most importantly, the runtime journey seed
   that was specified but never implemented).
3. Adds DB-level guards that were not in scope of round 1 but are
   needed before importer/runtime work begins.

Stay focused on schema, models, seeder, AuditService, and tests.
Do not touch controllers, UI, or business importer logic. Do not
change anything in `app/Domain/Whs/Importer/Tabs/*` except to fix
a missing call (none expected — flag if you find one).

---

## 1. Authoritative source order (when sources disagree)

1. Confirmed `OPEN_DECISIONS_2026_05_23.md` rows.
2. Laravel migration files (treat as the live schema).
3. This prompt's §3 fixes.
4. `docs/V0_3_SCHEMA_RESET_PLAN.md`.
5. Everything else is historical context.

If you find an instruction in this prompt that contradicts a confirmed
decision in `OPEN_DECISIONS_2026_05_23.md`, the confirmed decision wins
— stop and surface the contradiction in a new doc rather than guessing.

---

## 2. Required audit checklist — read before editing

For each item below, confirm by reading the referenced file. Output a
`docs/ROUND2_AUDIT_2026_05_23.md` file with PASS / FAIL / NOTE for
every row, *before* writing any code. If any row is FAIL or NOTE you
still proceed to §3 — the audit doc records what you found and why
the round-1 verification missed it.

| ID | Check | Where to look |
|----|-------|---------------|
| AUD-01 | Append-only trigger function exists | `2026_05_23_000004_harden_audit_events_and_evidence.php` |
| AUD-02 | Append-only trigger attached to `audit_events`, `signatures`, `evidence_files` | same file |
| AUD-03 | `audit_events.account_id` is `RESTRICT`, not `CASCADE` | same file + `V03SchemaContractTest::test_key_foreign_keys_and_indexes_match_the_v03_contract` |
| AUD-04 | `audit_events.worker_task_session_id` FK added | same file |
| AUD-05 | `audit_events_subject_link_check` CHECK constraint added | same file |
| AUD-06 | `signatures.account_id` and `evidence_files.account_id` are `RESTRICT` | same file |
| AUD-07 | `business_entities.blockchain_id` is NOT NULL and UNIQUE | `2026_05_23_000002_*.php` |
| AUD-08 | `users.blockchain_id` column is dropped | `0001_01_01_000000_create_users_table.php`, `2026_05_23_000002_*.php` |
| AUD-09 | `workplace_environments` is a global lookup (no `workplace_id` column) | `2026_05_23_000000_*.php` |
| AUD-10 | `workplaces.environment_id` FK exists and is `RESTRICT` | same file |
| AUD-11 | `user_occupations` table exists with partial unique on `(user_id, occupation_id) WHERE deleted_at IS NULL` | `2026_05_23_000001_*.php` |
| AUD-12 | 9 promoted SWMS columns exist on `swms_activity_steps` | `2026_05_23_000003_*.php` |
| AUD-13 | `tasks.trade_industry` column is dropped | `2026_05_23_000005_*.php` |
| AUD-14 | `import_validation_results.rule_code` CHECK constraint exists | `2026_05_23_000006_*.php` |
| AUD-15 | All round-1 orphan model files are deleted | `find app/Domain -type f -name "*.php"` |
| AUD-16 | `app/Domain/Shared/Tenancy/` is fully removed | `ls app/Domain/Shared/` |
| AUD-17 | Round-1 deleted scaffold migrations are absent | `ls database/migrations/2026_04_28_* 2026_05_12_* 2026_05_14_*` |
| AUD-18 | `V03DemoSeeder` seeds runtime/evidence/audit/alert rows | `database/seeders/V03DemoSeeder.php` |
| AUD-19 | `AuditService::record()` populates `worker_task_session_id` when subject is a `WorkerTaskSession` | `app/Domain/Shared/Audit/Services/AuditService.php` |
| AUD-20 | `Signature` and `EvidenceFile` Eloquent models declare `public $timestamps = false` OR the migration drops `updated_at` | `app/Domain/Whs/Evidence/Models/Signature.php`, `EvidenceFile.php` |
| AUD-21 | `permission_role` CHECK constraints `uba_permission_role_check` + `uwa_permission_role_check` exist | `2026_05_23_000007_harden_access_business_industries_and_account_consistency.php` |
| AUD-22 | `account_businesses_one_primary` partial unique index exists | `2026_05_23_000007_*.php` |
| AUD-23 | `business_industries.is_primary` column + `business_industries_one_primary` partial unique index exist; `BusinessIndustry` model casts `is_primary` as boolean | `2026_05_23_000007_*.php` + `app/Domain/OpsFortress/Industries/Models/BusinessIndustry.php` |
| AUD-24 | Cross-account consistency triggers exist on `audit_events`, `signatures`, `evidence_files` (round 1) AND on `alerts`, `prestart_submissions` (round 2 to add) | `2026_05_23_000007_*.php` + new 2026_05_24 migration |
| AUD-25 | Empty post-deletion folders are cleaned | `find app/Domain -type d -empty` |
| AUD-26 | Trigger error message is parameterised across tables | `2026_05_23_000004_*.php` |
| AUD-27 | Prepare-reset migration drop list no longer references tables that cannot exist at that point | `2026_05_18_000000_*.php` |
| AUD-28 | Status-column CHECK constraints exist on `swms_versions.status`, `worker_task_sessions.status`, `prestart_submissions.status`, `posttask_submissions.status`, `training_attempts.status`, `alerts.status`, `alerts.severity` | various 2026_05_18 + new patch migration |

AUD-01..AUD-17 are expected to PASS based on round 1.
AUD-21, AUD-22, AUD-23 are also expected to PASS because round 1
shipped migration `2026_05_23_000007_harden_access_business_industries_and_account_consistency.php`
covering them.

AUD-24 is expected PARTIAL: round-1 migration `000007` already added
cross-account triggers on `audit_events`, `signatures`,
`evidence_files`. It did NOT add triggers on `alerts`,
`prestart_submissions`, or `swms_step_events`. Round 2 must close
that gap.

AUD-18, AUD-19, AUD-20, AUD-25, AUD-26, AUD-27, AUD-28 are the
remaining fix targets. Confirm by reading anyway — if an item
silently already passes, drop it from §3 to keep the diff small.

---

## 3. Fixes required (priority order)

Create new migrations dated 2026-05-24 and onwards. Do not modify
existing migrations except where called out explicitly.

### Priority A — must fix

#### A1. Runtime journey seed in `V03DemoSeeder` (AUD-18)

The round-1 prompt §5.1 required this and it was skipped. Add seed
data inside the existing `AccountContext::runAs` block, AFTER the
`WorkplaceTaskSetting` write. Add the rows below, in order, using
the demo `$account`, `$business`, `$workplace`, `$task`, `$swms`,
`$occupation`, `$admin` already in scope.

Required additions:

1. A second demo user `worker@acme.test` with `person_type = employee`,
   `home_business_entity_id = $business->id`, password `password`.
2. `UserBusinessAccess` row giving `$worker` `permission_role = worker`.
3. `UserWorkplaceAccess` row giving `$worker` `permission_role = worker`
   at `$workplace`.
4. `UserOccupation` row linking `$worker` to `$occupation` with
   `is_primary = true`, `starts_on = today`.
5. `WorkerTaskSession` row (`status = completed`,
   `started_at = now()->subMinutes(20)`,
   `completed_at = now()->subMinutes(5)`,
   `total_read_seconds = 12`,
   `minimum_read_seconds_required = 10`).
6. One `SwmsStepEvent` row attached to the session and
   the seeded `SwmsActivityStep` (`event_type = read`,
   `read_seconds = 12`, `met_minimum_read_time = true`,
   `occurred_at = now()->subMinutes(15)`).
7. One `Signature` row attached to the session
   (`signature_type = swms_acknowledgement`,
   `signer_name = 'Wendy Worker'`,
   `signed_payload_hash = hash('sha256', $session->id.':swms_ack')`,
   `signature_data = ['type' => 'drawn', 'demo' => true]`,
   `signed_at = now()->subMinutes(10)`).
8. One `PrestartSubmission` row (`status = submitted`,
   `score_percent = 100`, `critical_failure_count = 0`,
   `has_critical_failure = false`,
   `submitted_at = now()->subMinutes(8)`).
9. One `PrestartResponse` row answering the seeded `PrestartQuestion`
   (`answer_boolean = true`, `answer_text = 'yes'`,
   `is_critical_failure = false`,
   `answered_at = now()->subMinutes(9)`).
10. One `EvidenceFile` row attached to the prestart submission
    (`evidence_type = photo`, `disk = 'local'`,
    `path = 'demo/prestart/site-check.jpg'`,
    `mime_type = 'image/jpeg'`, `size_bytes = 4096`,
    `file_hash = hash('sha256', 'demo-evidence-stub')`,
    `captured_at = now()->subMinutes(9)`).
11. One `Alert` row (`alert_type = critical_check_in_complete`,
    `severity = info`, `status = closed`,
    `title = 'Demo runtime journey complete'`,
    `assigned_to_user_id = $admin->id`,
    `resolved_at = now()->subMinutes(4)`). Use `info` and `closed`
    rather than firing an open warning, to keep the seed clean.
12. Two `audit_events` rows written via `AuditService::record()`:
    - First record: subject = `$session`, anchor =
      `AuditService::ANCHOR_SIGNATURE`,
      eventType = `worker_task_session.signature_recorded`,
      payload = `['session_id' => $session->id, 'signature_id' => $signature->id]`,
      userId = `$worker->id`.
    - Second record: subject = `$prestartSubmission`, anchor =
      `AuditService::ANCHOR_CLOSEOUT`,
      eventType = `prestart_submission.completed`,
      payload = `['submission_id' => $prestartSubmission->id, 'score_percent' => 100]`,
      userId = `$worker->id`.
    Call `AuditService::record()` via `app(AuditService::class)`.

Update `tests/Feature/Database/V03DevSeederTest.php` to assert each
new row is present after seeding and that the audit chain
`previous_hash` linkage is correct for the two events.

#### A2. AuditService must populate `worker_task_session_id` (AUD-19)

After D6 the `audit_events.worker_task_session_id` FK column exists
specifically to give the most common subject FK integrity. The
service currently never sets it.

Change `AuditService::record()` so that if `$subject` is an instance
of `\App\Domain\Whs\Runtime\Models\WorkerTaskSession`, the inserted
row sets `worker_task_session_id = $subject->getKey()` in addition
to the existing `subject_type` / `subject_id` polymorphic columns.

If the subject is some other model that exposes a
`worker_task_session_id` attribute (for example a `Signature` or a
`PrestartSubmission`), also copy that into the audit row, so the
audit trail of any session-derived subject still resolves via the
direct FK. Use `method_exists` / `isset` guards; do not throw if
absent.

Add a test in `V03SchemaContractTest` or a new
`tests/Feature/Audit/AuditServiceTest.php` asserting:

- For a session subject, `audit_events.worker_task_session_id` equals
  the session id.
- For a prestart-submission subject that carries
  `worker_task_session_id`, the column matches.
- For a subject with no session linkage (use any account-level
  model), the column is NULL and the row still passes the
  `audit_events_subject_link_check` CHECK.

#### A3. `Signature` / `EvidenceFile` models conflict with append-only trigger (AUD-20)

`audit_events` already declares `public $timestamps = false;`.
`Signature` and `EvidenceFile` do not, but the trigger blocks any
UPDATE, which Eloquent issues every time `save()` runs on an existing
model (to refresh `updated_at`).

Choose ONE of:

- Option A (recommended): add `public $timestamps = false;` to
  `Signature` and `EvidenceFile`, drop `updated_at` from those tables
  in a new patch migration, keep `created_at`.
- Option B: leave `$timestamps = true` and migrate `updated_at` out
  by trigger exception (more brittle).

Pick Option A. Create
`2026_05_24_000003_drop_updated_at_from_evidence_and_signatures.php`
that drops the `updated_at` column from `signatures` and
`evidence_files`. In the same commit, set
`public $timestamps = false;` on both Eloquent models and add a
`created_at` cast to immutable_datetime for consistency with
`AuditEvent`.

Add a test in `AppendOnlyEnforcementTest` that creates a `Signature`
via Eloquent, then calls `$signature->refresh()` and asserts the
load succeeds (i.e. no implicit update happened).

#### A4. Remove now-unused imports OR (preferred) keep them and use them via A1

`database/seeders/V03DemoSeeder.php` currently imports
`AuditService`, `Alert`, `EvidenceFile`, `Signature`,
`SwmsStepEvent`, `WorkerTaskSession` but never references them. This
fails `vendor/bin/pint --test`'s `no_unused_imports` rule. The
intended fix is to implement A1, which uses all six imports. Do not
delete the imports — finish A1 instead.

### Priority B — should fix

B1, B2, B3 from the round-1 follow-up draft are ALREADY DONE in
migration `2026_05_23_000007_harden_access_business_industries_and_account_consistency.php`.
Confirm via AUD-21, AUD-22, AUD-23 and skip if PASS.

The summary of what already exists:

- `uba_permission_role_check` + `uwa_permission_role_check` CHECK
  constraints restricting `permission_role` to
  `worker | supervisor | manager | admin | platform_admin`.
- `account_businesses_one_primary` partial unique index.
- `business_industries.is_primary` column + `business_industries_one_primary`
  partial unique index.
- `BusinessIndustry` model casts `is_primary` as boolean.

#### B4. Extend cross-account consistency triggers (AUD-24)

Round-1 migration `000007` already added consistency triggers on
`audit_events`, `signatures`, `evidence_files`. The remaining tables
that have both `account_id` and a session-derived FK are NOT yet
covered:

- `alerts` — has `account_id`, `worker_task_session_id`, `prestart_submission_id`.
- `prestart_submissions` — has `account_id` + `worker_task_session_id`.

`swms_step_events` and `prestart_responses` do not carry their own
`account_id`, so no trigger is needed for those tables (the parent
linkage already enforces consistency by inheritance).

Add `2026_05_24_000000_extend_account_consistency_triggers.php` that:

- Adds `alerts_account_consistency_check()` function comparing
  `alerts.account_id` against both
  `worker_task_sessions.account_id` (when `worker_task_session_id`
  is set) and `prestart_submissions.account_id` (when
  `prestart_submission_id` is set). Attach as
  `BEFORE INSERT OR UPDATE` trigger.
- Adds `prestart_submissions_account_consistency_check()` function
  comparing against `worker_task_sessions.account_id`. Attach as
  `BEFORE INSERT OR UPDATE` trigger.

Match the function-per-table naming pattern that round-1 `000007`
already used (e.g. `audit_events_account_consistency_check()`), so the
new functions are named `alerts_account_consistency_check()` and
`prestart_submissions_account_consistency_check()`. Each function
should raise with a table-named message like `'alerts account_id
does not match worker_task_sessions.account_id'`.

Add `tests/Feature/Database/AccountConsistencyTest.php` proving the
trigger fires when a mismatched `account_id` is inserted into
`alerts` and `prestart_submissions`. Extend
`V03SchemaContractTest::test_refactor_constraints_and_append_only_triggers_exist`
to also assert the two new triggers exist.

#### B5. Parameterise append-only trigger error message (AUD-26)

`audit_events_block_update_delete()` raises a hard-coded message
"audit_events is append-only" even when it fires on `signatures` or
`evidence_files`.

Create
`2026_05_24_000004_parameterise_append_only_trigger_message.php`
that:

1. Drops the three `*_no_update` triggers on `audit_events`,
   `signatures`, `evidence_files`.
2. Drops the function `audit_events_block_update_delete()`.
3. Creates a new function `enforce_append_only_table()` with body:

```sql
RAISE EXCEPTION '% is append-only', TG_TABLE_NAME;
```

4. Recreates the three `*_no_update` triggers pointing at the new
   function.

Update `AppendOnlyEnforcementTest` expectations: change
`expectExceptionMessage('append-only')` to
`expectExceptionMessage('signatures is append-only')` /
`'evidence_files is append-only'` / `'audit_events is append-only'`
per test method, so the parameterised message is actually asserted.

#### B6. Status-column CHECK constraints (AUD-28)

Add CHECK constraints on enumerated status / severity columns. New
migration `2026_05_24_000001_constrain_status_enums.php`:

| Table | Column | Allowed values |
|-------|--------|----------------|
| `customer_accounts` | `status` | `onboarding, active, suspended, closed` |
| `business_entities` | `entity_status` | `onboarding, active, suspended, archived` |
| `workplaces` | `status` | `active, inactive, archived` |
| `users` | `status` | `invited, active, suspended, archived` |
| `users` | `person_type` | `employee, labour_hire, contractor, visitor, regulator, supplier, other` |
| `swms_versions` | `status` | `draft, published, archived, superseded` |
| `worker_task_sessions` | `status` | `not_started, in_progress, completed, abandoned` |
| `prestart_submissions` | `status` | `draft, submitted, void` |
| `posttask_submissions` | `status` | `draft, submitted, void` |
| `training_attempts` | `status` | `in_progress, completed, abandoned` |
| `alerts` | `status` | `open, acknowledged, resolved, closed` |
| `alerts` | `severity` | `info, warning, critical` |
| `import_batches` | `status` | `pending, running, completed, failed, cancelled` |
| `import_source_files` | `status` | `pending, parsed, validated, imported, failed` |
| `import_validation_results` | `severity` | `info, warning, error` |

Use `NOT VALID` initially if the table might already contain
non-conforming values (it shouldn't because of the seeder, but be
defensive).

### Priority C — hygiene

#### C1. Delete empty domain folders (AUD-25)

Run `find app/Domain -type d -empty -delete`. Confirm the following
no longer exist:

- `app/Domain/OpsFortress/Tenancy`
- `app/Domain/OpsFortress/Businesses`
- `app/Domain/OpsFortress/Permissions`
- `app/Domain/Whs/Activities`
- `app/Domain/Whs/Submissions`
- `app/Domain/Whs/TaskPacks`
- `app/Domain/Whs/Files`

Add a `.gitkeep` if any namespace must be retained but does not yet
have a model — none expected.

#### C2. Trim `prepare_v0_3_reset` drop list (AUD-27)

`2026_05_18_000000_enable_postgres_extensions_and_prepare_v0_3_reset.php`
drops tables that, after round 1, no migration earlier in the chain
can create (`audit_events`, `user_occupations`, etc.). Remove dead
entries; keep only names a real partial-state DB might contain
(`tenants`, `businesses`, `task_packs`, `activities`, `submissions`,
`file_uploads`, `generated_documents`, `workplace_user_assignments`,
`user_roles`, `roles`, `industries` legacy, `occupations` legacy,
`workplaces` legacy, `task_pack_industries`, `task_pack_occupations`).

After trimming, the drop list should look like the legacy table set
ONLY — no name that the v0.3 reset migrations themselves create.

#### C3. Relax `import_validation_results.rule_code` regex

Current CHECK only allows one colon segment after the prefix. Allow
hierarchical sub-namespaces like `schema:json:missing_field`. New
migration `2026_05_24_000002_relax_rule_code_pattern.php`:

```sql
ALTER TABLE import_validation_results
  DROP CONSTRAINT import_validation_results_rule_code_prefix_check;

ALTER TABLE import_validation_results
  ADD CONSTRAINT import_validation_results_rule_code_prefix_check
  CHECK (rule_code ~ '^(schema|structure|fk|business|dup)(:[A-Za-z0-9_.-]+)+$');
```

Update the regex inside `ImportValidationResult::booted()` to match.

#### C4. WorkplaceEnvironment is global — seed it outside `AccountContext`

`V03DemoSeeder` currently writes the 5 environment lookup values
inside the `AccountContext::runAs` block. They are a global lookup;
move the writes above the runAs callback so the seeding reflects
that they are not account-scoped. Functional impact is zero but the
intent matches the schema.

---

## 4. Implementation rules

- All new migrations dated 2026-05-24 onward. Do not modify any
  existing 2026-05-18 or 2026-05-23 migration in place.
- Use the explicit numbering scheme:
  - `2026_05_24_000000_extend_account_consistency_triggers.php` (B4)
  - `2026_05_24_000001_constrain_status_enums.php` (B6)
  - `2026_05_24_000002_relax_rule_code_pattern.php` (C3)
  - `2026_05_24_000003_drop_updated_at_from_evidence_and_signatures.php` (A3)
  - `2026_05_24_000004_parameterise_append_only_trigger_message.php` (B5)
- All new CHECK constraints use named constraints
  (`ADD CONSTRAINT name CHECK (...)`) so they can be dropped cleanly.
- Verify the regenerated DBML
  (`docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml` and the `.txt` copy)
  matches the post-migration schema, including everything round 1
  already added (`business_industries.is_primary`,
  `account_businesses_one_primary`, `worker_task_session_id` on
  audit_events, etc.). Add notes for triggers / CHECK constraints
  as comments. Do not invent new TableGroup layouts.
- `php artisan migrate:fresh --seed` must pass cleanly at the end.
- The full test suite must pass: `php artisan test`.
- `./vendor/bin/pint --test` must pass (currently fails on V03DemoSeeder
  unused imports — see A4).

---

## 5. Verification gate

Run every command and paste output into
`docs/ROUND2_VERIFICATION_2026_05_23.md`:

```bash
php artisan migrate:fresh --seed

DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres \
  php artisan test --filter=V03SchemaContractTest

DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres \
  php artisan test --filter=V03DevSeederTest

DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres \
  php artisan test --filter=AppendOnlyEnforcementTest

DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres \
  php artisan test --filter=AccountConsistencyTest

DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres \
  php artisan test --filter=AuditServiceTest

DB_CONNECTION=pgsql DB_DATABASE=opsfortress_demo DB_USERNAME=postgres DB_PASSWORD=postgres \
  php artisan test

./vendor/bin/pint --test

psql opsfortress_demo -c "
SELECT relname,
       (SELECT count(*) FROM pg_trigger WHERE tgrelid = c.oid AND NOT tgisinternal) AS triggers
FROM pg_class c
WHERE relname IN ('audit_events','signatures','evidence_files','alerts',
                  'prestart_submissions','swms_step_events');"

psql opsfortress_demo -c "
SELECT n.nspname, c.conname, pg_get_constraintdef(c.oid)
FROM pg_constraint c
JOIN pg_namespace n ON n.oid = c.connamespace
WHERE c.contype = 'c' AND n.nspname = 'public'
ORDER BY c.conrelid::regclass::text, c.conname;"

psql opsfortress_demo -c "
SELECT COUNT(*) FROM worker_task_sessions;
SELECT COUNT(*) FROM signatures;
SELECT COUNT(*) FROM evidence_files;
SELECT COUNT(*) FROM audit_events;
SELECT COUNT(*) FROM alerts;
SELECT COUNT(*) FROM user_occupations;"
```

Expected counts after `migrate:fresh --seed`:

- `worker_task_sessions`: 1
- `signatures`: 1
- `evidence_files`: 1
- `audit_events`: 2
- `alerts`: 1
- `user_occupations`: 1

---

## 6. Expected branch outputs

- `docs/ROUND2_AUDIT_2026_05_23.md` (audit findings from §2)
- `docs/ROUND2_VERIFICATION_2026_05_23.md` (verification command output)
- 5 new migrations dated 2026_05_24 per §4 numbering
- Modified `database/seeders/V03DemoSeeder.php` (A1 + C4 reorder)
- Modified `app/Domain/Shared/Audit/Services/AuditService.php` (A2)
- Modified `app/Domain/Whs/Evidence/Models/Signature.php` (A3 — `$timestamps = false`)
- Modified `app/Domain/Whs/Evidence/Models/EvidenceFile.php` (A3 — `$timestamps = false`)
- Modified `app/Domain/Shared/Importer/Models/ImportValidationResult.php` (C3 regex)
- Modified `tests/Feature/Database/V03SchemaContractTest.php` (assert new triggers + status CHECKs)
- Modified `tests/Feature/Database/V03DevSeederTest.php` (assert runtime journey rows)
- Modified `tests/Feature/Database/AppendOnlyEnforcementTest.php` (B5 message + A3 refresh test)
- New `tests/Feature/Database/AccountConsistencyTest.php` (alerts + prestart_submissions trigger)
- New `tests/Feature/Audit/AuditServiceTest.php` (A2 worker_task_session_id population)
- Modified `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml` (+ `.txt`) — verify completeness
- Updated `docs/CHANGELOG_2026_05_23_REFACTOR.md` with round 2 deltas

Note: `BusinessIndustry.php` is NOT in the list — round-1 migration `000007`
already wired up the `is_primary` cast.

---

## 7. Anti-patterns

- Do not weaken any existing CHECK / trigger to make tests pass.
- Do not silently change `OPEN_DECISIONS_2026_05_23.md` outcomes;
  raise a new decision row if you find a confirmed answer needs to
  flip.
- Do not import a third-party permission package (Spatie etc.) in
  this round; keep the simple `hasRole()` query on the User model.
- Do not seed any second customer account or second business entity;
  the runtime journey is for the existing `Acme Construction`
  account.
- Do not modify the import tab implementations
  (`Industries/Occupations/TasksTabImporter`) except to adjust the
  rule_code namespace strings they emit if C3 changes the regex.
- Do not soft-delete or mark superseded any data the round-1 seeder
  produced; extend it.

---

## 8. Final reminder

This round is about closing the gap between what the round-1 prompt
asked for and what shipped, plus tightening DB invariants that round
1 did not address. After this round the schema should be
implementation-ready for the M17 importer slices and the first
worker-flow UI work — without further migrations until those
features surface a new requirement.
