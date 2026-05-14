# WHS App Rebuild — Source File Index

> **Purpose:** Reference guide for AI coding agents (Codex, Claude Code) and developers.
> Lists every source file that contains information needed to implement the Laravel rebuild.
> **Last updated:** 2026-04-28
> **Workspace root:** `C:\Users\User\Desktop\WHSAPP\`

---

## How to Use This Index

Each file entry answers: *what is it, what does it tell you, and when should you read it.*
Files are grouped by the development domain they inform. Read the architecture record first.

---

## 0. Start Here — Architecture Records

These two files are the **master summary** of everything discovered. Read these before any other file.

| File | Description |
|---|---|
| `WHS_Architecture_Record.md` | English architecture & discovery record (v9). System scope, data structures, business rules, open questions, tech stack rationale. **Read this first.** |
| `WHS架构分析记录.md` | Chinese version of the same record (v9). Identical content, used for team communication. |

---

## 1. Data Model & Entity Relationships

Files that define the shape of the database — tables, fields, relationships, hierarchies.

### 1.1 OpsFortress Core Entities

| File | Path | What it tells you |
|---|---|---|
| `INTERNS - Business Identity Information 1.docx` (Luke's version) | `Originals/Other/` | Complete entity walkthrough for the OpsFortress onboarding flow. Business types, ownership structures, relationship between Business → Workplace → Worker. **Primary reference for the `businesses`, `workplaces`, `users` tables.** |
| `INTERNS - Business Identity Information 1.docx` (Yiming's version) | `Uploads/` | 65-screenshot field-level specification for ALL identity paths including Trust structures. Every field name, type, and sequence for every entity variant. **Use this for exact column names in migrations.** |
| `Team Directory Input.xlsx` | `Originals/Other/` | Full user role taxonomy (7 role categories × subtypes) + profile field schema per role. **Primary reference for the `roles`, `user_profiles` tables and the permissions matrix.** |
| `Old Files/Kevin Excels/Industry.xlsx` | `Originals/Old Files/Kevin Excels/` | Sheet 1: Industry form header (Timestamp, Email, Last/First Name, Industry). Sheet 2 "Industry Acronyms": 435-column industry classification taxonomy — the full hierarchy for the `industries` and `industry_groups` tables. |

### 1.2 Content / Form Data Structure

| File | Path | What it tells you |
|---|---|---|
| `Old Files/Old WHS APPS Excels/` *(62 files)* | `Originals/Old Files/Old WHS APPS Excels/` | Legacy AppSheet data source files for every OHSMS form. Two patterns: (a) 3-col nav/view files, (b) 87–1019 col form data files. Universal header: `Timestamp \| Logo \| First/Last Name \| Email \| Report ID \| … \| Signature \| Date`. **Use to design the `form_submissions`, `form_fields`, `form_responses` tables.** Key forms: Workplace Inspection (1,019 cols), Crane Pre-Op (848), Chemical Risk (656), Incident Investigation (597). |
| `Old Files/Interns Old/Sushma/Capability Assessment Contractor Blockchain.xlsx` | `Originals/Old Files/Interns Old/Sushma/` | 164-col capability assessment with blockchain columns. Col A = `=MD5(CONCATENATE(C2:C4000))`, Col B = OriginalHash. **Defines the `blockchain_id` + `original_hash` tamper-detection pattern used across all records.** |
| `Old Files/Adam/Hot Work Permit BRANCHING.xlsx` | `Originals/Old Files/Adam/` | 2,298 rows × 71 cols. The only Permits to Work data structure. 3-phase workflow (Before/During/Completion), 6-level fire danger branching, 27 hot work types. **Primary reference for `permits`, `permit_workplace`, `permit_parties`, `permit_hazard_gates`, `permit_fire_watch`, `permit_authorisations` tables.** |
| `4WD_Vehicle_Inspection_Checklist.xlsx` | `Originals/` | 20-item pre/during/post checklist template. Representative structure for all SOPs checklist data. |
| `4WD_Knowledge_Assessment_Quiz_With_Feedback.xlsx` | `Originals/` | 20-question MCQ with per-question feedback. Representative structure for `training_questions` table (with feedback variant). |
| `4WD_Knowledge_Assessment_Quiz.xlsx` | `Originals/` | Same without feedback. Simpler `training_questions` variant. |

---

## 2. Business Rules & Workflows

Files that define conditional logic, approval flows, branching rules, and process sequences.

| File | Path | What it tells you |
|---|---|---|
| `Old Files/Adam/Hot Work Permit BRANCHING.xlsx` | `Originals/Old Files/Adam/` | Full branching decision logic for Permits to Work. Fire danger gates (Very High / Severe / Extreme / Catastrophic = block without manager or emergency services approval). **Implement as Laravel Policy + Gate rules.** |
| `Originals/Other/Docs/Prepare Forms for Appsheet.pptx` | `Originals/Other/Docs/` | 7-step AppSheet form creation workflow. Explains why all legacy data is structured the way it is. **Historical context — not needed for Laravel implementation, but explains legacy data layout.** |
| `Originals/Other/Marketing/App Creation Process.pptx` | `Originals/Other/Marketing/` | Full 5-stage pipeline: PDF → Excel → MS Forms → AppSheet → Google Drive. **Informs the Laravel equivalent: DB seeds → Inertia forms → PostgreSQL → S3 PDF.** |
| `Qld Health Final Draft.docx` | `Originals/Other/` | Sales proposal to Queensland Health. Contains: feature list, pricing tiers, SLA commitments, app creation process description. **Reference for product scope and multi-tenant requirements.** |

---

## 3. Content Generation & AI Prompts

Files that define Kevin's AI-assisted content pipeline — how assessment questions and checklists are generated.

| File | Path | What it tells you |
|---|---|---|
| `Originals/Prompts/` *(9 Excel files)* | `Originals/Prompts/` | Kevin's prompt templates for AI generation of: pre-start checklists, post-task reviews, 12-question training assessments. **The 12-question fixed structure (3 MCQ Hazard / 3 MCQ Controls / 2 T/F Emergency / 2 MCQ Equipment / 2 T/F PPE) is the mandatory output contract for the `training_questions` table.** Files: SOPS-PRE-START, SOPS-POST-START, SOPS-ASSESS, SWMS-PRE-START, SWMS-POST-TASK, SWMS-ASSESS, PRE START PROMPT, POST TASK PROMPT, ASSESS PROMPT. |

---

## 4. System Scope & Module Map

Files that define what the full system contains — every app, module, and data table.

| File | Path | What it tells you |
|---|---|---|
| `Work Directory Path.xlsx` | `Originals/Other/` (also in Uploads) | **The definitive scope map.** Hierarchical directory of every AppSheet app (~110) and Google Sheets data table across 5 modules: WHS Apps Data (OpsFortress), Registers, SOPS, SWMS, OHSMS. Also reveals: `WHSAppsOpenAi-4238531` (live OpenAI integration), `QLDHealthApp-4238531` (client-specific app), date-versioned apps (active dev through Oct 2025). |

---

## 5. Legacy System Reference (AppSheet → Laravel Mapping)

Files that help understand the old system architecture and guide migration decisions.

| File | Path | What it tells you |
|---|---|---|
| `Old Files/Old WHS APPS Excels/` | `Originals/Old Files/Old WHS APPS Excels/` | 62 legacy AppSheet data source files. Pattern 1 (27 files): 3-col nav/dashboard. Pattern 2 (35 files): full form data with universal header. **The universal header (`Timestamp \| Logo \| Name \| Email \| Report ID`) maps directly to the base model all OHSMS forms should extend.** |
| `Old Files/Kevin Excels/` | `Originals/Old Files/Kevin Excels/` | Kevin's own Excel working files. Contains industry taxonomy, SOP/SWMS content structures, and edit-versioned (`Edit.xlsx`) inspection forms showing in-development column changes. |

---

## 6. Operational / Administrative Context

Files that provide business context — useful for understanding product decisions, not implementation.

| File | Path | What it tells you |
|---|---|---|
| `2023.04.28 Home page.docx` | `Originals/Other/` | 2023 website copy. Business positioning, product categories, value proposition. |
| `Qld Health Final Draft.docx` | `Originals/Other/` | Sales proposal. Pricing model (per user / per business), feature commitments, SLA. |
| `321220_Chakradhar Reddy Garlapati.pdf` | `Originals/Other/` | Intern resume — not relevant to architecture. |
| `321383_Zhongda Qu.pdf` | `Originals/Other/` | Intern resume — not relevant to architecture. |
| `Resume_Kaushik MALIGELI.docx` | `Originals/Other/` | Intern resume — not relevant to architecture. |

---

## 7. Reading Priority for Laravel Implementation

If you are implementing the rebuild from scratch, read files in this order:

**Phase 1 — Understand the system (read before writing any code)**
1. `WHS_Architecture_Record.md` — full system picture
2. `Work Directory Path.xlsx` — module scope and full app list
3. `INTERNS - Business Identity Information 1.docx` (Yiming's version) — field-level entity spec
4. `Team Directory Input.xlsx` — user roles and permissions

**Phase 2 — Design the database schema**
5. `Old Files/Kevin Excels/Industry.xlsx` — industry taxonomy hierarchy
6. `Old Files/Interns Old/Sushma/Capability Assessment Contractor Blockchain.xlsx` — blockchain_id / hash pattern
7. `Old Files/Adam/Hot Work Permit BRANCHING.xlsx` — permits data model
8. Key OHSMS forms from `Old Files/Old WHS APPS Excels/` — start with Workplace Inspection, Toolbox Meeting, Incident Investigation

**Phase 3 — Implement content / form logic**
9. `Originals/Prompts/` (9 files) — assessment question format contract
10. `4WD_Knowledge_Assessment_Quiz_With_Feedback.xlsx` — assessment table structure
11. `4WD_Vehicle_Inspection_Checklist.xlsx` — checklist table structure

**Phase 4 — Implement permits and complex workflows**
12. `Old Files/Adam/Hot Work Permit BRANCHING.xlsx` (re-read for business rules) — gate logic for controllers

---

## 8. Files That Do NOT Need to Be Read for Implementation

These have already been fully summarised in `WHS_Architecture_Record.md`:

- `Docs/Prepare Forms for Appsheet.pptx` — summarised in section 3.17
- `Marketing/App Creation Process.pptx` — summarised in section 3.17
- All intern resumes — irrelevant
- `appsheet/data/` folder — 600+ mostly-empty app folders, structure already mapped in section 2

---

*This index will be updated as new source files are reviewed.*
