# OpsFortress Target Architecture

## 2026-05-17 Addendum — Platform Split and v0.3 Reset

The latest architecture review and meeting notes clarify a stronger distinction between **OpsFortress** and **WHSAPP**.

- **OpsFortress** is the reusable platform / software engine / shared database layer.
- **WHSAPP** is the WHS-specific branded application layer running on top of OpsFortress.

This matters technically and commercially. WHSAPP may be one product, brand, or future sellable business. OpsFortress should remain the underlying platform asset and licence layer that can support WHSAPP and future vertical applications.

### v0.3 Schema Supersedes the Early Demo Schema

The current repository contains an early scaffold schema built around:

```text
tenants
businesses
workplaces
task_packs
activities
submissions
file_uploads
generated_documents
```

That schema remains useful as a Laravel implementation reference, but it is no longer the authoritative product model. The v0.3 ERD / Database Spec / Column Mapping / Importer Source Index should drive the next migration set.

The recommended next step is:

```text
v0.3 Schema Reset + Importer-first P0
```

### v0.3 Core Modelling Direction

The next schema should move toward:

```text
customer_accounts
business_entities
account_businesses
business_identifiers
contractor_relationships
user_business_access
tasks
swms_versions
swms_activity_steps
worker_task_sessions
swms_step_events
signatures
evidence_files
audit_events
import_batches
import_source_files
import_validation_results
```

Important corrections:

1. Do not model business identity as ABN-only.
2. Do not treat `task_packs` as the final SWMS/SOP content model.
3. Do not rely on generic `submissions.payload` for all runtime/evidence data.
4. Do not infer contractor relationships only from user/business assignment mismatches.
5. Do not build major production workflows on old scaffold tables before the v0.3 reset.

---

## Purpose

This document captures the recommended target architecture for the Laravel rebuild of WHS Apps, based on the source material reviewed in:

- `others/WHS_Architecture_Record.md`
- `others/Work Directory Path.xlsx`
- `others/FILE_INDEX.md`
- `others/INTERNS - Business Identity Information 1.docx`
- `others/Team Directory Input.xlsx`
- `others/Industry.xlsx`
- `others/Hot Work Permit BRANCHING.xlsx`
- `others/Capability Assessment Contractor Blockchain.xlsx`
- `others/WHS_App_Task_Data_Pack_Hanging_a_Door_Laravel_Sample(1).xlsx`
- `others/WHS_App_OpsFortress_SWMS_Only_Pilot_Lay_concrete_blocks_Global_v7_fit_to_data.xlsx`
- `others/Kevin_Excels_OHSMS/*`
- `others/Qld Health Final Draft.docx`
- `Originals/OpsFortress_MVP_ERD_v0_3_Readable.pdf`
- `Originals/OpsFortress_MVP_Database_Spec_v0_3_Clean.xlsx`
- `Originals/OpsFortress_MVP_Column_Level_Mapping_v0_3_Clean.xlsx`
- `Originals/OpsFortress_MVP_Importer_Source_File_Index_for_Yiming_v0_1_Clean.xlsx`

The goal is to define a scalable application structure that matches the business model implied by the legacy files, rather than reproducing the old AppSheet setup one app at a time.

## Core Reading of the Legacy Material

The source files point to a very specific product shape:

- The system is not a collection of unrelated forms. It is a WHS platform with a clear separation between core business identity data and workflow execution.
- `Work Directory Path.xlsx` is best treated as a product capability map, not a technical folder map.
- Many legacy AppSheet apps are repeated variations of the same underlying pattern.
- `Business Identity` is a foundational onboarding flow with multiple branching legal structures.
- `SWMS`, `SOP`, `Pre-Start`, `Post-Task`, and training content are different views of the same task-driven content model.
- `OHSMS` and reporting are not single-purpose modules. They represent a larger form runtime layer with many workflow variants.
- White-labeling, client-specific deployments, and tenant-specific branding are real requirements.
- Mobile-first worker execution is a primary use case.
- OpsFortress should be treated as the reusable platform layer; WHSAPP should be treated as the first vertical app built on top of that layer.
- The importer is a core architectural component, not an optional utility.

## Architectural Direction

The recommended architecture is a modular monolith:

- One Laravel application
- One PostgreSQL database
- One React plus TypeScript frontend delivered through Inertia
- Clear domain boundaries inside the monolith
- Shared infrastructure for storage, queues, PDF generation, notifications, importer, and audit

This is the right tradeoff for the current stage. The system is too interconnected for early microservices, but too large for a flat controller-model-page structure.

## Top-Level Platform Model

The application should be organized into three major layers:

1. `OpsFortress Core`
2. `WHS Workflow Layer`
3. `Platform Services`

### 1. OpsFortress Core

This is the system of record for all identity and relationship data:

- customer accounts
- business entities / legal entities
- account-to-business relationships
- business identifiers
- countries and jurisdiction foundations
- partnerships
- trusts
- workplaces
- workers
- contractors
- contractor relationships
- roles
- permissions
- user business access
- user workplace access
- industries
- occupations
- teams
- branding and account configuration

This layer must remain clean and highly normalized. Everything else depends on it.

### 2. WHS Workflow Layer

This is where the operational WHS modules live:

- Business Identity
- Team Directory
- Tasks
- SWMS
- SOPS
- Worker App View
- Pre-Start
- Post-Task
- Training and Assessments
- Registers
- Inspections
- Incidents
- Permits to Work
- Return to Work
- OHSMS reporting
- Corrective actions
- Compliance dashboards

This layer should be built from reusable engines, not isolated one-off modules.

### 3. Platform Services

These are cross-cutting services:

- importer and validation pipeline
- file uploads
- signatures
- generated PDFs
- audit trails
- tamper-evidence hashes
- notifications
- alert escalation
- queue jobs
- object storage
- AI-assisted content generation
- import and export pipelines

These services should not contain business-specific rules. They should support the workflow modules.

## Recommended Domain Breakdown

The codebase should follow business domains first, then technical concerns inside each domain.

Suggested backend structure:

```text
app/
  Domain/
    OpsFortress/
      Accounts/
      BusinessEntities/
      BusinessIdentifiers/
      Workplaces/
      People/
      Access/
      Contractors/
      Teams/
      Industries/
      Occupations/
      Branding/
    Whs/
      BusinessIdentity/
      TeamDirectory/
      Tasks/
      Swms/
      Sops/
      Training/
      Registers/
      Inspections/
      Incidents/
      Permits/
      ReturnToWork/
      Reporting/
      Compliance/
      Runtime/
      Evidence/
    Shared/
      Audit/
      Files/
      Pdf/
      Notifications/
      Hashing/
      Importer/
      Ai/
  Application/
    Admin/
    Worker/
    Api/
  Infrastructure/
    Persistence/
    Storage/
    Queue/
    Services/
```

Suggested frontend structure:

```text
resources/js/
  modules/
    core/
    business-identity/
    team-directory/
    tasks/
    swms/
    sops/
    training/
    registers/
    inspections/
    incidents/
    permits/
    reporting/
    compliance/
    admin-runtime/
    worker-runtime/
  shared/
    components/
    forms/
    layouts/
    hooks/
    types/
    utils/
```

This structure aligns the backend and frontend around the same business capabilities.

## Internal Module Pattern

Each major workflow domain should use a consistent internal split:

- `Catalog`
- `Runtime`
- `Output`

### Catalog

Contains templates, rules, mappings, question sets, task definitions, and versioned content.

Examples:

- SWMS versions
- SWMS worker app steps
- SOP section definitions
- assessment question banks
- permit rule definitions
- occupation-to-task eligibility
- industry-to-task eligibility

### Runtime

Contains real execution data.

Examples:

- worker task sessions
- step view events
- task assignments
- site sessions
- SWMS acknowledgements
- pre-start submissions
- post-task submissions
- training attempts
- incident reports
- inspection runs
- permit approvals
- corrective actions

### Output

Contains derived artifacts and reporting.

Examples:

- PDFs
- audit records
- compliance snapshots
- exports
- dashboard summaries
- evidence bundles

This pattern allows multiple modules to share the same conceptual model even when the business content differs.

## Database Strategy

The database should be designed in four layers.

### 1. Master Data Layer

Core identity and relationship tables:

- customer_accounts
- business_entities
- account_businesses
- countries
- business_identifier_types
- business_identifiers
- workplaces
- workplace_environments
- users
- user_business_access
- user_workplace_access / workplace_user_assignments
- contractor_relationships
- industries
- occupations
- team_memberships

### 2. Content Layer

Versioned content and rules:

- tasks
- swms_versions
- swms_activity_steps
- prestart_questions
- posttask_questions
- training_questions
- sop_sections
- checklist_templates
- assessment_question_sets
- form_definitions
- workflow_rules
- task_occupation_access
- task_industry_access

### 3. Runtime Layer

Operational execution data:

- worker_task_sessions
- swms_step_events
- assignments
- site_sessions
- prestart_submissions
- prestart_responses
- posttask_submissions
- posttask_responses
- training_attempts
- training_responses
- acknowledgements
- approvals
- incidents
- inspections
- permits
- corrective_actions

### 4. Evidence and Output Layer

Proof, documents, and history:

- evidence_files
- uploads
- signatures
- generated_documents
- audit_events
- hash_snapshots
- notification_logs
- alerts

This matters because the legacy files mix content, identity, and runtime data together. The rebuild should separate them cleanly.

### Content-Layer Normalization — Updated Decision (2026-05-17)

The v0.3 content layer should use canonical v0.3 tables, not the older workbook/Laravel table names as final table names.

Important source-to-target interpretation:

- `WHSAPP_SWMS_Data` should load into `swms_versions.full_swms_content` as JSONB, while preserving source traceability.
- `WHSAPP_Worker_App_View_Map` should load into `swms_activity_steps`.
- `WHSAPP_PreStart_SWMS_15` should load into `prestart_questions`.
- `WHSAPP_PostTask_SWMS_15` should load into P1 `posttask_questions` unless P0 scope is expanded.
- `WHSAPP_Training_SWMS` should load into P1 `training_questions` unless P0 scope is expanded.
- OPSF occupation and industry source tabs should load into `occupations`, `industries`, `task_occupation_access`, and `task_industry_access`.

The `payload JSON` columns on runtime records may still be useful as immutable raw-submission snapshots for legal/dispute traceability. Business queries, scoring, and dashboards should read from normalized runtime tables where possible.

### Authorization — Updated Decision (2026-05-17)

Authorization is layered:

- account-level isolation using context/global scopes;
- business-level visibility using `user_business_access`;
- workplace-level visibility using `user_workplace_access` or strengthened `workplace_user_assignments`;
- per-record authorization using Laravel Policies;
- coarse-grained permission roles through a simple role system or Spatie when complexity justifies it.

Person identity types and permission roles are orthogonal.

Person identity examples:

- employee
- labour_hire
- contractor
- visitor
- regulator
- supplier
- other

Permission role examples:

- worker
- supervisor
- manager
- admin
- platform_admin

## Reusable Engines to Build First

The legacy material strongly suggests that the platform should be built around reusable engines.

### 1. Identity and Onboarding Engine

Purpose:

- business onboarding
- legal structure branching
- workplace creation
- worker and contractor setup
- role assignment
- business identifier capture

Key sources:

- Business Identity documents
- Team Directory workbooks
- v0.3 Database Spec

### 2. Importer Engine

Purpose:

- read approved source workbooks / Google Sheets;
- validate tabs and column headers;
- map source columns to canonical v0.3 tables;
- track file hashes and import batches;
- write validation results before commit;
- support staged imports.

Key sources:

- Importer Source File Index
- Column-Level Mapping workbook
- SWMS sample workbooks

### 3. Task / SWMS Engine

Purpose:

- occupation and industry matching
- SWMS content delivery
- SOP content delivery
- worker app view steps
- pre-start checklists
- post-task reviews
- training assessments

Key sources:

- v0.3 Column-Level Mapping
- SWMS sample workbooks

### 4. Form Runtime Engine

Purpose:

- inspections
- incidents
- reporting
- permits
- return to work plans
- OHSMS forms

Key sources:

- OHSMS Excel templates
- Hot Work Permit branching workbook

### 5. Compliance and Output Engine

Purpose:

- scoring
- critical failure tracking
- alerts
- corrective actions
- PDFs
- dashboards
- audit history
- tamper evidence

Key sources:

- Architecture records
- blockchain/hash-chain notes
- reporting samples

## Navigation and UX Implication

The sample screenshot shows a mobile-first home screen built around capability cards such as:

- Team Directory
- Resources
- Dashboard
- Contractor Management
- Incident Management

That implies the product should be organized around user-facing capabilities, not raw data entities.

Recommended UI posture:

- admin UI for configuration, visibility, and governance
- worker UI for fast task execution in the field
- touch-friendly screens
- clear offline-aware workflow boundaries
- capability-based navigation

## Multi-Tenancy and White-Labeling

The source material makes it clear that client-specific configuration is a real requirement.

The system should support:

- account-specific branding
- account-specific feature flags
- custom menu exposure
- business-specific content packs
- customer-specific deployment profiles
- potential future dedicated deployments for large government clients

This does not require separate codebases. It requires strong account configuration and content scoping.

### Tenancy Strategy — Updated Direction (2026-05-17)

Use a single shared PostgreSQL database with row-level account/business/workplace scoping for normal customers. Do not build DB-per-tenant as the default runtime mode.

If a future government contract mandates hard physical isolation, use a dedicated deployment of the whole stack for that customer rather than introducing a complex runtime dual-mode tenancy system.

Enforcement needed:

1. Every tenant/account-scoped domain table carries the appropriate account/business/workplace foreign key.
2. Context/global scopes handle account-level filtering.
3. Policies enforce business and workplace-level visibility.
4. Background jobs explicitly serialise and restore account context.
5. Tests assert cross-account, cross-business, and cross-workplace boundaries.

Anti-patterns to avoid:

- manually adding scope filters ad hoc in controllers;
- allowing unscoped public registration;
- allowing supervisors/managers to see unrelated businesses in the same account;
- treating contractor access as an ordinary employee assignment.

## AI Positioning

The presence of legacy OpenAI-related AppSheet evidence suggests AI is part of the product direction.

AI should be treated as a platform service, not as a hidden dependency inside business logic.

Recommended uses:

- content generation assistance
- question generation
- checklist drafting
- summarization of reports
- advisory prompts for admins

AI outputs should remain reviewable and versioned before becoming active content.

## Recommended Implementation Order

### Phase 1: Stabilize v0.3 Core Schema

Build and validate:

- customer accounts
- business entities
- account-business relationships
- countries
- business identifier types
- business identifiers
- workplaces
- users
- user business access
- user workplace access / assignments
- contractor relationships
- occupations
- industries

### Phase 2: Build Importer Foundation

Implement:

- import batches
- import source files
- import validation results
- source allow-list handling
- staged import for business identifiers, occupations, industries, tasks, SWMS content, worker view, and pre-start questions

### Phase 3: Build Task / SWMS P0

Implement:

- task content import
- SWMS version import
- worker app view import
- occupation/industry access
- pre-start questions
- worker task sessions
- SWMS step events
- signatures
- pre-start submission capture
- audit events

### Phase 4: Build Business Identity and Admin Configuration

Implement:

- business entity setup
- workplace setup
- group administrator setup
- worker/contractor setup
- workplace task settings
- role/access boundaries

### Phase 5: Build Output and Compliance

Implement:

- PDFs
- corrective action workflows
- dashboards
- audit trails
- hash evidence
- alerts and escalation

## Anti-Patterns to Avoid

- Do not model one legacy AppSheet app as one Laravel module.
- Do not create one database table per spreadsheet without abstraction.
- Do not bury business rules inside React screens.
- Do not treat OHSMS forms as unrelated special cases.
- Do not split into microservices early.
- Do not let public registration bypass tenant/account/business scoping.
- Do not continue production workflow development on the old scaffold schema after v0.3 has been accepted.

## Practical Next Step for This Repository

The best next step is to formalize the v0.3 schema reset before building more pages.

Recommended immediate actions:

1. Convert the v0.3 DBML to readable text if possible.
2. Generate fresh v0.3 migrations.
3. Preserve useful infrastructure from the current repo.
4. Build importer tracking tables early.
5. Build a minimal importer for approved source tabs.
6. Prove one imported SWMS worker flow end-to-end.

## Final Position

The legacy material does not describe a simple forms app.

It describes a WHS platform that should be built as:

- platform first
- domain driven
- account and business aware
- mobile first
- template based
- workflow centric
- importer driven
- audit capable

The correct mental model is:

`Platform -> Domain -> Importer -> Content -> Runtime Execution -> Evidence -> Output`

That is the structure this rebuild should follow.