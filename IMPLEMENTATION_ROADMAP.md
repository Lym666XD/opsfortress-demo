# OpsFortress Implementation Roadmap

## Purpose

This roadmap defines the active delivery order for the Laravel rebuild of WHSAPP on top of the OpsFortress platform model.

It is intentionally current-state focused. Historical prompt, review, and schema-reset planning files now live under `docs/archive/`.

## Guiding Principles

1. Build from the v0.3 schema, not the removed tenant-era scaffold.
2. Load real workbook content through importers before expanding UI.
3. Separate platform identity, WHS content, worker runtime, evidence, and output.
4. Keep account/business/workplace scoping explicit in database constraints, models, and tests.
5. Prove one vertical workflow end-to-end before broad module expansion.

## Locked Decisions

- **Schema authority:** `database/migrations/2026_05_18_*`, `database/migrations/2026_05_23_*`, `database/migrations/2026_05_31_*`, and `docs/OpsFortress_MVP_ERD_v0_3_Updated.dbml`.
- **Row isolation:** use `AccountContext`, `AccountScope`, and `BelongsToAccount`; the old `tenant_id` / `BelongsToTenant` stack is superseded.
- **Business model:** `customer_accounts` own access scope; `business_entities` represent legal entities; `account_businesses` links them.
- **Task content model:** use `tasks`, `swms_versions`, `swms_activity_steps`, prestart tables, and access maps; do not revive `task_packs`.
- **Evidence integrity:** `audit_events`, `signatures`, and `evidence_files` are append-only at the database layer.
- **Importer validation:** `import_validation_results.rule_code` uses namespaced prefixes such as `schema:*`, `structure:*`, `fk:*`, `business:*`, and `dup:*`.
- **Roles:** `users.person_type` / `users.contractor_type` store identity; `user_business_access.permission_role` and `user_workplace_access.permission_role` store permissions.

## Delivery Phases

### Phase 1: Schema Baseline

Status: `done`

Scope:

- v0.3 migrations
- regenerated DBML
- schema contract tests
- account/business/workplace/user/task/runtime/evidence tables
- append-only and cross-account consistency constraints
- v0.5 foundation tables for jurisdiction/regulatory lookup, workplace external parties, assets, generated documents, delivery events, and version-aware pre/post question templates

Exit criteria:

- `php artisan migrate:fresh --seed` passes
- schema tests prove key tables, columns, indexes, triggers, and constraints

### Phase 2: Importer Foundation

Status: `in-progress`

Completed:

- importer batch/source/validation tables
- workbook reader
- import runner
- CLI entrypoint
- industries importer
- occupations importer
- tasks importer
- shared value-normalisation and validation-result traits

Next:

- task occupation access importer
- task industry access importer
- SWMS workbook importers
- global business identifier importer

Exit criteria:

- approved source workbooks can load into canonical v0.3 tables with deterministic validation results
- importer tests cover clean import, idempotency, missing required fields, duplicate identity rows, missing FK targets, and unsupported enum values

### Phase 3: Worker Runtime Proof

Status: `todo`

Scope:

- create a worker task session from imported content
- render SWMS worker-view steps from imported `swms_activity_steps`
- capture SWMS step events
- capture prestart submission/responses
- capture signature and evidence file rows
- write audit hash-chain events
- create alert rows for warning/exception cases

Exit criteria:

- runtime data is generated from imported workbook content, not only from seed data
- append-only and cross-account DB protections remain green in tests

### Phase 4: Admin and Worker UI

Status: `todo`

Scope:

- v0.3 admin pages for accounts, business entities, workplaces, users, and task settings
- worker mobile flow for task start, SWMS acknowledgement, prestart, signature, and evidence
- policies/FormRequests wired to account/business/workplace access

Exit criteria:

- UI writes only through v0.3 tables
- no route depends on removed v0.2 admin/workplace tables or tenant-era models

### Phase 5: Compliance Output

Status: `todo`

Scope:

- PDFs
- dashboards
- corrective actions
- post-task workflows
- training records
- notification/escalation paths
- PWA/offline groundwork

Exit criteria:

- compliance output can be derived from stored runtime/evidence/audit data
- generated output does not become the source of truth

## Immediate Next Actions

1. Build M17.4 access map importers.
2. Add FK-resolution and enum-coercion tests.
3. Extend importer documentation with accepted access values and failure modes.
4. Continue to SWMS workbook importers after access maps are stable.
5. Prove the first worker runtime path from imported content.

## Anti-Patterns to Avoid

- Do not model one legacy AppSheet app as one Laravel module.
- Do not create one table per spreadsheet without abstraction.
- Do not bury workflow branching rules inside React components.
- Do not revive tenant-era `tenants`, `task_packs`, `activities`, or generic `submissions.payload` as production truth.
- Do not expand frontend/admin workflows before imported data can drive the backend reliably.

## Final Sequence

```text
Schema -> Importer -> Content -> Worker Runtime -> Evidence/Audit -> Output -> UI Expansion
```
