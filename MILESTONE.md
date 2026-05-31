# OpsFortress Demo Milestones

## Status Legend

- `todo`
- `in-progress`
- `done`
- `blocked`

## Current Milestones

| ID | Milestone | Status | Notes |
|---|---|---|---|
| M1-M8 | Laravel / React scaffold and WHS preview shell | done | Base Laravel 13 + Inertia stack, auth starter flows, and preview routes exist. |
| M15 | v0.3 schema reset migration pass | done | `2026_05_18_*` migrations generated and verified against PostgreSQL. |
| M16 | Port backend infrastructure to v0.3 | done | UUID users, account context/scope, v0.3 models, audit service, seeder, and schema tests. |
| M16.1 | Reconcile schema to authoritative DBML | done | `account_id` naming, task/industry/occupation taxonomy, access enums, and nullable country ISO columns. |
| M16.2 | 2026-05-23 schema hardening | done | Added D1-D7 decisions, runtime journey seed, append-only enforcement, role checks, primary-row constraints, and cross-account link triggers. |
| M16.3 | v0.5 foundation schema | done | Added jurisdiction/regulatory profiles, workplace external parties, asset foundation, generated document/delivery records, and version-aware pre/post question templates. |
| M17.1 | Importer framework + industries | done | SRC-001 `RAW_All_Industry_Master`. |
| M17.2 | Occupations importer | done | SRC-001 `RAW_All_Occupation_Master`. |
| M17.3 | Tasks importer + shared importer traits | done | SRC-001 `RAW_All_Task_Register`. |
| M17.4 | Access map importers | in-progress | Next target: `RAW_All_Task_Occupation_Access` and `RAW_All_Task_Industry_Access`. |
| M17.5 | SWMS workbook importer | todo | Target SRC-002/003/004 SWMS workbooks. |
| M17.6 | Global business identifiers importer | todo | Target SRC-005 countries and business identifier types. |
| M18 | Worker runtime from imported data | todo | Use imported task/SWMS/prestart content to create session, events, signature, evidence, audit, and alert records. |
| M19 | v0.3 admin UI restart | todo | Rebuild against account/business/workplace/task schema, not the removed v0.2 admin slice. |

## Current Product State

Implemented:

- v0.3 PostgreSQL migrations aligned to the regenerated DBML.
- Account-scoped backend context through `AccountContext`, `AccountScope`, and `BelongsToAccount`.
- Hash-chained `AuditService` writing `audit_events.worker_task_session_id` for worker sessions.
- Database append-only triggers for `audit_events`, `signatures`, and `evidence_files`.
- Cross-account consistency triggers for audit/evidence/signature links.
- v0.5 foundation DB tables for regulatory lookup, workplace PDF recipients, assets, generated document records, and document delivery events.
- Nullable `swms_version_id` on prestart/posttask question templates for version-aware imports.
- Idempotent `V03DemoSeeder` with admin and worker demo users plus a complete runtime journey.
- Importer framework with industries, occupations, and tasks slices.
- PostgreSQL schema, seeder, append-only, and importer integration tests.

Not implemented yet:

- Access map importers.
- SWMS workbook importers.
- Global business identifier importer.
- Runtime flow generated from imported workbook content.
- v0.3 admin policies/controllers/pages.
- Worker mobile task UI.
- PDF generation engine, dashboards, corrective actions, PWA/offline sync runtime.

## Immediate Next Actions

1. Build M17.4 access map importers:
   - `RAW_All_Task_Occupation_Access`
   - `RAW_All_Task_Industry_Access`
2. Resolve FK lookups from source strings to UUIDs:
   - `task_id` -> `tasks.id`
   - `occupation_id` -> `occupations.id`
   - `industry_id` -> `industries.id`
3. Coerce access values into the v0.3 enum:
   - `Yes` -> `full`
   - `Conditional` -> `conditional`
   - `Show supervised` -> `supervised`
   - `Management only` -> `supervised`
   - blank/unknown values -> validation result
4. Add importer tests for missing FK targets, duplicate source rows, and unsupported access values.
5. After M17.4, continue to SWMS workbook importers and prove the first worker runtime path from imported content.

## Verification

Latest local verification on 2026-05-31:

- `php artisan migrate:fresh --seed` passed.
- `php artisan migrate:rollback --step=1 && php artisan migrate` passed.
- `php artisan test` passed against PostgreSQL: 78 tests / 672 assertions / 8 skipped.
- `php vendor/bin/pint --test` passed.
- `php artisan route:list` passed with 36 routes.

## Superseded History

The old M8.5-M14 tenant-era and v0.2 admin-workplace milestone details are no longer active planning inputs. They are retained in git history and in archived context documents, but new work should use the v0.3 account/business/workplace/task/runtime model.
