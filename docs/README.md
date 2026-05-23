# Documentation Index

## Active Documents

Use these documents for current work:

- `../README.md` — repo overview, local run, verification, and source-of-truth order
- `../MILESTONE.md` — current milestone status and immediate next actions
- `../IMPLEMENTATION_ROADMAP.md` — active delivery sequence
- `../TARGET_ARCHITECTURE.md` — target architecture and anti-patterns
- `../IMPORTER_INTAKE_NOTES.md` — importer source-file notes, rule-code namespaces, and open importer questions
- `CHANGELOG_2026_05_23_REFACTOR.md` — audit trail for the v0.3 schema/docs refactor
- `OpsFortress_MVP_ERD_v0_3_Updated.dbml` — regenerated visual schema reference
- `Role_Architecture_Notes.md` — active role/identity/access interpretation

## Historical Context

These files are valuable business and product context, but they are not schema authority:

- `WHS_Architecture_Record.md`
- `WHS架构分析记录.md`
- `MEETING_NOTES_2026_05_17_WHSAPP_OPSFORTRESS.md`

If these files conflict with current migrations or DBML, treat the migrations and DBML as authoritative.

## Archived Execution Inputs

Completed prompts, reviews, schema-reset plans, and one-time update guides live in `archive/`.

They are preserved for traceability, but should not be used as active implementation instructions unless a newer active document explicitly points to them.

## Current Source of Truth

For schema questions, use this order:

1. `../database/migrations/2026_05_18_*`
2. `../database/migrations/2026_05_23_*`
3. `OpsFortress_MVP_ERD_v0_3_Updated.dbml`
4. `../tests/Feature/Database/V03SchemaContractTest.php`
5. `../database/seeders/V03DemoSeeder.php`
