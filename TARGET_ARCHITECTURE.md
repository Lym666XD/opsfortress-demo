# OpsFortress Target Architecture

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

## Architectural Direction

The recommended architecture is a modular monolith:

- One Laravel application
- One PostgreSQL database
- One React plus TypeScript frontend delivered through Inertia
- Clear domain boundaries inside the monolith
- Shared infrastructure for storage, queues, PDF generation, notifications, and audit

This is the right tradeoff for the current stage. The system is too interconnected for early microservices, but too large for a flat controller-model-page structure.

## Top-Level Platform Model

The application should be organized into three major layers:

1. `OpsFortress Core`
2. `WHS Workflow Layer`
3. `Platform Services`

### 1. OpsFortress Core

This is the system of record for all identity and relationship data:

- tenants
- businesses
- legal entities
- partnerships
- trusts
- workplaces
- workers
- contractors
- roles
- permissions
- industries
- occupations
- teams
- branding and tenant configuration

This layer must remain clean and highly normalized. Everything else depends on it.

### 2. WHS Workflow Layer

This is where the operational WHS modules live:

- Business Identity
- Team Directory
- Task Packs
- SWMS
- SOPS
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

- file uploads
- signatures
- generated PDFs
- audit trails
- tamper-evidence hashes
- notifications
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
      Tenancy/
      Businesses/
      LegalEntities/
      Workplaces/
      People/
      Teams/
      Industries/
      Occupations/
      Permissions/
      Branding/
    Whs/
      BusinessIdentity/
      TeamDirectory/
      TaskPacks/
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
    Shared/
      Audit/
      Files/
      Pdf/
      Notifications/
      Hashing/
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
    task-packs/
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

- SWMS templates
- SOP section definitions
- assessment question banks
- permit rule definitions
- occupation-to-task eligibility

### Runtime

Contains real execution data.

Examples:

- task assignments
- worker acknowledgements
- pre-start submissions
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

This pattern allows multiple modules to share the same conceptual model even when the business content differs.

## Database Strategy

The database should be designed in four layers.

### 1. Master Data Layer

Core identity and relationship tables:

- tenants
- businesses
- legal_entities
- workplaces
- users
- role_assignments
- industries
- occupations
- team_memberships

### 2. Content Layer

Versioned content and rules:

- task_pack_versions
- swms_templates
- sop_sections
- checklist_templates
- assessment_question_sets
- form_definitions
- workflow_rules

### 3. Runtime Layer

Operational execution data:

- assignments
- site_sessions
- submissions
- answers
- acknowledgements
- approvals
- incidents
- inspections
- permits
- corrective_actions

### 4. Evidence and Output Layer

Proof, documents, and history:

- uploads
- signatures
- generated_documents
- audit_events
- hash_snapshots
- notification_logs

This matters because the legacy files mix content, identity, and runtime data together. The rebuild should separate them cleanly.

### Content-Layer Normalization — Decision (2026-05-12)

The content layer is built as **normalized relational tables**, not JSON blobs. Kevin's `OPSF_Laravel_Table_Map` (44 rows, LTM-001 through LTM-044) is the authoritative column-to-table mapping and the import contract.

MVP scope = the 17 tables flagged "open in Phase 1" in `WHS_Architecture_Record.md` §3.18:

- `swms_records` (LTM-002), `swms_activity_risks` (LTM-003), `swms_responsible_roles` (LTM-004)
- `worker_app_view_map` (LTM-005)
- `prestart_swms_questions` (LTM-011), `posttask_swms_questions` (LTM-012), `swms_training_questions` (LTM-013)
- `opsf_occupation_master`, `opsf_industry_master`, `opsf_task_occupation_access`, `opsf_task_industry_access`
- `role_permissions`, `pdf_rules`, `digital_signature_rules`, `photo_evidence_rules`
- `audit_events` (built in Phase 0)
- `import_validation_results` (Week-3 importer dependency)

Deferred to Phase 2/3 (~27 tables): permit_*, control_hazard_link_map, critical_control_verifications, dashboard_rules, dashboard_data_map, blockchain_logic.

The `payload JSON` columns on `activities` and `submissions` are **kept** but used only as raw-submission snapshots for legal/dispute traceability. Business queries, scoring, and dashboards must read from the normalized tables, not from `payload`.

### Authorization — Decision (2026-05-12)

Authorization is layered:

- **Coarse-grained role membership** — uses `spatie/laravel-permission`. The `HasRoles` trait on `User`; the package is configured (via `config/permission.php`) to point at the existing `roles` and `user_roles` tables instead of installing its default `model_has_roles` / `role_has_permissions` schema.
- **Fine-grained per-record authorization** — uses Laravel Policies (`app/Policies/...`) for decisions like "can this supervisor edit this submission".

Person identity types (Employee / Contractor / Labour Hire) and contractor sub-types remain on the `users` table as `person_type` / `contractor_type` enums (per `Role_Architecture_Notes.md`) — they are orthogonal to permission roles and are NOT modeled through the permission system.

## Reusable Engines to Build First

The legacy material strongly suggests that the platform should be built around reusable engines.

### 1. Identity and Onboarding Engine

Purpose:

- business onboarding
- legal structure branching
- workplace creation
- worker and contractor setup
- role assignment

Key source:

- `INTERNS - Business Identity Information 1.docx`

### 2. Task Pack Engine

Purpose:

- occupation and industry matching
- SWMS content delivery
- SOP content delivery
- pre-start checklists
- post-task reviews
- training assessments

Key sources:

- `WHS_App_Task_Data_Pack_Hanging_a_Door_Laravel_Sample(1).xlsx`
- `WHS_App_OpsFortress_SWMS_Only_Pilot_Lay_concrete_blocks_Global_v7_fit_to_data.xlsx`

### 3. Form Runtime Engine

Purpose:

- inspections
- incidents
- reporting
- permits
- return to work plans
- OHSMS forms

Key sources:

- `Kevin_Excels_OHSMS/*`
- `Hot Work Permit BRANCHING.xlsx`

### 4. Compliance and Output Engine

Purpose:

- scoring
- critical failure tracking
- corrective actions
- PDFs
- dashboards
- audit history
- tamper evidence

Key sources:

- `Capability Assessment Contractor Blockchain.xlsx`
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

- tenant-specific branding
- tenant-specific feature flags
- custom menu exposure
- business-specific content packs
- customer-specific deployment profiles

This does not require separate codebases. It requires strong tenant configuration and content scoping.

### Tenancy Strategy — Decision (2026-05-12)

**Single shared PostgreSQL database with `tenant_id` row-level scoping. No DB-per-tenant.**

Rationale:

- Operationally simpler — one set of migrations, one connection pool, one backup pipeline.
- Cross-tenant analytics, content pack sharing, and platform-wide AI services are easier when all data lives in one schema.
- Per-tenant DB has been considered for government clients (e.g. Queensland Health) but is deferred. If a government contract later mandates data isolation, the chosen path is a separate dedicated deployment of the whole stack for that one tenant — not a runtime dual-mode system.

Enforcement (must be in place before any business controller is written):

1. Every domain table carries a `tenant_id` foreign key (already true in current migrations).
2. A `BelongsToTenant` Eloquent global scope auto-filters every query by the current tenant context.
3. A `SetTenantContext` middleware resolves the active tenant from the authenticated user and binds it for the request lifecycle.
4. Background jobs explicitly serialise and restore tenant context; never rely on web-request state.
5. Every model fillable list excludes `tenant_id` to prevent mass-assignment cross-tenant writes.
6. A pest/phpunit test suite asserts that a user from tenant A cannot read or write any record belonging to tenant B for each domain.

Anti-patterns to avoid:

- Manually adding `where('tenant_id', auth()->user()->tenant_id)` in controllers — easy to forget, must be scope-driven.
- Allowing nullable `tenant_id` on runtime tables — only platform-level catalog tables (industries, occupations) may be tenant-null.

## AI Positioning

The presence of `WHSAppsOpenAi-4238531` suggests AI is part of the product direction.

AI should be treated as a platform service, not as a hidden dependency inside business logic.

Recommended uses:

- content generation assistance
- question generation
- checklist drafting
- summarization of reports
- advisory prompts for admins

AI outputs should remain reviewable and versioned before becoming active content.

## Recommended Implementation Order

### Phase 1: Stabilize the Core

Build and validate:

- tenants
- businesses
- legal entities
- workplaces
- workers
- industries
- occupations
- roles and permissions

### Phase 2: Build Business Identity

Implement the first complete onboarding flow:

- business type selection
- legal entity branching
- group administrator creation
- workplace setup
- initial worker setup

### Phase 3: Build the Task Pack Engine

Implement:

- task pack assignment
- SWMS acknowledgement
- pre-start checklist
- submission capture
- scoring and critical-fail logic

### Phase 4: Build the Form Runtime Engine

Generalize:

- inspections
- incidents
- permits
- return to work
- reporting forms

### Phase 5: Build Compliance and Output

Implement:

- PDFs
- corrective action workflows
- dashboards
- audit trails
- hash evidence

## Anti-Patterns to Avoid

- Do not model one legacy AppSheet app as one Laravel module.
- Do not create one database table per spreadsheet without abstraction.
- Do not bury business rules inside React screens.
- Do not treat OHSMS forms as unrelated special cases.
- Do not split into microservices early.
- Do not let public registration bypass tenant and business scoping.

## Practical Next Step for This Repository

The best next step is to formalize the codebase around these domain boundaries before too many new pages are added.

Recommended immediate actions:

1. Create domain-oriented backend namespaces.
2. Restructure frontend pages into module-oriented directories.
3. Lock down multi-tenant onboarding rules.
4. Implement `Business Identity` as the first true workflow.
5. Implement `Task Packs` as the shared content engine for SWMS, SOP, and pre-start flows.

## Final Position

The legacy material does not describe a simple forms app.

It describes a WHS platform that should be built as:

- platform first
- domain driven
- tenant aware
- mobile first
- template based
- workflow centric
- audit capable

The correct mental model is:

`Platform -> Domain -> Engine -> Content Pack -> Runtime Execution -> Output`

That is the structure this rebuild should follow.
