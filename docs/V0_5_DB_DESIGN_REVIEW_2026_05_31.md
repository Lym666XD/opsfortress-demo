# OpsFortress v0.5 DB Design Review

Prepared: 2026-05-31  
Status: reviewed; first foundation migration implemented

## 2026-05-31 Implementation Update

The first v0.5 foundation migration has been implemented in
`database/migrations/2026_05_31_000001_create_v0_5_foundation_tables.php`.

Implemented from this review:

- nullable `swms_version_id` on `prestart_questions` and `posttask_questions`;
- `jurisdiction_regulatory_profiles`;
- `workplace_external_parties`;
- `asset_types`, `assets`, and `workplace_asset_assignments`;
- `worker_task_session_assets`;
- `generated_documents`;
- `document_delivery_events`.

Still not implemented:

- PDF generation;
- email sending;
- offline sync;
- Word Exchange transformation/import;
- External Register import;
- v0.5 workbook importers.

## Source Files Reviewed

- `docs/OpsFortress_35_Tab_Workbook_Framework_v0_5_REVISED.xlsx`
- `docs/OpsFortress_Workbook_Framework_Spec_v0_5_REVISED.xlsx`
- `docs/LUKE 14.08.24 Word Exchange - Word Exchange.csv`
- Current v0.3 Laravel migrations under `database/migrations/`
- Current roadmap files: `README.md`, `MILESTONE.md`, `IMPLEMENTATION_ROADMAP.md`, `IMPORTER_INTAKE_NOTES.md`

## Executive Recommendation

Do not broadly update the database from the v0.5 workbook.

The first DB update should remain limited to the foundation batch:

1. version-aware pre/post question template links;
2. workplace external parties and PDF recipient context;
3. jurisdiction regulatory profile lookup;
4. generated document and delivery-event records;
5. minimum asset foundations.

This foundation batch was implemented on 2026-05-31. The next product work should still avoid adding speculative PDF/offline/runtime tables until importer and worker runtime slices need them.

The v0.5 workbook is directionally good, but it mixes four different kinds of data:

- content that can be imported now;
- lookup/config data that needs new backend decisions;
- runtime rule definitions that must not be imported as real runtime records;
- future offline/PDF/asset requirements that should not be implemented before the worker runtime shape is stable.

## Current Backend State

The Laravel backend is currently a v0.3 schema-reset prototype.

Already present:

- `countries`
- `customer_accounts`
- `business_entities`
- `account_businesses`
- `business_identifiers`
- `workplaces`
- `workplace_environments`
- `industries`
- `occupations`
- `tasks`
- `task_industry_access`
- `task_occupation_access`
- `swms_versions`
- `swms_activity_steps`
- `prestart_questions`
- `posttask_questions`
- `training_questions`
- `worker_task_sessions`
- `swms_step_events`
- `prestart_submissions`
- `posttask_submissions`
- `training_attempts`
- `signatures`
- `evidence_files`
- `audit_events`
- `alerts`
- importer tracking tables
- jurisdiction/regulatory profile foundation
- workplace-level external party/PDF recipient foundation
- asset register foundation
- generated document and delivery-event foundation

Still not present:

- normalized regulator/contact/legislation/code-of-practice child tables
- PDF generation engine
- email sending integration
- offline sync tables or consistent offline sync fields
- Word Exchange transformation/import

The old `generated_documents` table was explicitly dropped during the v0.3 reset and should not be revived unchanged. A new document-output model should be designed around `swms_version_id`, workplace context, jurisdiction profile, recipients, and delivery audit trail.

## v0.5 Workbook Findings

The 35-tab workbook is mostly a locked header template. Most tabs contain only row 1 headers. That is useful for contract design, but it means importer behavior still needs to be confirmed with real rows.

The six new v0.5 tabs are:

- `OPSF_Jurisdiction_Profile`
- `OPSF_PDF_Generation_Rules`
- `OPSF_External_Parties`
- `OPSF_Asset_Register`
- `OPSF_Offline_Sync_Rules`
- `OPSF_Versioning_Rules`

The spec workbook confirms the intended importer slices:

- S0 workbook control
- S1 core SWMS content
- S2 questions
- S3 access mapping
- S4 config/JSONB
- S5 runtime/config rules
- S6 PDF/jurisdiction/external parties
- S7 assets/offline
- S8 versioning

Important issue: `Header_Lock_Register` is effectively empty. The validation checklist says row 1 headers must match this register, but the register currently has only the `canonical_tab_name` heading populated. Before production importer work, Damon should either populate this sheet or we should generate it from the workbook headers and treat that as the lock file.

## Word Exchange Findings

`LUKE 14.08.24 Word Exchange - Word Exchange.csv` is not shaped like `OPSF_Jurisdiction_Profile`.

It is a wide jurisdiction matrix:

- first column: `Word`
- 246 jurisdiction/context columns
- rows include terms such as:
  - Safe work method statement
  - SWMS
  - Standard operating procedure
  - SOP
  - Work health and safety
  - WHS
  - Regulator
  - Accident reporting
  - Act
  - Regulations

This file should not be imported directly into a relational table as-is. It needs a transform step:

```text
wide matrix
-> jurisdiction/context parser
-> term rows / regulatory profile rows
-> approved profile data used by PDF generation
```

Recommended normalized output shape:

- one jurisdiction/context profile per country/state/workplace domain;
- one set of terminology values per profile;
- regulator/contact/legislation values attached to that profile;
- source traceability retained for every generated profile.

The matrix also contains domain-prefixed columns such as `MINE - ...` and `PET/GAS - ...`. That supports Kevin's requirement that workplace type affects document terminology and regulator wording. However, the v0.5 `OPSF_Jurisdiction_Profile` tab currently has `workplace_type` but no task/category field. Kevin explicitly asked for location + workplace type + task/category. That field gap should be resolved before migration.

## DB Delta Classification

| Area | Current support | v0.5 need | Recommendation |
|---|---|---|---|
| Core SWMS content | Strong partial | `WHSAPP_SWMS_Data` to `tasks` + `swms_versions.full_swms_content` | Continue importer work; no major schema change first. |
| Worker app steps | Strong partial | v0.5 worker view fields and asset/offline prompts | Map core fields to `swms_activity_steps`; keep asset/offline prompts in metadata until asset/offline modules exist. |
| Prestart questions | Foundation present | v0.5 includes `swms_version_label`, asset fields, offline fields | `swms_version_id` has been added; asset/offline fields should remain metadata until runtime flow needs typed fields. |
| Posttask questions | Foundation present | v0.5 includes `swms_version_label`, asset status fields | `swms_version_id` has been added; asset status fields should remain metadata until runtime flow needs typed fields. |
| Training questions | Better support | already has nullable `swms_version_id` | Add/import `external_question_id` if confirmed; metadata/scoring rules can hold feedback fields. |
| Access maps | Strong | five access fields already exist | Implement M17.4 next. |
| Jurisdiction/regulator | Foundation present | regulatory block by location/workplace type/task | `jurisdiction_regulatory_profiles` now exists; Word Exchange transformation remains future work. |
| PDF distribution | Foundation present | PC/Client recipient and PDF rules | `generated_documents` and `document_delivery_events` now exist; PDF generation/email sending remain future work. |
| External parties | Foundation present | workplace Principal Contractor/Client and external register separation | `workplace_external_parties` now covers workplace-level PDF/reporting responsibility. |
| Assets | Foundation present | asset type vs specific asset item | `asset_types`, `assets`, workplace assignments, and session asset selections now exist. |
| Offline | Partial metadata only | local ID, device, sync status, conflict rules | Do not build full offline DB now; design with worker/PWA runtime. |
| Versioning | Partial | in-progress sessions keep original version; PDFs record version | Keep `worker_task_sessions.swms_version_id`; add document output version references later. |

## Recommended Work Order

### Step 1 - Foundation Migration Only

Complete these first:

- populate or generate `Header_Lock_Register`;
- update `IMPORTER_INTAKE_NOTES.md` with v0.5 intake findings;
- finish M17.4 access map importers;
- confirm unresolved workbook questions with Damon/Kevin.

The foundation migration has been implemented. The remaining items still
matter before production v0.5 importers are built.

### Step 2 - M17.4 Access Map Importers

Implement next because current schema already supports it:

- `RAW_All_Task_Occupation_Access` -> `task_occupation_access`
- `RAW_All_Task_Industry_Access` -> `task_industry_access`

Required behavior:

- resolve `task_id` to `tasks.external_task_id`;
- resolve `occupation_id` / occupation candidate key to `occupations`;
- resolve `industry_id` / industry candidate key to `industries`;
- coerce access values to `full`, `conditional`, `supervised`, `none`;
- record validation results for missing FK targets, duplicate rows, and unsupported access values.

This is still the cleanest next coding task because it is part of the existing roadmap and is not blocked by v0.5 PDF/offline decisions.

### Step 3 - v0.5 Foundation Migration Batch

Implemented as the first v0.5 migration batch:

1. Versioned question templates:
   - nullable `swms_version_id` on `prestart_questions`;
   - nullable `swms_version_id` on `posttask_questions`;
   - unique rules compatible with old task-level questions.

2. Workplace party assignment:
   - `workplace_external_parties`;
   - flexible party type field for Principal Contractor, Client, Subcontractor, Consultant, Supplier, Regulator, Other;
   - PDF recipient flag;
   - reporting responsibility;
   - contact details or link to a contact table.

3. Jurisdiction regulatory profiles:
   - start with one pragmatic `jurisdiction_regulatory_profiles` table;
   - include country/state/workplace type/task category fields;
   - include regulator/contact fields;
   - keep legislation/codes as JSONB initially unless Kevin confirms enough structure to normalize them.

4. Generated document records:
   - `generated_documents`;
   - `document_delivery_events`;
   - must reference `swms_version_id`, `workplace_id`, jurisdiction profile, file path/hash, and delivery status.

This batch supports PDF/versioning without building the full PDF engine yet.

### Step 4 - Asset Foundation

Implemented as a minimum asset model:

- `asset_types`
- `assets`
- `workplace_asset_assignments`
- `worker_task_session_assets`

Do not immediately add many direct `asset_id` columns across runtime tables.
The first runtime link is a pivot, `worker_task_session_assets`, so a task can
use one or more assets without forcing that decision into every submission,
evidence, or audit table.

Evidence/prestart/posttask links can then reference the session and asset context cleanly.

### Step 5 - Offline Design Later

Keep `OPSF_Offline_Sync_Rules` as config/design for now.

Do not add full offline sync fields to every runtime table until the worker mobile/PWA flow exists. Premature offline schema will likely be wrong.

Future direction:

- client-generated local IDs;
- device identity;
- offline-created timestamp;
- server sync timestamp;
- sync status;
- conflict/error payload;
- retry count;
- audit trail for sync events.

This can be implemented either as common columns on runtime tables or as a dedicated `offline_sync_records` table. Decide when the frontend sync architecture is known.

## Proposed Table Direction

### `workplace_external_parties`

Purpose: workplace-level Principal Contractor / Client / recipient assignment.

Likely fields:

- `id`
- `account_id`
- `business_entity_id`
- `workplace_id`
- `external_business_entity_id` nullable
- `external_party_type`
- `business_name`
- `business_identifier`
- `contact_given_name`
- `contact_family_name`
- `contact_role`
- `contact_email`
- `contact_phone`
- `reporting_responsibility`
- `pdf_recipient`
- `effective_from`
- `effective_to`
- `metadata`
- timestamps / soft deletes

Note: this should stay separate from the broader External Register source. The broad register may later feed `business_entities` or a supplier/contractor register, but the workplace-level table answers the PDF/reporting responsibility question.

### `jurisdiction_regulatory_profiles`

Purpose: resolve document terminology and regulatory block by location/workplace type/task category.

Likely fields:

- `id`
- `profile_code` or `external_jurisdiction_profile_id`
- `country_code`
- `country_name`
- `state_territory_province`
- `workplace_type`
- `task_category` nullable but recommended
- `document_type_name`
- `document_type_acronym`
- `safety_terminology_name`
- `safety_terminology_acronym`
- `regulator_name`
- `regulator_contact_name`
- `regulator_phone`
- `regulator_email`
- `regulator_website`
- `emergency_reporting_contact`
- `incident_reporting_instructions`
- `legislation_references`
- `codes_of_practice`
- `source_reference`
- `last_reviewed_at`
- `active_status`
- `metadata`
- timestamps

Start with JSONB for legislation/codes. Normalize into separate legislation/code tables after the source data proves stable.

### `generated_documents`

Purpose: compliance output record, not source of truth.

Likely fields:

- `id`
- `account_id`
- `business_entity_id`
- `workplace_id`
- `task_id` nullable for bundled PDFs
- `swms_version_id` nullable for bundled PDFs
- `jurisdiction_regulatory_profile_id` nullable
- `document_type`
- `document_title`
- `version_label`
- `generation_trigger`
- `bundle_key` nullable
- `status`
- `disk`
- `path`
- `file_hash`
- `file_hash_algorithm`
- `generated_by_user_id`
- `generated_at`
- `metadata`
- timestamps

For email/distribution audit trail, add either:

- `generated_document_recipients`, or
- `document_delivery_events`.

### Asset Tables

Minimum likely set:

- `asset_types`
- `assets`
- `workplace_asset_assignments`

Potential runtime link:

- `worker_task_session_assets`

Do not overbuild maintenance/defect tables until the product flow requires them.

## Importer Implications

v0.5 importer should remain allow-list driven.

Recommended implementation path:

1. S0: workbook control/header validation.
2. S3: access maps, because schema exists.
3. S1: SWMS content and worker app steps.
4. S2: prestart/posttask/training questions.
5. S6: jurisdiction/external party/PDF only after foundation tables exist.
6. S7/S8: assets/offline/versioning rules only after backend decisions.

Runtime boundary remains mandatory:

- never import real `worker_task_sessions`;
- never import real `signatures`;
- never import real `evidence_files`;
- never import real `audit_events`;
- never import real `alerts`.

Excel defines content, templates, rules, and lookup data. Laravel creates runtime records from real users and real events.

## Questions To Confirm Before Migration

1. Should `OPSF_Jurisdiction_Profile` include `task_category` or `task_group`? Kevin's latest requirement says location + workplace type + task/category.
2. Should legislation and codes of practice start as JSONB in one profile table, or as normalized child tables immediately?
3. What are the minimum v0.5 external party types? Suggested minimum: Principal Contractor, Client, Subcontractor, Consultant, Supplier, Regulator, Other.
4. Is a Principal Contractor / Client a workplace assignment, a project assignment, or both?
5. Is "project/site" always represented by `workplaces`, or do we need a separate project/site layer later?
6. Does each worker task session select one asset, or can a task use multiple assets?
7. Should prestart/posttask question templates be versioned by `swms_version_id` now?
8. Should `generated_documents` be created before the PDF engine, purely as a future output record?
9. Is the Word Exchange CSV the approved source of truth for terminology/regulator details, or is it only an intermediate draft?
10. Who owns regulator update review and `last_reviewed_at` approval?

## Final Position

The database has now been updated only with the v0.5 foundation batch.

The highest-value immediate engineering work remains the current importer roadmap: finish access map importers, then move into SWMS content importers.

For v0.5, avoid further broad database work until importer and runtime needs are concrete. The implemented foundation now covers:

- versioned question template support;
- workplace party/PDF recipient assignment;
- jurisdiction regulatory profile lookup;
- generated document records;
- asset foundation.

Offline should stay at design/config level until the worker mobile/PWA runtime is underway.
