# OpsFortress 29-Tab Workbook v0.3 — Database Acceptance Review

## Overall Assessment

The 29-tab workbook framework is directionally correct and aligns well with the current OpsFortress / WHSAPP database direction.

However, the current backend **cannot directly import the full workbook yet**.

The database can accept the core content layer, but several tabs need transformation, some should remain configuration-only, and some would require future schema or importer work.

## Current Importer Limitation

The current Laravel importer only supports three SRC-001 tabs:

- `RAW_All_Industry_Master`
- `RAW_All_Occupation_Master`
- `RAW_All_Task_Register`

The new 29-tab workbook uses new sheet names such as:

- `WHSAPP_SWMS_Data`
- `WHSAPP_Worker_App_View_Map`
- `WHSAPP_PreStart_SWMS_15`
- `OPSF_Task_Industry_Access`
- `OPSF_Task_Occupation_Access`

Therefore, the current workbook requires new importer slices before it can be processed.

---

## Tab-by-Tab Review

| # | Tab | Current DB Acceptance | Mapping | Gaps | Recommendation |
|---:|---|---|---|---|---|
| 1 | `README` | Yes, read only | Workbook metadata | No DB target | Keep control-only. |
| 2 | `WHSAPP_Index` | Yes, read only | Canonical sheet map | Importer does not use it yet | v0.4 importer should resolve sheets through this tab. |
| 3 | `OPSF_Laravel_Table_Map` | Yes, read only | Import mapping spec | Not production data | Use as importer contract, not DB data. |
| 4 | `OPSF_Validation_Rules` | Partial | Validation results map to `import_validation_results` | No validation-rule definition table | Keep config-only unless dynamic DB rules are required. |
| 5 | `OPSF_Import_Order` | Partial | Import sequencing | No import-order definition table | Read by importer code, not direct DB import. |
| 6 | `WHSAPP_SWMS_Data` | Yes, with transform | `tasks` + `swms_versions` | `task_description` has no typed DB column; control sections likely go to JSONB | Good primary SWMS/PDF source. |
| 7 | `WHSAPP_Worker_App_View_Map` | Yes, with transform | `swms_activity_steps` | Headers differ from DB columns | Map to step title, instruction, hazards, controls, minimum read seconds. |
| 8 | `WHSAPP_PreStart_SWMS_15` | Yes, with transform | `prestart_questions` | Rich guidance fields need JSON mapping | Importable if scoring/guidance JSON schema is defined. |
| 9 | `WHSAPP_PostTask_SWMS_15` | DB ready, code incomplete | `posttask_questions` | No importer/model coverage yet | DB can accept it; backend code must be added. |
| 10 | `WHSAPP_Training_SWMS` | Partial | `training_questions` | No `external_question_id`; feedback fields need JSONB/metadata | Add external id or define metadata mapping. |
| 11 | `WHSAPP_Activity_Register` | Partial | `swms_activity_steps` | No dedicated activity table | Use for worker steps now; normalize later if analytics require it. |
| 12 | `WHSAPP_Hazard_Register` | JSONB only | `swms_activity_steps.hazards` or `swms_versions.full_swms_content` | No hazard table | Store as JSON now; normalize later if needed. |
| 13 | `WHSAPP_Control_Register` | JSONB only | `swms_activity_steps.controls` or `swms_versions.full_swms_content` | No control table | Store as JSON now; normalize later if needed. |
| 14 | `WHSAPP_Control_Hazard_Link_Map` | No direct table | Future hazard/control link layer | No link table | Keep as validation/config for now. |
| 15 | `WHSAPP_Critical_Control_Verif` | Rule config only | Prestart/posttask/training rules | No dedicated rule table | Keep as configuration; runtime verification should be app-generated. |
| 16 | `Industries` | Yes, with transform | `industries` | Workbook field names differ from DB | Map to `industry_group`, `industry_sub_group`, `industry_leaf`. |
| 17 | `Position or Role` | Yes, with transform | `occupations` + access metadata | Access fields do not belong entirely in `occupations` | Split occupation master data from access mapping. |
| 18 | `OPSF_Task_Industry_Access` | Yes, but header too coarse | `task_industry_access` | DB requires five access columns, not one `access_permission` | Split into five access fields or define default mapping. |
| 19 | `OPSF_Task_Occupation_Access` | Yes, but header too coarse | `task_occupation_access` | DB requires five access columns, not one `access_permission` | Split into five access fields or define default mapping. |
| 20 | `Management` | Config/reference only | User/access policy reference | No management-role config table | Do not import yet. |
| 21 | `WHSAPP_Role_Permissions` | Config only | Policy/access reference | No role-permission matrix table | Use config/code first; do not write to access rows directly. |
| 22 | `WHSAPP_Task_Training_Link_Map` | Partial | `training_questions.task_id`, metadata | No dedicated task-training link table | Simple links are possible; complex retraining rules need JSONB or a new table. |
| 23 | `WHSAPP_Activity_Training_Link` | No proper table | Future activity/training relationship | No activity table | Keep config-only until activity layer is defined. |
| 24 | `WHSAPP_Dashboard_Data_Map` | Config only | Dashboard/API layer | No dashboard config table | Do not import to DB yet. |
| 25 | `WHSAPP_Dashboard_KPIs` | Config only | Dashboard/API layer | No KPI definition table | Do not import to DB yet. |
| 26 | `WHSAPP_Alert_Rules` | Runtime-rule only | Alert rule configuration | DB has `alerts`, but those are real runtime records | Do not import as `alerts`. |
| 27 | `WHSAPP_Photo_Evidence` | Runtime-rule only | Evidence prompt/rule configuration | DB has `evidence_files`, but those are real evidence records | Do not import as `evidence_files`. |
| 28 | `WHSAPP_Digital_Signatures` | Runtime-rule only | Signature rule configuration | DB has `signatures`, but those are real signature records | Do not import as `signatures`. |
| 29 | `WHSAPP_Audit_Events` | Runtime-rule only | Audit rule configuration | DB has `audit_events`, but those are real hash-chain records | Never import real audit rows from Excel. |

---

## Key Mapping Corrections Required

### Industries

Current workbook/spec mapping should not target `industries.name`.

Use:

- `industry_level_1` → `industries.industry_group`
- `industry_level_2` → `industries.industry_sub_group`
- `industry_leaf_value` → `industries.industry_leaf`

### Occupations / Position or Role

Current workbook/spec mapping should not target `occupations.name`.

Use:

- `occupation_group` or role group → `occupations.occupation_group`
- `occupation_family` or sub-group → `occupations.occupation_sub_group`
- `role_value` / `occupation_leaf_value` → `occupations.occupation_leaf`

### Task Access Maps

The workbook currently uses a single `access_permission` field.

The database requires five separate fields:

- `swms_view_access`
- `pre_start_access`
- `post_task_access`
- `training_access`
- `menu_visibility`

Allowed values are:

- `full`
- `conditional`
- `supervised`
- `none`

The v0.4 workbook should either:

1. split `access_permission` into the five DB-aligned columns; or
2. define a deterministic mapping rule that expands one source value into five DB fields.

Splitting into five columns is the cleaner importer contract.

---

## Runtime Boundary

This boundary should be locked in the v0.4 contract:

Excel may define:

- content
- questions
- access rules
- dashboard rules
- alert rules
- evidence prompts
- signature requirements
- audit-event rule definitions

Excel must not import real worker runtime records.

The following records must be generated by the WHS App at runtime:

- `worker_task_sessions`
- `swms_step_events`
- `prestart_submissions`
- `prestart_responses`
- `posttask_submissions`
- `posttask_responses`
- `training_attempts`
- `training_responses`
- `signatures`
- `evidence_files`
- `alerts`
- `audit_events`

This is important because real runtime records need timestamps, user identity, device context, evidence files, signatures, and audit hash-chain integrity.

---

## Kevin Feedback Fields

Kevin’s requested fields are valid and should be included in the v0.4 importer contract.

### Training

Recommended fields:

- `feedback_if_incorrect`
- `learning_message`
- `safety_reason`

Suggested DB handling:

- store in `training_questions.scoring_rules`, `training_questions.metadata`, or a future structured learning-feedback schema.

### Pre-Start and Post-Task

Recommended fields:

- `n_a_eligibility`
- `scoring_logic`
- `failed_response_action`
- `corrective_action_trigger`
- `why_it_matters`
- `common_mistake_to_avoid`
- `quick_tip`

Suggested DB handling:

- store in `prestart_questions.scoring_rules` / `metadata`
- store in `posttask_questions.scoring_rules` / `metadata`

These fields support interactive risk-control checks rather than simple checklist completion.

---

## Recommended v0.4 Contract Decisions

Before this becomes a code-level importer contract, confirm:

1. Final Excel sheet names and canonical tab names.
2. Final column names for every direct-import tab.
3. Whether access maps will use five DB-aligned access columns.
4. Whether `training_questions` needs an `external_question_id`.
5. Whether `task_description` should become a typed DB column or stay in metadata/full content JSON.
6. Whether hazard/control registers remain JSONB or require normalized tables.
7. Where dashboard, alert, evidence, signature, and audit rules should live.
8. Exact FK lookup rules for:
   - `task_id`
   - `industry_id`
   - `occupation_id`
   - `swms_version_id`
   - `training_question_id`
9. Exact upsert rules:
   - duplicate task IDs
   - duplicate question numbers
   - duplicate access map rows
   - version replacement vs new version creation
10. Runtime boundary: Excel rules/config only; app generates runtime records.

---

## Final Verdict

The current database can accept the **core content layer** of the 29-tab framework, but the full workbook is not yet directly importable.

The framework is suitable as the starting point for a **v0.4 importer contract**, provided the following are locked first:

- field-level Excel-to-DB mapping;
- access-map column structure;
- JSONB vs normalized table decisions;
- config/rule-only tab handling;
- runtime record boundary;
- FK lookup and upsert rules.

In short:

> The 29-tab framework is a strong product and importer foundation, but it should be refined into a stricter v0.4 technical mapping before Laravel importer implementation.
