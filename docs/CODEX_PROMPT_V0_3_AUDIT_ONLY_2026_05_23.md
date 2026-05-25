# Codex Prompt — v0.3 Database AUDIT ONLY (no code changes)

> Created: 2026-05-23  
> Updated: 2026-05-24  
> Audience: Codex / Claude Code starting from a fresh conversation.  
> Mode: READ-ONLY AUDIT. Do not modify source code. Output one findings document and stop.

## Predecessor Docs

Read these first:

- `docs/archive/CODEX_PROMPT_V0_3_REFACTOR_2026_05_23.md` — round 1 spec
- `docs/CODEX_PROMPT_V0_3_FOLLOWUP_2026_05_23.md` — round 2 fix spec; do NOT execute it
- `docs/archive/OPEN_DECISIONS_2026_05_23.md` — D1-D7 confirmed
- `docs/CHANGELOG_2026_05_23_REFACTOR.md` — round 1 changelog
- `docs/README.md` — current documentation/source-of-truth map

If `docs/CODEX_PROMPT_V0_3_FOLLOWUP_2026_05_23.md` is missing, do not stop. Use the Group 2 claims in this audit prompt as the source of round-2-pending claims and mark the missing follow-up prompt as a NOTE in the Environment section.

---

## 0. What This Task Is

Round 1 of the v0.3 refactor shipped the `2026_05_23_*` migrations and follow-up hardening. A second round of fixes has been drafted in `docs/CODEX_PROMPT_V0_3_FOLLOWUP_2026_05_23.md`.

This audit independently verifies:

1. which round-1 claims are actually complete;
2. which round-2 "still pending" claims are still pending;
3. which round-2 claims have already been completed and should be removed from the fix prompt.

The goal is to produce ONE markdown file:

```text
docs/ROUND2_AUDIT_2026_05_23.md
```

Do not make any source-code or schema changes.

---

## 1. Rules

You must NOT:

- Run `php artisan migrate`, `php artisan migrate:fresh`, or `php artisan db:seed`.
- Create, edit, or delete any file other than `docs/ROUND2_AUDIT_2026_05_23.md`.
- Run `pint` in fix mode.
- Touch `.localdoc/`, `vendor/`, `node_modules/`, or `storage/`.
- Edit `docs/CODEX_PROMPT_V0_3_FOLLOWUP_2026_05_23.md` based on your findings.

You may:

- Read files in the repo.
- Run read-only search/list commands.
- Run read-only `psql` queries if a local Postgres database is already migrated; do not migrate it yourself.
- Run `php vendor/bin/pint --test` if useful.
- Avoid running the full test suite unless you need it for a specific audit item. Prefer source evidence.

---

## 2. Status Semantics

Use exactly these statuses:

```text
PASS | FAIL | NOTE | NEEDS-DB
```

Interpret them as follows:

- `PASS`: the claim or desired state is verified by source evidence.
- `FAIL`: the claim or desired state is false or missing, with source evidence.
- `NOTE`: the condition is true but not clearly a defect, or the finding needs human judgement.
- `NEEDS-DB`: the item cannot be verified from source files alone and would require inspecting a migrated database.

Important Group 2 rule:

- Group 2 uses **desired fixed states**, not negative pending claims.
- For Group 2, `PASS` means the follow-up prompt is trying to fix something already done; recommend removing that section from the follow-up prompt.
- For Group 2, `FAIL` means the item is genuinely still pending; recommend keeping it in the follow-up prompt.
- For Group 2, `NOTE` means the item is a design judgement, defensive cleanup, or non-blocking hygiene issue; recommend human review before changing the follow-up prompt.

For each row, output a single line:

```text
AUD-XX | PASS | <one-line evidence with file:line or constraint/function/index name>
AUD-XX | FAIL | <what is missing or wrong, with file:line evidence>
AUD-XX | NOTE | <unexpected or judgement-dependent condition, with evidence>
AUD-XX | NEEDS-DB | <why source files are insufficient>
```

---

## 3. Audit Items

### Group 1 — Round-1 Work Claimed Complete

These should normally be `PASS`. Any `FAIL` is a regression or an incomplete round-1 item.

| ID | Claim |
|----|-------|
| AUD-01 | `audit_events_block_update_delete()` function exists in `database/migrations/2026_05_23_000004_harden_audit_events_and_evidence.php`. |
| AUD-02 | Append-only triggers `audit_events_no_update`, `signatures_no_update`, `evidence_files_no_update` are created in the same file. |
| AUD-03 | `audit_events.account_id` FK is changed to `RESTRICT` in the same file. |
| AUD-04 | `audit_events.worker_task_session_id` nullable FK is added in the same file. |
| AUD-05 | `audit_events_subject_link_check` CHECK constraint exists in the same file. |
| AUD-06 | `signatures.account_id` and `evidence_files.account_id` FKs are changed to `RESTRICT` in the same file. |
| AUD-07 | `business_entities.blockchain_id` is set up as UUID, NOT NULL, and UNIQUE in `2026_05_23_000002_move_blockchain_id_to_business_entities.php`. |
| AUD-08 | `users.blockchain_id` is absent from `0001_01_01_000000_create_users_table.php`, and `2026_05_23_000002_*.php` drops it from any pre-existing state. |
| AUD-09 | `workplace_environments` is rebuilt as a global lookup in `2026_05_23_000000_reset_workplace_environments_as_lookup.php`: it has `environment_code`, `environment_name`, and no `workplace_id`. |
| AUD-10 | `workplaces.environment_id` FK with `RESTRICT` semantics is added in the same file. |
| AUD-11 | `user_occupations` table is created in `2026_05_23_000001_create_user_occupations_table.php` with a partial unique index on `(user_id, occupation_id) WHERE deleted_at IS NULL`. |
| AUD-12 | These 9 columns exist on `swms_activity_steps` per `2026_05_23_000003_promote_swms_step_fields.php`: `initial_risk_level`, `residual_risk_level`, `residual_risk_reason`, `stop_work_trigger`, `evidence_required`, `evidence_prompt`, `quick_view_summary`, `primary_task_performer`, `supervisory_verification`. |
| AUD-13 | `tasks.trade_industry` is dropped in `2026_05_23_000005_normalise_tasks_taxonomy.php`. |
| AUD-14 | `import_validation_results_rule_code_prefix_check` CHECK constraint exists in `2026_05_23_000006_add_import_rule_code_prefix_constraint.php`. |
| AUD-15 | The following legacy Eloquent model files do NOT exist anywhere under `app/Domain/`: `Tenant.php`, `Business.php` legacy model, `Role.php`, `UserRole.php`, `WorkplaceUserAssignment.php`, `Activity.php`, `Submission.php`, `TaskPack.php`, `TaskPackIndustry.php`, `TaskPackOccupation.php`, `FileUpload.php`, `GeneratedDocument.php`. Do not count `BusinessEntity.php` as a legacy `Business.php`. |
| AUD-16 | `app/Domain/Shared/Tenancy/` does not exist. `app/Domain/Shared/Context/` contains `AccountContext.php`, `AccountScope.php`, and `BelongsToAccount.php`. |
| AUD-17 | These scaffold migrations are absent: `2026_04_28_212300_*`, `2026_04_28_212500_*`, `2026_05_12_100000_*`, `2026_05_12_100100_*`, `2026_05_12_100200_*`, `2026_05_14_100000_*`, `2026_05_14_100100_*`. `2026_04_28_212400_*` and `2026_05_12_110000_*` may be absent or folded into the base users migration; note which. |
| AUD-21 | `uba_permission_role_check` and `uwa_permission_role_check` CHECK constraints exist in `2026_05_23_000007_harden_access_business_industries_and_account_consistency.php`, restricting `permission_role` to `worker`, `supervisor`, `manager`, `admin`, and `platform_admin`. |
| AUD-22 | `account_businesses_one_primary` partial unique index exists on `account_businesses (account_id) WHERE is_primary = true AND deleted_at IS NULL` in the same file. |
| AUD-23 | `business_industries.is_primary` boolean default false column exists in the same file; `business_industries_one_primary` partial unique index exists; `app/Domain/OpsFortress/Industries/Models/BusinessIndustry.php` casts `is_primary` as boolean. |
| AUD-24A | Cross-account consistency triggers exist on `audit_events`, `signatures`, and `evidence_files`, sourced from `2026_05_23_000007_*.php`. |

### Group 2 — Round-2 Pending Claims, Audited Against Desired Fixed State

These are items the follow-up prompt may claim are still pending. Audit the **desired fixed state** below.

| ID | Desired fixed state |
|----|---------------------|
| AUD-18 | `database/seeders/V03DemoSeeder.php` seeds a runtime journey containing `worker_task_sessions`, `swms_step_events`, `signatures`, `prestart_submissions`, `prestart_responses`, `evidence_files`, `alerts`, `audit_events`, and `user_occupations`. |
| AUD-19 | `app/Domain/Shared/Audit/Services/AuditService.php::record()` writes `worker_task_session_id` into inserted `audit_events` rows when the subject is a `WorkerTaskSession`. |
| AUD-20A | `app/Domain/Whs/Evidence/Models/Signature.php` declares `public $timestamps = false;`. |
| AUD-20B | `app/Domain/Whs/Evidence/Models/EvidenceFile.php` declares `public $timestamps = false;`. |
| AUD-20C | Determine whether the `signatures` and `evidence_files` tables still have `updated_at` columns. Mark `NOTE` if the columns remain but the models disable timestamps; mark `FAIL` only if Eloquent can still update these append-only rows by default. |
| AUD-24B | A cross-account consistency trigger exists on `alerts` for `worker_task_session_id` and `prestart_submission_id`, or the absence is explicitly documented as a known deferred decision. |
| AUD-24C | A cross-account consistency trigger exists on `prestart_submissions.worker_task_session_id`, or the absence is explicitly documented as a known deferred decision. |
| AUD-25 | Empty legacy domain directories are absent for `app/Domain/OpsFortress/Tenancy`, `app/Domain/OpsFortress/Businesses`, `app/Domain/OpsFortress/Permissions`, `app/Domain/Whs/Activities`, `app/Domain/Whs/Submissions`, `app/Domain/Whs/TaskPacks`, and `app/Domain/Whs/Files`. |
| AUD-26 | The append-only trigger error message is parameterised via `TG_TABLE_NAME`, not hard-coded as `audit_events is append-only`. |
| AUD-27 | Check whether `database/migrations/2026_05_18_000000_enable_postgres_extensions_and_prepare_v0_3_reset.php` still drops `audit_events` and/or `user_occupations`. If yes, mark `NOTE`, not `FAIL`, unless the drop loop creates an actual migration failure. Defensive cleanup may be intentional. |
| AUD-28 | Report which of these status/severity/person-type columns have CHECK constraints: `customer_accounts.status`, `business_entities.entity_status`, `workplaces.status`, `users.status`, `users.person_type`, `swms_versions.status`, `worker_task_sessions.status`, `prestart_submissions.status`, `posttask_submissions.status`, `training_attempts.status`, `alerts.status`, `alerts.severity`, `import_batches.status`, `import_source_files.status`, `import_validation_results.severity`. Mark `NOTE` if constraints are absent but the allowed enum values are not yet product-confirmed. |
| AUD-29 | `ImportValidationResult::RULE_CODE_PATTERN` and `import_validation_results_rule_code_prefix_check` allow multi-segment namespaces such as `schema:json:missing_field`. |
| AUD-30 | `WorkplaceEnvironment` global lookup rows in `V03DemoSeeder.php` are seeded outside `AccountContext::runAs(...)`, or the seeder structure clearly prevents account-scoped side effects for lookup data. |
| AUD-31 | `V03DemoSeeder.php` imports only classes it uses, and `php vendor/bin/pint --test` passes if run. If `pint --test` is not run, report source evidence for used imports and mark `NOTE`. |

### Group 3 — Sanity Checks Not Explicitly in the Follow-Up Prompt

Mark `PASS`, `FAIL`, `NOTE`, or `NEEDS-DB`.

| ID | Claim |
|----|-------|
| AUD-32 | `app/Domain/Whs/` contains NO Eloquent model files for existing P1 tables: `posttask_questions`, `posttask_submissions`, `posttask_responses`, `training_questions`, `training_attempts`, `training_responses`, `worker_training_completions`. |
| AUD-33 | `app/Http/Middleware/SetTenantContext.php` does NOT exist. `app/Http/Middleware/SetAccountContext.php` exists and binds `AccountContext`. |
| AUD-34 | Searching `app/`, `routes/`, `config/`, and `tests/` for `TenantContext`, `BelongsToTenant`, `TenantScope`, or `Tenant::class` returns zero matches. |
| AUD-35 | `tests/Feature/Database/V03SchemaContractTest.php` asserts the trigger names `audit_events_no_update`, `signatures_no_update`, and `evidence_files_no_update`. |
| AUD-36 | `tests/Feature/Database/AppendOnlyEnforcementTest.php` exists and tests UPDATE + DELETE rejection on all three append-only tables. |
| AUD-37 | The `WorkerTaskSession` model does NOT use `SoftDeletes`; supersession should be represented through `supersedes_id`, not mutable deletes. |
| AUD-38 | The `AuditEvent` model declares `public $timestamps = false;`. |
| AUD-39 | `customer_accounts` table has soft deletes enabled, so account closure can be represented without breaking audit FK RESTRICT. |
| AUD-40 | The DBML at `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml` mentions every v0.3 application/domain table created by the live migrations. Exclude Laravel framework tables such as `migrations`, `cache`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, and password reset tables. List any missing v0.3 table. |

---

## 4. Output Format

Create exactly ONE file at `docs/ROUND2_AUDIT_2026_05_23.md` with this structure:

```markdown
# Round 2 Database Audit — 2026-05-23

> Mode: read-only.
> Source of claims: docs/CODEX_PROMPT_V0_3_AUDIT_ONLY_2026_05_23.md

## Environment

- Branch: <git branch name>
- HEAD commit: <git rev-parse HEAD short>
- Working tree clean: <yes/no>
- Follow-up prompt present: <yes/no>
- PostgreSQL available locally: <yes/no/unknown>

## Summary

| Group | PASS | FAIL | NOTE | NEEDS-DB |
|---|---:|---:|---:|---:|
| Group 1 (round-1 claimed complete) | n | n | n | n |
| Group 2 (round-2 desired fixed states) | n | n | n | n |
| Group 3 (sanity checks) | n | n | n | n |

## Findings

AUD-01 | PASS | database/migrations/2026_05_23_000004_*.php:<line> defines audit_events_block_update_delete().
AUD-02 | PASS | database/migrations/2026_05_23_000004_*.php:<line> creates audit_events_no_update, signatures_no_update, evidence_files_no_update.
...

## Surprises

List any condition that contradicts both the round-1 and round-2 prompts. Use `AUD-Sxx` IDs only if the finding is materially outside AUD-01..AUD-40.

## Recommendations

- Group 1 FAIL: recommend whether round 2 should absorb the fix or whether a hot patch is needed first.
- Group 2 PASS: recommend removing the corresponding section from `docs/CODEX_PROMPT_V0_3_FOLLOWUP_2026_05_23.md`.
- Group 2 FAIL: recommend keeping the corresponding section in the follow-up prompt.
- Group 2 NOTE: recommend human review before changing the follow-up prompt.
```

Stop after writing this file. Do not begin any fix work.

---

## 5. Anti-Patterns

- Do NOT edit `docs/CODEX_PROMPT_V0_3_FOLLOWUP_2026_05_23.md`.
- Do NOT run `migrate:fresh`.
- Do NOT trust the changelog as proof; verify actual files.
- Do NOT add audit rows beyond AUD-01..AUD-40 unless the finding is materially outside scope; use `AUD-Sxx` only in Surprises.
- Do NOT mark Group 1 as "probably PASS"; either cite evidence or mark FAIL/NEEDS-DB.
- Do NOT treat Group 2 `PASS` as "needs fixing"; Group 2 `PASS` means the desired fixed state already exists.

---

## 6. Time Budget

This audit should take well under one model session. If you are repeatedly running tests or reading far beyond the files needed for AUD-01..AUD-40, stop and reassess: this is an audit, not a repair pass.
