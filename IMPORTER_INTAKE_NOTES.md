# Importer Intake Notes

> Living log of issues, open questions, and conventions for ingesting
> source workbooks into the v0.3 importer (M17). Update as new workbooks
> arrive or decisions are confirmed.

## Purpose

When Kevin / Damon hand us a new source workbook to import, this file
captures:

1. What's in the file (sheets, headers, row counts).
2. Whether it conforms to the Importer Source File Index allow-list
   (`.localdoc/OpsFortress_MVP_Importer_Source_File_Index_for_Yiming_v0_1_Clean.xlsx`).
3. Inconsistencies / data quirks that need a decision before we can
   write a `TabImporter` against it.
4. Open questions we need Kevin or Damon to answer.

This document is **not** authoritative — the DBML
(`.localdoc/OpsFortress_MVP_ERD_v0_3_Updated.txt`) and the column
mapping (`.localdoc/OpsFortress_MVP_Column_Level_Mapping_v0_3_Clean.xlsx`)
are. This file just records where reality has diverged from the spec
and what we propose to do about it.

## Status of M17 importer slices

| Slice | Source | Status | Commit |
|---|---|---|---|
| 1 — Industries | SRC-001 `RAW_All_Industry_Master` | done | `0caf6ec` |
| 2 — Occupations | SRC-001 `RAW_All_Occupation_Master` | done | `303f8d1` |
| 3 — Tasks + trait extraction | SRC-001 `RAW_All_Task_Register` | done | `0beca34` |
| 4 — Access maps | SRC-001 `RAW_All_Task_Occupation_Access` + `RAW_All_Task_Industry_Access` | **blocked — see Open Question A below** | — |
| 5 — SWMS workbook | SRC-002/003/004 (`SWMS_*.xls(m)`) | **blocked — see Open Questions B, C, D below** | — |
| 6 — Global Business Identifiers | SRC-005 `Global Business Identifiers.xlsx` | not started | — |

## Preferred data delivery format

Going forward, when a new workbook needs to drive importer logic, the
preferred handoff is:

1. The original `.xlsx` / `.xlsm` (for traceability and audit).
2. **CSV exports of the approved tabs**, named after the tab and placed
   in `.localdoc/csv/<workbook-stem>/<TabName>.csv`.

CSV is preferred for review and importer-test fixtures because:

- What we see is what the importer sees. No formula caching, no hidden
  rows, no conditional-formatting surprises, no macro-enabled content
  warnings to click past.
- `git diff` becomes meaningful when source content changes (XLSX is
  binary).
- Either Claude / Codex / a developer can read the CSVs without
  needing Excel installed.

CSV exports are not committed to git (`.localdoc/` is gitignored). They
are local working copies.

## Workbook intake log

### 2026-05-23 — `WHS_App_OpsFortress_SWMS_Only_Mixing_of_mortar_v4_user_example_aligned.xlsm`

Received from Kevin as a sample SWMS workbook aligned to v0.3 spec.
Filename says `v4_user_example_aligned` — an aligned-to-spec template
moving toward production format, not a real customer workbook.

**File shape**: 73 sheets. The four P0 SWMS tabs from the Importer
Source File Index are all present and noticeably richer than the older
SRC-002/003/004 samples.

| Tab | Non-empty rows | Cols | Notes |
|---|---|---|---|
| `WHSAPP_Task_Register` | 1 task | 34 | Same shape as SRC-001 Task Register |
| `WHSAPP_SWMS_Data` | 1 row (wide) | 23 | Formal SWMS content; 23 cols of safety controls + activity risk assessments |
| `WHSAPP_Worker_App_View_Map` | **11 steps** (older samples: 10) | **34** (older samples: 12) | Adds `quick_view_summary`, `initial_risk_level` / `residual_risk_level` / `residual_risk_reason`, `linked_assets` / `linked_hazardous_chemicals` / `linked_permit_triggers` / `linked_critical_controls`, `primary_task_performer` / `supporting_role` / `supervisory_verification`, `verification_method`, `worker_burden_basis`, `consequences_from_nw`, `hazard_description_from_nw`, `checks_verification`, etc. |
| `WHSAPP_PreStart_SWMS_15` | 15 questions | 16 (older: 12) | Adds `scoring_value`, `n_a_eligibility`, `failed_response_action`, `corrective_action_trigger`, `linked_activity_id` |

**New (not in older SRC-002/003/004 samples)**:

- `OPSF_Task_Occ_Access` (16 rows, 18 cols) — workbook-local task-to-occupation access map
- `OPSF_Task_Ind_Access` (18 rows, 18 cols) — workbook-local task-to-industry access map
- `OPSF_Laravel_Table_Map` (1 row, 18 cols) — finally populated (was empty in SRC-001)
- Front-of-workbook lookup sheets: `Industries`, `Management`, `Position or Role`
- Many additional `WHSAPP_*` and `WHSAPP_CtrSup_*` registers (non-conformance, contractor/supplier evidence, doc review, comply, exception, etc.) — out of scope for current M17 slices

## Open Questions (blocking)

These are concrete questions we need answered before Slices 4 or 5 can
ship. Tag with the date the question was raised and the date it was
answered.

### A. `task_id` naming is inconsistent — across files AND within this workbook

Raised: 2026-05-23. Status: **open**.

Same logical task "Mixing of mortar" carries different external IDs in
different places:

| Source | `task_id` value |
|---|---|
| SRC-001 `RAW_All_Task_Register` | `SWMS_MIXING_OF_MORTAR_001` |
| SRC-001 `RAW_All_Task_Occupation_Access` | `SWMS_MIXING_OF_MORTAR_001` |
| v4 workbook `WHSAPP_Task_Register` | `MIXING_OF_MORTAR` |
| v4 workbook `WHSAPP_Worker_App_View_Map` | `MIXING_OF_MORTAR` (matches Task Register ✓) |
| v4 workbook `OPSF_Task_Occ_Access` | `ACC-001` (!) |
| v4 workbook `OPSF_Task_Ind_Access` | `ACC-001` (!) |

The v4 workbook's own access tabs do not agree with its Task Register
on what `task_id` means. The `ACC-*` values look like access-record IDs
(parallel to `TOA-*` / `TIA-*`) that got placed in the `task_id` column
by mistake — but that needs Kevin's confirmation.

If real workbooks ship with this inconsistency, the access importer
(Slice 4) needs a name-based or candidate-key fallback resolver instead
of strict FK-by-external-id, which is significantly heavier to build
and validate.

**What we need**:

- [ ] Kevin to confirm whether `ACC-001` in v4 `OPSF_Task_Occ_Access.task_id`
      is a data bug (correct value should be `MIXING_OF_MORTAR`) or
      intentional (in which case explain what `ACC-*` represents).
- [ ] Kevin to confirm the long-term naming convention for `task_id`:
      `SWMS_MIXING_OF_MORTAR_001` (verbose) vs `MIXING_OF_MORTAR`
      (terse) — we cannot have both forever.

### B. Two sources of access data — which is authoritative?

Raised: 2026-05-23. Status: **open**.

The Importer Source File Index says:

- **SRC-001 (Central Source Pack)** is authoritative for `occupations`,
  `industries`, `task_occupation_access`, `task_industry_access`.
- **SRC-002/003/004 (SWMS workbooks)** are authoritative only for
  `tasks`, `swms_versions`, `swms_activity_steps`, `prestart_questions`.

The v4 workbook breaks that boundary by carrying its own
`OPSF_Task_Occ_Access` + `OPSF_Task_Ind_Access` tabs. If SRC-001 and
this workbook disagree (and they will), which wins?

Three positions:

- **A — SRC-001 always wins; ignore in-workbook `OPSF_*` tabs.** Matches
  the Importer Source File Index allow-list. Cleanest.
- **B — Workbook wins for its own task; SRC-001 covers others.**
  Last-write-wins per task. Messier but pragmatic if Kevin actually
  edits per-task access inside SWMS workbooks.
- **C — Workbook `OPSF_*` tabs are author's local notes, not importer
  input.** Same as A but more explicit about intent.

**Working recommendation** (subject to Kevin's confirmation): **A/C**.
Otherwise every SWMS workbook becomes a denormalised mini-master, which
is exactly what v0.3 architecture aims to prevent.

**What we need**:

- [ ] Kevin / Damon to confirm whether SWMS-workbook `OPSF_*` tabs are
      ever the source of truth, or if SRC-001 always wins.

### C. Worker App View Map grew from 12 cols to 34 cols

Raised: 2026-05-23. Status: **open / partly self-answerable**.

Our M16.1 `swms_activity_steps` schema covers ~9 of the v4 workbook's
34 source columns:

- `step_number` ← `activity_number`
- `title` ← `activity_name`
- `instruction` ← `app_screen` or `quick_view_summary` (TBD)
- `hazards` (jsonb) ← `hazards_from_nw` / `hazard_description_from_nw`
- `controls` (jsonb) ← `controls_from_nw_dm`
- `required_ppe` (jsonb) ← (not directly in source; derive from controls?)
- `minimum_read_seconds` ← (not in source; comes from workplace_task_settings)
- `metadata` (jsonb) ← everything else

The other ~25 cols are either:

- **Future P1+ linkages** (`linked_assets`, `linked_hazardous_chemicals`,
  `linked_permit_triggers`, `linked_critical_controls`) — belong to
  tables we have not built yet.
- **Risk rating** (`initial_risk_level`, `residual_risk_level`,
  `residual_risk_reason`) — could go in `metadata` JSONB, or in
  dedicated columns if we want to query by risk.
- **Roles** (`primary_task_performer`, `supporting_role`,
  `supervisory_verification`, `responsible_roles`) — same question.
- **UX/visual** (`quick_view_summary`, `app_screen`) — straight into
  worker-facing JSON.
- **Other** (`worker_burden_basis`, `consequences_from_nw`,
  `checks_verification`, `verification_method`,
  `critical_control_failure_action`, `evidence_prompt`, `pre_start_link`,
  `post_task_link`, `training_link`, `stop_work_trigger`, etc.) — mostly
  control / response metadata.

**Working recommendation**: stick to the same pattern Codex used for
`swms_versions.full_swms_content` — store the 9 mapped fields in
dedicated columns, dump the remaining 25 fields into
`swms_activity_steps.metadata` JSONB. No data loss; queryable later if
needed.

**What we need**:

- [ ] Decide whether any of the 25 extra fields are P0 query targets
      (i.e. needed for the worker UI in slice 5 of the importer flow).
      If yes, they get dedicated columns. If no, JSONB.
- [ ] Confirm with Kevin whether `linked_assets` / `linked_hazardous_chemicals`
      / `linked_permit_triggers` / `linked_critical_controls` are
      pointers to future tables (in which case they're tombstone
      references for P1+ work).

### D. Worker App View Map has 11 steps, not 10

Raised: 2026-05-23. Status: **likely benign, confirm**.

Older samples and the column-mapping spec ("step_number must be 1-10")
assume exactly 10 worker steps per task. The v4 workbook has 11
(`activity_number` 1.0 through 11.0).

Our M16.1 schema makes `swms_activity_steps.step_number` an
`unsignedSmallInteger` with no upper bound — so 11 imports fine. The
validation rule in `OpsFortress_MVP_Column_Level_Mapping_v0_3_Clean.xlsx`
row 88 (`SWMS-001`) saying "exactly 10 worker app steps" needs to be
re-stated as "1-N where N is the number of activities the task
actually has".

**What we need**:

- [ ] Confirm with Kevin that the 10-step assumption is dropped.

## The "ACC-001 invisible in Excel" rendering quirk

Raised: 2026-05-23. Status: **resolved as Excel-rendering, not data**.

When inspecting the v4 workbook in Excel, the user reported that the
`OPSF_Task_Occ_Access` and `OPSF_Task_Ind_Access` tabs appeared empty.
Verified via openpyxl that the data is physically present in the file:

- Both tabs have `sheet_state = 'visible'`
- No hidden rows, no hidden columns
- No `auto_filter` set
- Cell values are plain strings (not formulas), identical under both
  `data_only=True` and `data_only=False` read modes.

Most likely cause: the `.xlsm` file's macro / dynamic-content security
prompt was not clicked through ("Enable Content" in Excel's yellow
banner), which can blank dynamic-content rendering in some templates.

**Mitigation**: when a workbook tab "looks empty" in Excel but Claude /
the importer can read content, export the tab to CSV and inspect the
CSV directly. The CSV will not have any Excel-rendering quirks.

## Conventions to follow when adding to this log

- New workbook intake gets a `### YYYY-MM-DD — <filename>` subsection
  under "Workbook intake log".
- New open questions get an `### Letter. Short title` subsection under
  "Open Questions" with the date raised and a status line.
- When a question is answered, change `Status: open` to `Status:
  answered YYYY-MM-DD` and add the decision inline below the question,
  without deleting the original framing. Past decisions are a record.
- When a slice unblocks because of an answered question, update the
  table at the top of this file and reference the question letter
  in the slice's commit message.
