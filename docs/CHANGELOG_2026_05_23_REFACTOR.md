# 2026-05-23 v0.3 Schema + Docs Refactor Changelog

## Schema Migrations Added

- `2026_05_23_000000_reset_workplace_environments_as_lookup.php`
- `2026_05_23_000001_create_user_occupations_table.php`
- `2026_05_23_000002_move_blockchain_id_to_business_entities.php`
- `2026_05_23_000003_promote_swms_step_fields.php`
- `2026_05_23_000004_harden_audit_events_and_evidence.php`
- `2026_05_23_000005_normalise_tasks_taxonomy.php`
- `2026_05_23_000006_add_import_rule_code_prefix_constraint.php`
- `2026_05_23_000007_harden_access_business_industries_and_account_consistency.php`

## Decisions Resolved

The original decision log is preserved at `docs/archive/OPEN_DECISIONS_2026_05_23.md`.

- D1: `workplace_environments` is a global lookup table.
- D2: `user_occupations` provides worker-to-occupation FK integrity.
- D3: `blockchain_id` moved from `users` to `business_entities`.
- D4: 9 SWMS worker-view fields were promoted to dedicated columns.
- D5: `contractor_relationships` remains business-to-business.
- D6: `audit_events` keeps polymorphic subject fields and adds `worker_task_session_id`.
- D7: audit/evidence account FKs use restrict semantics and append-only triggers.

## Follow-Up Hardening

- `V03DemoSeeder` now creates a complete worker runtime journey: worker user, occupation, task session, SWMS step event, prestart submission/response, signature, evidence file, audit hash chain, and alert.
- `AuditService` writes `worker_task_session_id` when auditing a `WorkerTaskSession`.
- Access role values are constrained to `worker`, `supervisor`, `manager`, `admin`, and `platform_admin`.
- `account_businesses` and `business_industries` enforce one active primary row.
- Evidence/audit account consistency is enforced at the DB layer for linked worker sessions, prestarts, and signatures.
- Append-only trigger errors now report the actual table name.
- Importer `rule_code` accepts nested namespace segments such as `schema:json:missing_field`.

## Removed Files

- Legacy migrations from `2026_04_28_*`, `2026_05_12_*`, and `2026_05_14_*`.
- Orphan Eloquent models for old `tenants`, `businesses`, `roles`, `user_roles`, `workplace_user_assignments`, `activities`, `submissions`, `task_packs`, `file_uploads`, and `generated_documents`.
- Old `app/Domain/Shared/Tenancy/*` stack and `app/Http/Middleware/SetTenantContext.php`.
- Obsolete duplicate `docs/OpsFortress_MVP_ERD_v0_3_Updated.txt`; use the regenerated `.dbml` file instead.

## Documentation Updated

- `docs/README.md`
- `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml`
- `IMPLEMENTATION_ROADMAP.md`
- `MILESTONE.md`
- `docs/Role_Architecture_Notes.md`
- `docs/WHS_Architecture_Record.md`
- `docs/WHS架构分析记录.md`
- `TARGET_ARCHITECTURE.md`
- `README.md`
- `IMPORTER_INTAKE_NOTES.md`

## Documentation Archived

- `docs/archive/CODEX_PROMPT_V0_3_MIGRATIONS.md`
- `docs/archive/CODEX_PROMPT_V0_3_REFACTOR_2026_05_23.md`
- `docs/archive/GENERAL_CODEX_PROJECT_PROMPT.md`
- `docs/archive/GOOGLE_DRIVE_MARKDOWN_UPDATE_GUIDE_2026_05_17.md`
- `docs/archive/TECHNICAL_REVIEW_OPSFORTRESS_DEMO_2026_05_17.md`
- `docs/archive/DBML_FINAL_REVIEW_2026_05_17.md`
- `docs/archive/V0_3_SCHEMA_RESET_PLAN.md`
- `docs/archive/OPEN_DECISIONS_2026_05_23.md`

## Verification Run

- `php artisan migrate:fresh --seed` passed against PostgreSQL.
- `php artisan test --filter=V03SchemaContractTest` passed.
- `php artisan test --filter=V03DevSeederTest` passed.
- `php artisan test --filter=AppendOnlyEnforcementTest` passed.
- Importer tests passed for industries, occupations, and tasks.
- Full `php artisan test` passed: 71 tests / 470 assertions / 8 skipped.
- `php vendor/bin/pint --test` passed.
- `php artisan route:list` passed with 36 routes.
