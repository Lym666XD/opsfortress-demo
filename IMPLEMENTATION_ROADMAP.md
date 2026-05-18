# OpsFortress Implementation Roadmap

## Purpose

This roadmap turns the target architecture into an execution plan for the Laravel rebuild of WHS Apps.

It is designed to:

- sequence the work in a scalable way
- reduce rework caused by premature module sprawl
- align backend, frontend, and data modeling decisions
- support both demo delivery and long-term production development

This roadmap assumes the system will be built as a modular monolith using:

- Laravel
- PostgreSQL
- React + TypeScript + Inertia.js
- Redis and queues later for background work
- S3-compatible storage for files and generated documents

## Guiding Principles

1. Build engines before building module variants.
2. Stabilize core identity and tenancy before workflow expansion.
3. Separate master data, content, runtime submissions, and generated outputs.
4. Prefer reusable form and task-pack models over one-off feature implementations.
5. Build demo-ready interfaces early, but anchor them to the real domain model.

## Delivery Strategy

The implementation should move in two parallel tracks:

- `Platform Track`: data model, services, permissions, audit, workflow rules
- `Experience Track`: admin UI, worker UI, navigation, demo flows, visual proof of product direction

This matters because the business needs a convincing product demonstration before every backend capability is complete.

## Phase 0: Foundation Alignment

### Goal

Set the codebase up so future development follows clear boundaries.

### Scope

- confirm domain boundaries from `TARGET_ARCHITECTURE.md`
- create domain-oriented backend namespaces
- create module-oriented frontend directories
- define route groups for admin, worker, and shared flows
- document the initial data model map

### Deliverables

- agreed target module map
- initial folder structure in backend and frontend
- implementation conventions for models, services, requests, and actions
- updated milestone tracking

### Exit Criteria

- the repository structure reflects the target architecture
- new work can be added by domain instead of by ad hoc page creation

### Locked Decisions (2026-05-12)

- **Tenancy strategy:** single shared PostgreSQL database with `tenant_id` row-level scoping. No DB-per-tenant. Government clients with stricter isolation needs are handled via a separate full-stack deployment, not a dual-mode runtime. See `TARGET_ARCHITECTURE.md` §Multi-Tenancy and White-Labeling.
- **Phase 0 refactor commitment:** the 9-step refactor in `MILESTONE.md` §Phase 0 Refactor must complete before any business controller is written. This includes domain namespace creation, splitting workflow migrations, generating Eloquent models for all 17 existing tables, tenant global scope + middleware, audit/hash-chain service, and the tenant-isolation test suite.
- **Content-layer schema:** normalize from day one. Build the 17 MVP-required content tables listed in `WHS_Architecture_Record.md` §3.18 (`WHSAPP_SWMS_Data`, `prestart_swms_questions`, `posttask_swms_questions`, `swms_training_questions`, `swms_responsible_roles`, `worker_app_view_map`, `OPSF_Occupation_Master`, `OPSF_Industry_Master`, `OPSF_Task_Occupation_Access`, `OPSF_Task_Industry_Access`, plus role permissions / PDF rules / digital signatures / photo evidence / audit events tabs). Defer the remaining ~27 tables (permits, blockchain_logic, dashboard_rules) to Phase 2/3. The `payload JSON` columns on `activities` and `submissions` stay as raw-submission snapshots for dispute traceability — they are NOT used for business queries.
- **Authorization library:** `spatie/laravel-permission`. Coarse-grained role checks (Worker / Supervisor / Manager / Admin) use the package's `HasRoles` trait; fine-grained per-record decisions (e.g. "can this user edit this submission") still use Laravel Policies. The package's tables are configured to point at the existing `roles` and `user_roles` tables rather than installing its default `model_has_roles` schema.
- **Import path:** Python-first. A one-shot Python script (Google Sheets API → pandas → psycopg2) imports the 1,700-workbook seed corpus during the content-prep phase. The Laravel importer is built in Week 3-4, copying the field-mapping logic the Python script has already validated. After launch, Kevin uses the Laravel importer (UI/CLI) for new task packs; the Python script is archived. Rationale: cheap schema validation before paying the cost of building UI/error-handling around a still-evolving format.
- **M10+ delivery method:** vertical slices, not horizontal layers. Build one user-visible feature end-to-end (route → controller → FormRequest → policy → Inertia page → phpunit) before moving to the next. First slice = "Add Workplace". Rationale: every slice exercises the architectural invariants (BelongsToTenant, Audit, policy) in real controller code, so we catch integration issues immediately rather than discovering them weeks later when UI meets backend. See `MILESTONE.md` §M10 Delivery Plan for the slice queue.

## Phase 1: Core Platform and Tenancy

### Goal

Establish the system of record for all identity and access relationships.

### Scope

- tenants
- businesses
- legal entities
- workplaces
- workers and contractors
- industries
- occupations
- roles and permissions
- business-to-workplace-to-worker relationships
- tenant branding configuration

### Deliverables

- normalized core tables and relationships
- seed data for a realistic demo tenant
- authorization rules aligned to tenancy boundaries
- admin screens for viewing and maintaining core records

### Exit Criteria

- every user is properly scoped to a tenant and business context
- the system can represent at least one realistic business hierarchy

## Phase 2: Business Identity Onboarding

### Goal

Implement the first complete domain workflow from the legacy material.

### Scope

- business type selection
- branching legal structures
- partnership and trust variants where required
- group administrator setup
- workplace setup
- initial team setup

### Deliverables

- admin onboarding wizard
- validation rules for required entity paths
- summary screens for identity data
- audit events for onboarding changes

### Exit Criteria

- a new tenant can be onboarded through a controlled flow
- the flow produces valid core records without manual repair

## Phase 3: Task Pack Engine

### Goal

Build the reusable content and runtime model for task-driven WHS workflows.

### Scope

- task pack definitions
- task pack versions
- occupation and industry mapping
- SWMS content
- SOP content
- pre-start checklists
- post-task reviews
- training assessments

### Deliverables

- admin task pack management views
- worker task pack execution views
- versioned content model
- assignment and acknowledgement flow
- critical-fail and score calculation logic

### Exit Criteria

- a worker can be assigned a task pack and complete the required pre-work flow
- the same engine supports multiple task content types

## Phase 4: Worker Field Runtime

### Goal

Turn task content into a realistic field workflow for mobile use.

### Scope

- site entry or workplace check-in
- worker sign-in
- SWMS acknowledgement
- pre-start completion
- signature capture
- photo upload
- offline-aware draft handling

### Deliverables

- mobile-first worker home
- worker task list
- field execution flow
- submission confirmation and status tracking

### Exit Criteria

- a worker can complete a basic field workflow from phone-friendly screens
- submissions are saved cleanly with evidence attachments

## Phase 5: Form Runtime Engine

### Goal

Generalize the reporting and OHSMS layer into a reusable runtime engine.

### Scope

- inspections
- incidents
- permits to work
- return to work plans
- reporting forms
- register-style records

### Deliverables

- form definition model
- reusable renderer for structured forms
- conditional branching support
- submission storage model
- approval-ready workflow hooks

### Exit Criteria

- at least three distinct business forms run through the same engine
- form logic is not duplicated per module

## Phase 6: Compliance, Corrective Actions, and Output

### Goal

Convert captured activity into governance outputs.

### Scope

- compliance scoring
- corrective actions
- exception tracking
- dashboard status aggregation
- PDF generation
- audit trail reporting
- tamper-evidence hashes

### Deliverables

- admin compliance dashboard
- corrective action workflow
- generated PDF exports
- audit and evidence views

### Exit Criteria

- admins can see risk, completion, and exceptions from real workflow activity
- generated documents and audit records are available for key flows

## Phase 7: Tenant Customization and AI Services

### Goal

Introduce tenant-level differentiation and platform augmentation.

### Scope

- tenant-specific menus
- branding themes
- feature flags
- customer-specific content bundles
- AI-assisted content generation
- AI-assisted drafting and summarization

### Deliverables

- tenant configuration screens
- feature exposure controls
- AI service integration layer
- reviewable AI output workflow

### Exit Criteria

- one codebase can present different branded experiences
- AI can assist content workflows without bypassing review controls

## Demo-Oriented Milestones

Not all milestones need full backend completion to support stakeholder review. The following checkpoints are useful for showing progress to leadership.

### Milestone A: Product Shell Demo

Show:

- app shell
- module navigation
- admin dashboard
- worker home
- capability-based menu structure

Purpose:

- prove product direction and information architecture

### Milestone B: Business Identity Demo

Show:

- onboarding wizard
- business and workplace setup
- team directory seed data

Purpose:

- prove the platform foundation

### Milestone C: Task Pack Demo

Show:

- assigned task pack
- SWMS acknowledgement
- pre-start checklist
- completion summary

Purpose:

- prove field workflow viability

### Milestone D: Reporting and Compliance Demo

Show:

- inspection or incident flow
- corrective action trigger
- dashboard state update
- PDF preview

Purpose:

- prove governance value

## Recommended Frontend Priority

If the near-term goal is to show progress to stakeholders, frontend work should focus on a credible product narrative rather than backend breadth.

Recommended page priority:

1. admin dashboard
2. business identity onboarding
3. team directory
4. task pack library
5. worker mobile home
6. worker task execution flow
7. incident or inspection reporting page
8. compliance summary page

These pages communicate the platform much more effectively than building many low-fidelity CRUD screens.

## Team Working Model

To avoid drift during implementation:

- backend work should be owned by domain
- frontend work should be owned by user journey
- shared components should be limited and intentional
- all new modules should declare which engine they use

Every major feature should answer:

- which domain owns it
- which engine powers it
- which runtime records it creates
- which outputs it produces

## Risks to Manage

### Risk 1: Recreating AppSheet one file at a time

Impact:

- uncontrolled module sprawl
- repeated logic
- weak architecture

Mitigation:

- abstract by engine and domain

### Risk 2: Building frontend pages without real domain anchors

Impact:

- attractive demos that collapse during implementation

Mitigation:

- keep demo screens tied to the target data model and module map

### Risk 3: Underestimating identity complexity

Impact:

- broken onboarding
- invalid tenant scoping

Mitigation:

- prioritize Business Identity early

### Risk 4: Hardcoding workflow branching in UI components

Impact:

- fragile forms
- duplicated behavior

Mitigation:

- centralize rules and runtime definitions in backend models and services

## Immediate Next Actions

2026-05-18 update: the v0.3 migration-only reset has been generated and verified against local PostgreSQL. The next actions should stay backend-first until the application infrastructure matches the new schema.

1. Port backend infrastructure to v0.3: UUID `users.id`, account context, business/workplace scoping, audit service, core models, and tests.
2. Create a minimal v0.3 dev seeder/login path using `customer_accounts`, `business_entities`, `account_businesses`, `workplaces`, and the existing auth `users` table.
3. Build the importer engine slice for approved source tabs: occupations, industries, tasks, task access maps, SWMS versions, worker steps, and prestart questions.
4. Prove the first runtime path from imported content: worker task session, step read events, signature, prestart submission, evidence/audit/alert records.
5. Delete obsolete scaffold tests rather than maintaining old and new schemas in parallel.
6. Resume frontend/demo work only after the backend path above can create and query v0.3 data reliably.

## Final Recommendation

The project should be developed in a way that supports both:

- a polished product demonstration in the short term
- a scalable WHS platform in the long term

The correct sequence is:

`Core platform -> Business identity -> Task pack engine -> Worker runtime -> Form runtime -> Compliance output -> Tenant customization`

That sequence keeps the architecture coherent while still producing demo-visible progress at each stage.
