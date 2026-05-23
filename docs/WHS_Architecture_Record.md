# WHS App — Architecture & Discovery Record

> Status: historical business/product discovery context. For current schema truth, use the Laravel migrations, regenerated DBML, and `docs/README.md`.
>
> **Purpose:** Running record of system analysis for the WHS App rebuild.
> **Last updated:** 2026-05-06 (v14 — Voice call series: worker flow order corrected, configurable pre-start/post-task, training refresh intervals, PDF dual-layer model, Worker-Supervisor-Manager scope hierarchy, Stripe per-seat billing, content template automation, PDF generation mechanism (RMP 1–10), alert escalation chain, minimum read time UX rule, immutable records, peak load window, competitor Siculture.com)
> **BA:** Yiming (assisted by Claude)

---

## 1. Business Overview

**Company:** Workplace Health and Safety (workplacehealthandsafety.com.au / whsapps.com)

**What they sell:** Industry-specific WHS documentation packages, including:
- **SWMS** — Safe Work Method Statements (high-risk construction activities)
- **SOPs** — Standard Operating Procedures (equipment/task-level safety)
- **Management Systems** — Full WHS management system documentation

**Industries served:** Agriculture, Construction, Forestry, Manufacturing, Engineering, Mining, Public Service, Transport, Utilities

**Key value proposition:**
- Pre-built, compliant, easy-to-understand safety documents
- Free document branding service (add business logo, name, ABN)
- Affordable, accessible, real-world ready

**App vision (from 2023 home page):**
> "WHS Apps — Where Simplicity Meets Visibility. Real-time health and safety communication and compliance reporting, accessible anytime, anywhere."

---

## 2. Current AppSheet System — What We Actually Found

### Scale
| Metric | Count |
|---|---|
| Total files in Google Drive | 5,436 |
| Total folders | 2,212 |
| AppSheet apps (approx.) | 600+ |

### Critical Insight — It's NOT 600 Unique Apps
The 600+ AppSheet "apps" are **one repeating template** applied to hundreds of different equipment types and tasks. Each app folder in Google Drive contains mostly `empty.txt` + one icon image. The real app logic lived in AppSheet's cloud, not in Google Drive.

### App Categories (from `Appsheet Apps/` folder)
```
SOPS Apps/
  ├── Motor Vehicles
  ├── Plant and Equipment
  │   └── Concrete Pumps (Boom Pump, Line Pump, etc.)
  ├── Tools
  └── Workplace Safety Procedures

SWMS Apps/
  └── (largely empty / incomplete)
```

### Asset Register Categories (13 total)
1. Access, Height and Work Platforms
2. Appliances and Digital Assets
3. Electrical, Mechanical and Pressure Assets
4. Environmental and Monitoring Assets
5. Fixed Plant, Machinery and Processing Assets
6. Hazardous Chemicals and Dangerous Goods Assets
7. Load Shifting and Materials Handling Equipment
8. Mobile Plant and Heavy Equipment
9. PPE, Safety and Emergency Assets
10. Specialist and Industry-Specific Assets
11. Tools, Equipment and Trade Assets
12. Vehicles and Transport Assets
13. Workplace Infrastructure and Facilities

---

## 3. Document Analysis

### 3.1 WHS Apps PDF (SWMS Output Sample)
- **What it is:** A completed SWMS output document — i.e. what the app *produces* for the end user
- **Format:** Risk assessment table with structured columns
- **Fields captured:**
  - User ID, Company Name, ABN
  - Unit/Street/Suburb/City/State/Country/Postcode
  - Workplace Name + address
  - First Name, Last Name, Email, Mobile
  - SWMS Selected (task type)
  - Risk Assessment Table per hazardous task

- **Risk Table Structure:**

| Column | Description |
|---|---|
| Hazardous Task | What the worker is doing |
| Hazard Description | What could go wrong |
| Initial Risk Level | Before controls (Low/Medium/High/Extreme) |
| Control Measures | Steps to mitigate risk |
| Residual Risk Level | After controls applied |

- **Example:** Installation of Roof Trusses — 10 hazards identified including working at height (Extreme), structural instability (Extreme), use of power tools (High)

---

### 3.2 Home Page Doc (2023.04.28)
- **What it is:** Website copy for workplacehealthandsafety.com.au
- **Key insight:** The app was described as "upcoming" in 2023 — so development has been ongoing for 3+ years
- **Product categories sold:** SWMS Packs, SOP Packs, Management Systems — all industry-specific
- **Document branding service:** Add logo, business name, address, ABN — this is a core workflow to preserve in the rebuild

---

### 3.3 4WD Vehicle Inspection Checklist (Excel)
- **What it is:** A pre-operation/during-operation checklist for 4WD vehicles
- **Structure:** 20 checklist items, each a yes/no compliance question
- **Fields:** Item No. + Question text (response columns appear blank — likely filled in-app)
- **Sample questions:**
  - Valid driver's licence confirmed?
  - Pre-start inspection completed (fluids, brakes, tyres, lights, mirrors)?
  - PPE items available and worn?
  - Recovery gear present and in working condition?
  - Route assessed for weather, terrain, and hazards?
  - Vehicle inspected for damage after operation?

- **Pattern:** Pre-start → During operation → Post-operation structure

---

### 3.4 4WD Knowledge Assessment Quiz (Excel)
- **What it is:** A multiple-choice knowledge test with feedback for incorrect answers
- **Structure:** 20+ questions across 1 sheet (1000 rows allocated)
- **Fields per question:**
  1. Question No.
  2. Question text
  3. Correct Answer (prefixed A/B/C/D)
  4. Incorrect Options (semicolon-separated)
  5. Feedback if Answered Incorrectly

- **Topics covered:** 4WD system operation, PPE, terrain assessment, recovery procedures, risk levels, pre-start checks, hazard identification

- **Key insight:** The quiz is tightly coupled to the SWMS/SOP content — it's essentially a comprehension test on the safety document. This is a strong learning/compliance feature worth keeping.

---

### 3.5 Qld Health Sales Proposal (Qld Health Final Draft.docx)
- **What it is:** A formal sales pitch / proposal document from Kevin Gowdie (WHS Apps) to Tracey Wyatt (Queensland Health)
- **Date context:** Beta launch offer dated 01 July 2022 — gives us a clear timeline anchor
- **Key product description from this doc:**
  > "WHS Apps is a Software-as-a-Service (SaaS) tool that streamlines OHS management and provides information reports."

**Problems it solves (stated in the doc):**
- No centralised dashboard for WHS compliance status
- Teams using paper-based or spreadsheet systems
- No consistent way to schedule inspections
- No single view across all divisions

**Features promised:**
- Centralised hazard, incident, and inspection management
- Real-time notifications when submissions are made
- Offline capability — data saved locally, syncs when reconnected
- Data visualisation: videos, graphs, pie charts
- Filter data by location, industry, product, manager
- Custom menus and selections per client

**App creation process (how AppSheet apps were built):**
```
PDF document → Convert to Excel → Upload to AppSheet → Database with branching logic → Cloud storage
```
This confirms the current AppSheet workflow was essentially: take a paper WHS form, digitise it into Excel, then import into AppSheet. The rebuild should eliminate this manual conversion entirely.

**Pricing model (2022 rates — for scale reference):**

| Plan | Price/User | Qty (Qld Health) | Total |
|---|---|---|---|
| Monthly | $20/month | 4,500 | $90,000/month |
| Yearly | $240/year | 4,500 | $864,000/year (with 20% discount) |
| Early adopter monthly | $15/month | 4,500 | $67,500/month |

**Additional services:**
- Onboarding & Training: $250/hr or $2,000/day
- Custom development: quoted per scope

**Key insight:** This is enterprise SaaS targeting large organisations (Queensland Health = 4,500 users). The rebuild must support enterprise-scale multi-tenancy and have a proper subscription/billing model.

---

### 3.6 Incident Investigation Report (PDF)
- **What it is:** A structured form for recording workplace incidents
- **Major sections:**
  1. Incident classification + reporting details (date, time, who reported)
  2. Workplace identification (org name, ABN, address, contact)
  3. Investigating person's details (qualifications, role)
  4. Incident Investigation Team (multiple member slots)
  5. WHS Consultation Arrangements
- **Key insight:** Supports multiple investigation team members — needs a proper relational data model, not a flat spreadsheet

---

### 3.7 Team Directory Input (Excel)
- **What it is:** The schema/template for the team directory — defines all user types and profile fields
- **Structure:** 4,804 rows × 30 columns — functions as a branching decision tree for user classification

**User Profile Fields:**
| Field | Notes |
|---|---|
| Employee ID | Unique identifier |
| Photograph | Visual record |
| Title | Mr/Ms/Dr etc. |
| Family name | |
| Given names | |
| Date of birth | |
| Sex | |
| Street address, Suburb, City, State, Postcode | Full address |
| Mobile | |
| Email | |
| Industry | |
| Division | Org hierarchy level 1 |
| Sub-division | Org hierarchy level 2 |
| Group | Org hierarchy level 3 |
| User status | Active/Inactive etc. |
| User role | See role taxonomy below |

**Full User Role Taxonomy (critical for data model):**

```
BRANCH
├── Employees
│   ├── Contract employee
│   └── Employee
│       ├── Apprentice
│       ├── Casual
│       ├── Full-Time Permanent
│       ├── Full-Time Casual
│       ├── Part-Time Permanent
│       ├── Part-Time Casual
│       ├── Student
│       └── Volunteer
├── Labour Hire
│   ├── Labour hire manager
│   ├── Labour hire safety manager
│   ├── Apprentice
│   └── Employee
├── Contractor
│   ├── Asbestos removalist
│   ├── Cleaning → Cleaning contractor
│   ├── Construction
│   │   ├── Construction works
│   │   ├── Building services
│   │   ├── Trades
│   │   ├── Land/housing/residential
│   │   └── Heritage conservation consultants
│   ├── Demolisher → Demolition contractor
│   ├── Maintenance contractor
│   ├── Property management and maintenance
│   └── Service contractor
├── Fire and emergency services
│   ├── Emergency services (HAZCHEM Authority)
│   └── Fire brigade
├── Medical personnel (Workplace)
│   ├── Ambulance
│   ├── First aid attendant
│   ├── General practitioner
│   └── Nurse
└── Other
    ├── Client
    ├── Guest
    ├── Inspector / Regulator
    ├── Insurer
    ├── Solicitor
    └── Union representative
```

**Key insight:** This is far more complex than "worker + admin". The rebuild data model needs to handle a rich role taxonomy with 3-level org hierarchy (Division → Sub-division → Group). Permissions and document visibility will likely depend on role + hierarchy level.

---

## 3.8 Business Identity Information — AppSheet Prototype (LUKE - BII 1.docx)
- **What it is:** 73 screenshots of the live AppSheet Business Identity app — the complete onboarding flow for a new business
- **Key insight:** This is the working prototype Kevin wants us to replicate in OpsFortress/Laravel

**Full Onboarding Screen Flow:**

```
Business Identity Dashboard
        ↓
Business Type
  [Blockchain ID auto-generated, User Email auto-filled]
  → "Are you a sole trader, a company, or a partnership?"
        ↓
  ┌─────────────────────────────────────────────┐
  │ Sole Trader / Company branch:               │
  │  → Trading name, ABN, Business logo         │
  │  → Business Address (Primary Workplace)      │
  │     Street/Unit, Street Address,             │
  │     Suburb, City/Town                        │
  │  [Company only] → Company structure          │
  │     (e.g., Standard Pty Ltd Company)         │
  └─────────────────────────────────────────────┘
        ↓
  ┌─────────────────────────────────────────────┐
  │ Partnership branch:                          │
  │  → Partnership structure                     │
  │  → Partner role (Active/managing, Limited)   │
  │  → Partner identity (Individual/Company/Trust│
  │  → Partner details (name, ABN, address)      │
  │  → "Are there any other partners?" Yes→loop  │
  └─────────────────────────────────────────────┘
        ↓
Industry Classification
  → Industry Group 1 or 2
  → Company, Email, First/Last Name, Industry
  → Industry sub-category
    (e.g., Construction → Contractors/Services/Suppliers)
        ↓
Group Administrator Information
  → "Are you the Group Administrator?" Yes/No
  → If Yes: Title, Given names, Family name, Email, Mobile
  → Admin Type: Yes/No
        ↓
User Identity Admin confirmation
  → Given names, Family name, Email, Mobile, Admin Type
        ↓
Occupational Group selection
  → Clerical & Administrative Workers
  → Community & Personal Service Workers
  → Labourers
  → Machinery Operators & Drivers
  → Managers
     → Chief Executives/General Managers/Legislators
        → Managing Director / CEO / etc.
     → Farmers & Farm Managers
     → Hospitality/Retail/Service Managers
     → Specialist Managers
  → Professionals
        ↓
"Do you have any additional users?" Yes→loop / No→done
```

**Key data fields captured during onboarding:**

| Entity | Fields |
|---|---|
| Business | Blockchain ID, Business type, Trading name, ABN, Logo, Address |
| Partnership | Partner role, Partner type (Individual/Company/Trust), ABN, Address |
| Industry | Industry group, Industry sub-group, Sub-category |
| Group Admin | Title, Given names, Family name, Email, Mobile, Admin type |
| Occupation | Group → Sub-group → Sub-sub-group → Leaf occupation |
| Additional users | Looped entry until No |

**Critical finding — Blockchain ID:**
Every record in the system gets an auto-generated Blockchain ID (e.g., `dad90c29`, `3f9a89c2`). This is confirmed as a core system feature — not optional.

---

## 3.9 "Hanging a Door" Data Pack v1 (Excel — First Trial Run)
- **What it is:** The first trial data pack — 10 sheets, Laravel-ready content for the "Hanging a door" carpentry task
- **Purpose:** Proved the content-generation approach and defined the initial database schema
- **Status:** Superseded by v7 concrete blocks pack (see 3.10) — structure has evolved significantly

**10 Sheets and what they map to:**

| Sheet | Laravel Table | Purpose |
|---|---|---|
| README | — | Workbook purpose and instructions |
| SWMS_Data | `swms_records` | Full SWMS content (columns A–W) |
| SOP_Data | `sop_records` | Step-by-step SOP, 13 steps |
| PreStart_Checklist | `prestart_questions` | 30 questions with critical fail flags |
| PostTask_Report | `posttask_questions` | 12 post-completion questions |
| Training_Assessment | `training_questions` | 12 MCQ/Yes-No/True-False questions |
| Occupation_Access | `occupation_access` | 22 roles mapped to access levels |
| Dashboard_Rules | `dashboard_rules` | Scoring, alert and status logic |
| Laravel_Table_Map | — | Maps sheets to actual DB tables |
| Scoring_Logic | — | Pass/fail calculation rules |

**Confirmed Laravel DB Tables:**
```
tasks               → task identity (task_id, title, trade/industry)
swms_records        → SWMS content (FK: task_id)
sop_records         → SOP steps and sections (FK: task_id)
prestart_questions  → pre-start checklist items (FK: task_id)
posttask_questions  → post-task report items (FK: task_id)
training_questions  → scored assessment questions (FK: task_id)
occupation_access   → who can access what (FK: task_id)
dashboard_rules     → scoring and alert rules (FK: task_id)
submissions         → worker responses at runtime (FK: task_id, user_id, business_id, workplace_id)
corrective_actions  → actions generated from failures (FK: submission_id, task_id)
```

**Scoring Logic (confirmed):**

| Area | Rule |
|---|---|
| Pre-start Pass | Score ≥ 90% AND zero critical fails |
| Pre-start Conditional | Score 80–89% AND zero critical fails |
| Pre-start Blocked | Any critical fail = Do not commence |
| Post-task Failed | Any critical post-task No = corrective action required |
| Training Pass | Score ≥ 80% AND no critical safety question missed |
| Training Retraining | Fail OR assessment expired |
| Dashboard Green | Score ≥ 90% |
| Dashboard Amber | Score 80–89% |
| Dashboard Red | Score < 80% OR critical fail |

**Occupation Access Levels (4 tiers):**
- `Full task access` — primary trade (Carpenter, Joiner, Fix-out carpenter, Door installer)
- `Supervised access` — apprentices only
- `Support access` — trade assistants, labourers (assist only)
- `Management access` — leading hand, site supervisor, project manager
- `Conditional task access` — cabinetmakers, maintenance workers
- `Training access` — trainers and assessors
- `Review access` — WHS officers, safety advisors

**Key insight:** This was the first trial. Structure has since evolved — see 3.10 for the current standard.

---

## 3.10 "Lay Concrete Blocks" Data Pack v7 (Excel — Current Standard)
- **What it is:** The latest evolved data pack — 20 sheets, split into WHSAPP layer and OPSF layer
- **Task:** Lay concrete blocks (Blocklaying trade)
- **Key difference from v1:** Now explicitly separates WHS App content from OpsFortress master data

**20 Sheets — split into two layers:**

| Layer | Sheet | Purpose |
|---|---|---|
| WHSAPP | `WHSAPP_SWMS_Data` | SWMS A–W content (single row) |
| WHSAPP | `WHSAPP_PreStart_SWMS_15` | **Exactly 15** pre-start questions |
| WHSAPP | `WHSAPP_PostTask_SWMS_15` | **Exactly 15** post-task questions |
| WHSAPP | `WHSAPP_Training_SWMS` | 8 training questions |
| WHSAPP | `WHSAPP_Dashboard_Rules` | Scoring and alert rules |
| WHSAPP | `WHSAPP_Role_Permissions` | Role-based permissions |
| WHSAPP | `WHSAPP_PDF_Rules` | PDF export control |
| WHSAPP | `WHSAPP_Digital_Signatures` | Signature requirements |
| WHSAPP | `WHSAPP_Photo_Evidence` | Photo evidence rules |
| WHSAPP | `WHSAPP_Audit_Events` | Timestamped audit trail |
| WHSAPP | `WHSAPP_Blockchain_Logic` | Hash-chain and anchoring logic |
| OPSF | `OPSF_Occupation_Master` | 4-level occupation taxonomy |
| OPSF | `OPSF_Industry_Master` | 4-level industry taxonomy |
| OPSF | `OPSF_Task_Occupation_Access` | Task → occupation links |
| OPSF | `OPSF_Task_Industry_Access` | Task → industry links |
| OPSF | `OPSF_Laravel_Table_Map` | DB table mapping |
| OPSF | `OPSF_QA_Checklist` | QA checks (all passing) |

**New features vs v1 (Hanging a Door):**

**1. Role Permissions (simplified and formalised):**

| Role | View | Submit | Management Review | PDF Export |
|---|---|---|---|---|
| Worker | ✅ | ✅ | ❌ | ❌ |
| Apprentice/Trainee | ✅ | ✅ (supervised) | ❌ | ❌ |
| Supervisor | ✅ | ✅ | ✅ | Conditional |
| Manager | ✅ | ❌ | ✅ | ✅ |
| WHS Officer | ✅ | ❌ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ |

**Workers CANNOT export PDFs** — management only. This is a deliberate control.

**2. Blockchain Logic (confirmed core feature):**

| Event | DB Record | Hash Chain | Blockchain Anchor |
|---|---|---|---|
| Page viewed | ✅ | ✅ | ❌ (high volume) |
| Pre-start submitted | ✅ | ✅ | ✅ |
| SWMS acknowledged | ✅ | ✅ | ✅ |
| Training completed | ✅ | ✅ | ✅ |
| Post-task submitted | ✅ | ✅ | ✅ |
| PDF exported | ✅ | ✅ | ✅ |

**3. Digital Signatures:** Worker must digitally sign SWMS acknowledgement (required, not optional)

**4. Photo Evidence:** Conditional — required for pre-start hazards and post-task defects

**5. OpsFortress Occupation Taxonomy (4 levels):**
```
Group → Sub-group → Sub-sub-group → Leaf occupation
e.g.: Building Structure Services → Bricklaying and blocklaying → Blocklaying → Blocklayer
```

**6. Central OpsFortress source pack** (referenced but not included):
- `jurisdiction_profiles` — jurisdiction/regulator/legislation data
- `regulator_contacts`
- `terminology_profiles`
These are GLOBAL tables shared across all tasks, stored separately.

**7. QA Checklist** — every data pack must pass before import:
- Exactly 15 pre-start questions ✅
- Exactly 15 post-task questions ✅
- Workers cannot export PDF ✅
- Blockchain logic present ✅
- No duplicate master tabs ✅

---

## 3.11 Work Directory Path (Excel — Full System Scope Map)
- **What it is:** A complete hierarchical directory of every AppSheet app and data table in the system — the definitive scope map for the rebuild
- **Key insight:** The system is MUCH larger than the SWMS/SOP pilot suggested. There are ~110 distinct AppSheet apps across 5 major modules.

**Complete Module Hierarchy:**

```
WHS Apps Data  [OpsFortress Layer]
├── Core App Files
│   └── AppSheet apps: WHSAPPSLauncher, WHSAppsSetPermissions,
│       WHSAPPSSignUp, WHSAppsStart, WHSAppsUserPermissions
├── Company Information
│   ├── AppSheet apps (9): BusinessIdentity, CompanyIdentity,
│   │   CompanyInformation, PartnershipIdentity, PrivateInformation,
│   │   Public, TradieIdentity, WorkerIdentity, WorkplaceLocations
│   └── Data tables (12): Business Identity, Company Identity,
│       Company Information, Partnership Identity, Private Information,
│       Public, Set Permissions, Tradie Identity, User Identity,
│       User Permissions, Worker Identity, Workplace Locations
├── Industry Groups
│   ├── AppSheet apps (6): IndustryClassification, IndustryGroup3,
│   │   IndustryGroup2–5, IndustryGroups
│   └── Data tables (2): Industry Classification, Industry Groups
└── Occupational Groups
    └── AppSheet apps (1): WHSAPPSOccupation

WHS Apps Registers
└── Asset Register
    ├── AppSheet apps (2): WHSAppsAgitators, WHSAppsAssetRegister
    └── Data: WHS Apps Agitators, WHS Apps Asset Register

WHS Apps SOPS
├── General (5 apps): SOPSPostAssessment, SOPSPostInspection,
│   SOPSPreStartInspection, SOPSTrainingAssessment, WHSAppsChecklists
├── Motor Vehicles (2 apps): DocumentsMotorVehicles, SOPSMotorVehicles
├── Plant and Equipment (9 apps): ConcretePumps, Cranes,
│   EarthmovingEquipment, HoistsLiftingGear, HeightAccessEquipment,
│   IndustrialTrucks, LandscapingEquipment, PilingDrillRigs,
│   PlantandEquipment (main)
├── Tools (7 apps): ConstructionTools, CuttingTools, EPT-REBATTOOLS,
│   IndustrialToolsandEquipment, Lasers/ToolSafety/Welding, SOPSTools
└── Workplace Safety (2 apps): DocumentsWorkplaceSafety,
    SOPSWorkplaceProcedures

WHS Apps SWMS
├── Core (2 apps): WHSAppsDigitalSWMS, WHSSWMS
├── Construction Trades (4 apps): Documents, Post, PreStart, Training
├── Finishing Trades (5 apps): Documents, Post, PreStart, SWMS, Training
├── Installation Trades (4 apps): Documents, Post, PreStart, Training
├── Non-Building (4 apps): Documents, Post, PreStart, Training
├── Preliminary Works (5 apps): Documents, Post, PreStart ×2, Training
└── Work at Heights (4 apps): Documents, Post, PreStart, Training

OHSMS / WHS Apps Reporting  [~40+ apps]
├── Consultation: AgreedConsultationProcedures, RecordofConsultation,
│   SafetyCommitteeAgenda/Minutes, WHSConsultation, ToolboxMeeting
├── Capability Assessments: CapabilityAssessmentContract/Employee
├── Drills: EmergencyEvacuationDrill, FireEvacuationDrill,
│   WorkplaceEmergencyPlan, WorkplaceFireEvacuationPlan
├── Inspections: CraneInspections, FirstAidEquipment/Room,
│   MotorVehicleInspections, MobilePlant/PreOp, PortablePlant,
│   StaticPlant, ScaffoldInspection, WorkplaceInspection,
│   MaterialsProducts, Inspections (general)
├── Notices: ImprovementNotice, ProhibitionNotice, WHSCompliance
├── OHS Gap Analysis + OHSMS Systems Audit
├── Permits: Hot Work Permits
├── Registers: HazardousChemicalRegister, PPERegister
├── Rehabilitation: WHSAppsRehabilitation
├── Reporting: HazardReporting, HazardandIncidentManagement,
│   IncidentReporting, FirstAidReporting
├── Risk Assessments: ChemicalRiskAssessment, RiskAssessment
├── SWMS Template: OnlineSWMS
├── Training: WHSAppsTraining
└── Visitors: VisitorDeliveryRegistration, VisitorRegistration,
    VisitorsLog, WorkplaceManagement
```

**Full Rebuild Scope — Module Count:**

| Module | AppSheet Apps | Notes |
|---|---|---|
| Core Data / OpsFortress | ~15 | Users, Businesses, Workplaces, Industry, Occupations |
| Registers | 2 | Asset register |
| SOPS | ~25 | 4 trade categories |
| SWMS | ~28 | 6 trade categories |
| OHSMS / Reporting | ~40 | 12+ sub-types — the largest module |
| **Total** | **~110** | **1 unified Laravel platform** |

**Key finding:** The OHSMS/Reporting module is the largest and was almost entirely absent from the trial data packs. It includes Safety Committee, Toolbox Meetings, Permits, Rehabilitation, Audits, and 5 different inspection types — all need their own data structures in Laravel.

**Known legacy bug visible:** One entry is marked `FIX - Height` next to `SopsHeighAccessEquipment-4238531` — a known incomplete app in the legacy system.

**NEW findings from full re-read (v9):**

| Discovery | AppSheet ID | Implication for rebuild |
|---|---|---|
| **OpenAI integration** | `WHSAppsOpenAi-4238531` | Kevin already built an OpenAI-connected AppSheet app — AI content generation is a live feature, not just a plan |
| **Queensland Health custom app** | `QLDHealthApp-4238531` | A client-specific app exists — multi-tenant white-labelling is a real requirement, not hypothetical |
| **Alpha testing app** | `WHSAPPSAlphaTesting-4238531` | A dedicated alpha environment was maintained — suggests we need dev/staging/prod environment parity |
| **Video-based company info app** | `CompanyInformatiomVideoApp-4238531` | Video delivery of company information is a distinct feature (note: name has typo "Informatiom") |
| **Facility assets module** | `FacilityAssets-4238531` | Separate from `WHSAppsAssetRegister` — facility-level asset tracking is a distinct concept |
| **Dated construction SOPS** | `20250106-SOPS-FINAL-CONSTRUCTION-4238531` | Construction SOPS were finalised January 6, 2025 — recent, likely stable content for seeding |
| **Backup app** | `WHSAppsBackup-4238531` | Kevin was manually backing up AppSheet configs — confirms no CI/CD existed |

**Active development timeline confirmed by date-versioned AppSheet IDs:**
```
SWMSFinishingTrades-4238531-25-05-16         → May 16, 2025
SOPSPostInspection-4238531-25-07-01          → July 1, 2025
SOPSPreStartInspection-4238531-25-08-13      → August 13, 2025
SOPSTrainingAssessment-4238531-25-08-13      → August 13, 2025
PreStartPreliminaryWorks-4238531-25-10-30    → October 30, 2025
```
Kevin was still versioning and pushing changes to AppSheet as recently as October 2025 — the legacy system was in active use right up until the rebuild decision. Some of these dated versions co-exist with the originals (not replacements), suggesting A/B testing or staged rollout.

**OHSMS AppSheet apps — full confirmed list (50 apps):**
```
Consultation layer:
  AgreedConsultationProcedures, RecordofConsultation,
  SafetyCommitteeAgenda, SafetyCommitteeMinutes,
  WHSConsultation, WHSAppsToolboxMeeting

Capability Assessments:
  CapabilityAssessmentContract, CapabilityAssessmentEmployee

Risk / Chemicals:
  ChemicalRiskAssessment, WHSAppsRiskAssessment,
  WHSAppsHazardousChemicalRegister

Incidents / Hazards:
  HazardandIncidentManagement, WHSAppsHazardReporting,
  WHSAppsIncidentReporting, WHSAppsFirstAidReporting

Inspections — General:
  Inspections, WHSAppsWorkplaceInspection,
  WHSAppsScaffoldInspection, WHSAppsMaterialsProducts

Inspections — Plant & Equipment:
  WHSAppsMobilePlant, WHSAppsMobilePlantPreOp,
  WHSAppsPortablePlant, WHSAppsStaticPlant,
  WHSAppsCraneInspections

Inspections — Vehicles:
  WHSAppsMotorVehicle, WHSAppsMotorVehicles  ← TWO motor vehicle apps (possible duplicate or versioned)

First Aid:
  WHSAppsFirstAidEquipment, WHSAppsFirstAidInspections,
  WHSAppsFirstAidRoom

Notices:
  WHSAppsImprovementNotice, WHSAppsProhibitionNotice,
  WHSCompliance

Drills / Emergency:
  EmergencyEvacuationDrill, WHSAppsFireEvacuationDrill,
  WorkplaceEmergencyPlan, WorkplaceFireEvacuationPlan

Registers:
  Registers, WHSAppsPPERegister  (+ RegistersAugust2024 — legacy versioned data)

Permits:
  Hot Work Permits

Reporting:
  OHSGapAnalysisReport, WHSAppsOHSMSSystemsAudit,
  WHSAppsOnlineSWMS, WHSAppsRehabilitation

Visitors:
  VisitorDeliveryRegistration, WHSAppsVisitorRegistration,
  WHSAppsVisitorsLog, WorkplaceManagement
```

**Motor vehicle duplication flag:** Both `WHSAppsMotorVehicle-4238531` and `WHSAppsMotorVehicles-4238531` exist under OHSMS. This is either a versioning artefact or two distinct forms (e.g. inspection vs registration). Needs clarification from Kevin before building the `motor_vehicle_inspections` table.

---

## 3.12 INTERNS BII — Yiming's Version (Additional Partnership Paths Confirmed)
- **What it is:** 65 screenshots of the same Business Identity onboarding (see 3.8), but with full walkthroughs of ALL partnership sub-paths including Trust
- **Blockchain IDs visible (multiple test runs):** `dad90c29`, `3f9a89c2`, `65e5552c`, `df4ebbae`, `caa5f195`, `71475a7d`, `0b4d0e31`, `adae964c`, `6dcd7abd`

**Partnership → Partner Identity branching (complete — extends 3.8):**

```
Partner Details
  What is the partner's role?
  ├── Active or managing partner
  └── Limited partner

Partner Identity  ("A partner doesn't have to be an actual person.
  A company or trust is considered a 'legal person'")
  Are you an individual, company, or trust?
  ├── Individual
  │   Blockchain ID, User Email, Title, Given names, Family name,
  │   Sex, Date of Birth, Residential Address (Street/Unit,
  │   Street, Suburb, City/Town, State/Territory, Post/Zip),
  │   Contact Details (Email, Mobile)
  ├── Company
  │   Blockchain ID, User Email, Company name, ABN,
  │   Company Structure (e.g. Standard Pty Ltd Company),
  │   State/Territory, Contact Person (Title, Given names,
  │   Family name), Contact Details (Email, Mobile), Post/Zip Code
  └── Trust
      Blockchain ID, User Email, Trust structure
      (e.g. Fixed trust / Unit trust), Trust name, ABN for trust
      Trustee: Company OR Individual
      └── [Same sub-fields as Company / Individual above]
```

**User Identity confirmation (2 variants confirmed):**

| Screen | Admin Type field value |
|---|---|
| User Identity Admin | **Yes** (green toggle) |
| User Identity Non Admin | **No** (green toggle) |

Fields: Given names, Family name, Email, Mobile, Admin Type (Yes/No)

**Industry → Construction sub-path (field detail confirmed):**
```
Construction → "Contractors, Services and Suppliers"
  → [Contractors] | [Services] | [Suppliers]  (button selector)
    → Contractors sub-dropdown (e.g. "Bricklaying")
    → Client info fields: Company, Email, First Name, Last Name, Industry
```

**Key difference vs Luke's BII:** Luke's doc showed the overall flow; Yiming's shows every individual field in sequence for ALL branches — the definitive field-level specification for OpsFortress data modelling.

---

## 3.13 Kevin's AI Content Generation Prompts (Prompts/ folder — 9 Excel files)
- **What it is:** Kevin's prompt templates for using AI to auto-generate all data pack content (pre-start checklists, post-task reviews, training assessments) from source safety documents
- **Key insight:** ALL question content in the data packs is AI-generated from source PDFs/Excel docs using these templates — not written by hand

**Prompt system overview:**

| File | Scope | Output |
|---|---|---|
| SOPS - PRE-START | 85 vehicle SOPs (4WD, trucks, trailers) | Pre-start yes/no checklist, single-row Excel |
| SOPS - POST-START | Same 85 SOPs | Post-task closure checklist |
| SOPS - ASSESS | Same 85 SOPs | 12-question training assessment |
| SWMS - PRE-START | 40 construction/carpentry SWMS | Pre-start yes/no checklist |
| SWMS - POST-TASK | Same 40 SWMS | Post-task closure checklist |
| SWMS - ASSESS | Same 40 SWMS | 12-question training assessment |
| PRE START PROMPT | Generic master prompt | Pre-start template |
| POST TASK PROMPT | Generic master prompt | Post-task template |
| ASSESS PROMPT | Generic master prompt | Assessment template |

**Pre-start/Post-task checklist output format (confirmed):**
```
Columns: Timestamp | Blockchain ID | User Email | Business Name |
         [Yes/No questions grouped by WHS control hierarchy] |
         Photo_1 ... Photo_6 | Digital Signature
```
Questions grouped by: Work Area Prep → Materials & Equipment → Manual Handling → Chemical/Dust Exposure → Safe Procedures → PPE → (task-specific categories)

**Assessment structure — CORRECTED: 15-question fixed sequence (NOT 12):**
```
15 questions per training set — pass mark = 12/15 (80%)
Mix of Yes/No, True/False, and Multiple Choice (4 options A/B/C/D)
Each question has: correct_answer, critical_fail flag,
  corrective_action_required, feedback_if_incorrect, learning_message
```
> ⚠️ **v11 CORRECTION:** Earlier records stated 12 questions. Production workbook QA-028 confirms 15. The "12" figure was the pass mark (12/15 = 80%), not the question count.
**Column format per question:**
- A = Topic (extracted from document title)
- B = Subtopic (auto-assigned by row position)
- C = Question Type (Multiple Choice / True or False)
- D = Question text
- E–H = Options A/B/C/D (T/F uses A only)
- I = Correct Answer ("Option A/B/C/D")
- J = Explanation/rationale

**4 answer variation patterns** (A/C/B/D rotation) to randomise correct answer position across question banks.

**Key architectural implication:** The Laravel import system needs to accept this exact column format. The 12-question fixed structure and the pre-start single-row format are the output contracts Kevin has already standardised.

---

## 3.14 Hot Work Permit — Branching Process Map (Adam/Hot Work Permit BRANCHING.xlsx)
- **What it is:** Full branching logic for the Hot Work Permit — the only Permits to Work data structure found so far
- **Key insight:** Permits to Work have conditional approval gates based on environmental conditions — not just a simple form

**Three-phase workflow:**
```
Phase 1: BEFORE hot work commences
  → Inspect area + determine safety precautions
  → Determine fire watch duration (based on hazard level)
  → Check if fire suppression system interruption needed → get authorisation
  → Complete permit form with signature
  → Submit signed copy to Manager/Supervisor

Phase 2: DURING hot work
  → Consult permit for specific precautions
  → Complete work within time limit
  → Maintain fire watch at all times
  → Keep signed permit on site

Phase 3: COMPLETION
  → Maintain fire watch for designated hours after completion
  → Sign off fire watch completion
  → Perform final check + sign permit closure
  → Submit closed permit to Manager/Supervisor
```

**Branching decision gates:**
```
Has risk assessment been undertaken?  → Yes / No
Does task involve hot work?           → Yes / No
Is hot work in designated zone?       → Yes / No
Is fire ban in place?                 → Yes / No (if yes → Do not proceed)
Fire danger rating:
  → Low-Moderate  → proceed with standard precautions
  → High          → enhanced precautions
  → Very High     → Do NOT proceed without Manager OR Emergency Services approval
  → Severe        → Do NOT proceed without Manager OR Emergency Services approval
  → Extreme       → Do NOT proceed without Manager OR Emergency Services approval
  → Catastrophic  → Do NOT proceed without Manager OR Emergency Services approval
Approval given by authorised person?  → Yes / No
```

**Key data fields captured:**
```
Permit: start date/time, expiry date/time, work order number
Nature of hot work: dropdown (27 types — welding, brazing, cutting,
  grinding, soldering, thermal spraying, torch-applied roofing, etc.)
Work description: free text
Workplace: org name, ABN, name, street, suburb, city, state, postcode,
  phone, fax
Workplace classification: Low Risk / High Risk / Remote
Business nature: free text
Contact person: Title, family name, given names, position, mobile, email
Principal contractor: trading name, ABN, business structure
  (Limited by Guarantee, Sole Trader, Pty Ltd, etc.)
Contractor (PCBU): same fields as principal contractor
Authorised person: name + signature + date
Fire watch: officer name + sign-off + times
```

**Laravel table implications:**
```
permits                → permit header (type, dates, work_order, status)
permit_workplace       → workplace identification (FK: permit_id)
permit_parties         → principal contractor + PCBU (FK: permit_id)
permit_hazard_gates    → decision branch outcomes (FK: permit_id)
permit_fire_watch      → fire watch log entries (FK: permit_id)
permit_authorisations  → named approvals with timestamps (FK: permit_id)
```

---

## 3.15 OHSMS Form Templates Survey (Old WHS APPS Excels/ — 62 files)
- **What it is:** The complete set of legacy AppSheet data source files for the OHSMS/Reporting module — 62 Excel files covering every form type
- **Key insight:** Two distinct architectural layers visible — dashboard/navigation (3-col) + data forms (160–1019 col)

**Two structural patterns found:**

| Type | Count | Column count | Purpose |
|---|---|---|---|
| Dashboard/View layer | 27 files | 3 cols (`View Name \| Details \| Icon`) | App menu/navigation structure |
| Form data export | 35 files | 87–1019 cols | Actual form fields and responses |

**Universal form header (all 35 data forms):**
```
Timestamp | Logo | First Name | Last Name | Email | Report ID |
Page Header | Image Logo | [content columns] | Declaration | Signature | Date
```

**Form categories and column counts (key forms):**

| Category | Key Forms | Columns | Notable fields |
|---|---|---|---|
| **Inspections** | Workplace Inspection | 1,019 | Plant, electrical, ventilation matrix |
| | Crane Pre-Op | 848 | Detailed crane-specific checks |
| | Chemical Risk Assessment | 656 | Enum sheet for dropdowns; exposure controls |
| | Incident Investigation Report | 597 | 20 recipient notification slots |
| | Mobile Plant Inspection | 443 | |
| | Fire Evacuation Drill | 497 | Full evacuation procedure validation |
| | Emergency Evacuation Drill | 461 | |
| **Assessments** | Return to Work Plan | 331 | Post-injury coordination |
| | Toolbox Meeting Form | 277 | Weather, agenda, 20 attendee roster |
| | Record of Consultation | 291 | Worker/contractor consultation |
| | Safety Committee Minutes | 325 | Structured meeting recording |
| | Risk Assessment | 160 | Likelihood/consequence matrix; declaration |
| | OHSMS Systems Audit | 193 | Yes/No compliance, documentation control |
| **Registers** | First Aid Injury Register | 215/177 (2 sheets) | Location field; treatment recorded |
| | Visitors Log | 190 | Entry/exit tracking |
| **Notices** | Improvement Notice | 146 | Contravention date, scope |
| | Prohibition Notice | 128 | |
| | Contravention Notice | 144 | |

**No blockchain fields** in legacy OHSMS forms — blockchain was added later by Sushma as a prototype (see 3.16).

---

## 3.16 Blockchain — What It Actually Is (CRITICAL CLARIFICATION)
- **Source:** `Sushma/Capability Assessment Contractor Blockchain.xlsx`
- **Key revelation:** "Blockchain" in this system is NOT a real distributed ledger — it is MD5 hash-based tamper detection

**How it works in the Excel prototype:**
```
Column A (live hash):     =MD5(CONCATENATE(C2:C4000))
Column B (original hash): d41d8cd98f00b204e9800998ecf8427e  [stored on creation]
Column C: Timestamp
```
- On record creation: hash of all data is computed and stored as `OriginalHash`
- On any future read: live hash is recomputed and compared to `OriginalHash`
- If they differ → data has been tampered with

**What "Blockchain ID" (e.g. `dad90c29`) actually is:**
- An 8-character truncated hash used as a unique record identifier
- Auto-generated at record creation time
- Provides tamper-evident audit trail without any real blockchain network

**Laravel implementation implication — this is SIMPLE:**
```php
// On record create:
$record->blockchain_id = substr(md5(uniqid()), 0, 8);
$record->original_hash = hash('sha256', json_encode($record->toArray()));

// On record read/verify:
$live_hash = hash('sha256', json_encode($record->fresh()->toArray()));
$is_tampered = ($live_hash !== $record->original_hash);
```
No Web3, no Ethereum, no external blockchain service needed. Just SHA-256 (more secure than MD5) stored in the DB.

> ⚠️ **v11 CORRECTION:** `previous_hash_required: Yes` in production workbook confirms this is a **hash-chain**, not isolated point-in-time hashes. Each signed record must reference the previous record's hash. Two anchor events defined: `HASH-001` = digital signature finalisation; `HASH-002` = post-task closeout approval. Laravel implementation must store and thread `previous_hash` on every audit record.

---

## 3.18 Workbook Internal Tab Structure — The Import Contract (CRITICAL)

- **Source:** Kevin's ChatGPT conversation (shared April 29, 2026) + voice recording
- **Key revelation:** Every SWMS/SOP workbook has a standardised set of 20+ named tabs. Kevin has already built `OPSF_Laravel_Table_Map` and `OPSF_QA_Checklist` inside each workbook — he is actively mapping his own content to Laravel tables.

**This is the definitive import contract for the Task Pack Engine.**

### WHSAPP_ tabs — WHS App content layer

| Tab name | Purpose |
|---|---|
| `README` | Data pack metadata, import notes |
| `WHSAPP_Index` | Task pack index and task name |
| `WHSAPP_SWMS_Data` | Main SWMS display content — primary PDF source |
| `WHSAPP_PreStart_SWMS_15` | 15-question pre-start checklist |
| `WHSAPP_PostTask_SWMS_15` | 15-question post-task close-out |
| `WHSAPP_Training_SWMS` | Training quiz question bank |
| `WHSAPP_Dashboard_Rules` | Dashboard display and scoring logic |
| `WHSAPP_Dashboard_Data_Map` | Role-based reporting field mapping |
| `WHSAPP_Role_Permissions` | Role visibility rules per task |
| `WHSAPP_PDF_Rules` | PDF generation and layout rules |
| `WHSAPP_Digital_Signatures` | Signature requirement and capture rules |
| `WHSAPP_Photo_Evidence` | Photo upload rules and requirements |
| `WHSAPP_Audit_Events` | Audit log event rules |
| `WHSAPP_Blockchain_Logic` | Hash tamper-detection rules |

### OPSF_ tabs — OpsFortress core layer

| Tab name | Purpose |
|---|---|
| `OPSF_Index` | Pack-level index |
| `OPSF_Occupation_Master` | Occupation list for this task |
| `OPSF_Industry_Master` | Industry list for this task |
| `OPSF_Task_Occupation_Access` | Which occupations can access this task |
| `OPSF_Task_Industry_Access` | Which industries can see this task |
| `OPSF_Laravel_Table_Map` | **Direct mapping of columns to Laravel DB tables** |
| `OPSF_QA_Checklist` | Import validation rules and admin QA checklist |
| Permit maps | Hot Work / Confined Space / WAH permit rules (later phases) |

**Architectural implication:** The Task Pack import engine (Week 3 of build) should read `OPSF_Laravel_Table_Map` first to determine column-to-table mappings, then use `OPSF_QA_Checklist` to validate before committing. Kevin has already done the hard work of defining the mapping — the Laravel importer just needs to honour it.

### MVP tabs: open first vs. delay

**Open in MVP (Phase 1):**
`README`, `WHSAPP_Index`, `WHSAPP_SWMS_Data`, `WHSAPP_PreStart_SWMS_15`, `WHSAPP_PostTask_SWMS_15`, `WHSAPP_Training_SWMS`, `WHSAPP_Role_Permissions`, `WHSAPP_PDF_Rules`, `WHSAPP_Digital_Signatures`, `WHSAPP_Photo_Evidence`, `WHSAPP_Audit_Events`, `OPSF_Occupation_Master`, `OPSF_Industry_Master`, `OPSF_Task_Occupation_Access`, `OPSF_Task_Industry_Access`, `OPSF_Laravel_Table_Map`, `OPSF_QA_Checklist`

**Delay to Phase 2+:**
`WHSAPP_Dashboard_Rules`, `WHSAPP_Dashboard_Data_Map` (use simple counts first), `WHSAPP_Blockchain_Logic` (valuable but not a launch blocker), Permit maps (Phase 3)

---

## 3.19 Revised Scale, Data Migration Path & Three UI Layers

### Scale correction (voice recording — April 29, 2026)

| Layer | Previous understanding | Corrected understanding |
|---|---|---|
| AppSheet apps | ~110 | ~110 — correct (these are the *application* layer) |
| Content workbooks | Not previously known | **~1,700 workbooks** — one per SWMS or SOP |
| Content creators | Kevin | Kevin + Brodie + Lisa (actively building now) |
| Content ETA | Unknown | **2–3 weeks** from April 29 |

**Implication:** The import engine must handle bulk ingestion of 1,700 files. Performance, error handling, and the `import_validation_results` table are not optional — they are essential from Week 3. A batch import CLI command (`php artisan import:taskpack`) is more practical than a UI upload for initial seeding.

### Data migration path confirmed (voice recording)

```
Kevin / Brodie / Lisa
  → build workbooks in Google Sheets (interim storage)
  → Python script reads Google Sheets API
  → Transforms and loads into PostgreSQL
  → Laravel Eloquent models read from PostgreSQL
```

This means the dev team needs a **Python migration script** in addition to the Laravel import engine. The two are complementary:
- Python script: one-time bulk migration from Google Sheets (used during content prep phase)
- Laravel import engine: ongoing — used whenever Kevin adds a new task pack post-launch

### Three UI layers confirmed (voice recording + Yiming's Cody build)

| UI layer | Users | Status |
|---|---|---|
| **Admin / Manager Dashboard** | Business owners, supervisors | Yiming building in Cody (Cursor) |
| **Business Identity Setup** | New businesses onboarding | Based on BII document (Kevin's "final prompt" ready tonight) |
| **Worker Mobile** | Field workers | Mobile-first, fast task execution |

Yiming confirmed he is already building the frontend using Cody/Cursor based on the BII document. Kevin's "final prompt" (for the BII onboarding flow) was being finalised the evening of April 28.

### ChatGPT's 8-week MVP build plan — validated and adopted

ChatGPT independently arrived at a build plan that matches the TARGET_ARCHITECTURE.md phase ordering. This is the agreed delivery sequence:

| Week | Goal | Critical deliverables |
|---|---|---|
| 1 | Project lock + DB foundation | Laravel project, PostgreSQL, Auth, Tenancy model, 5 base roles, first migrations |
| 2 | Core business entities | tenants, businesses, workplaces, workers, teams, roles, permissions, branding placeholder |
| 3 ⚠️ | **Task Pack import engine** | Upload .xlsx, parse tabs, validate structure, store versions, import SWMS/SOP/pre-start/training content |
| 4 | SWMS/SOP delivery | Task assignment, worker task list, SWMS viewer, SOP viewer, read acknowledgement, audit events |
| 5 | Pre-start / Post-task / Training | Form runtime, pass/fail scoring, critical-fail flag, basic corrective action |
| 6 | Signatures + Photos + PDFs | Digital signature capture, S3 photo upload, PDF generation queue, evidence bundle |
| 7 | Dashboard + pilot hardening | Admin/supervisor dashboards, mobile UI, bug fixes |
| 8 | **Paid pilot release** | 5–10 pilot businesses, monitoring, support, backup process |

**Week 3 is the critical path.** A clean import engine unlocks everything downstream. If the import is messy, all 1,700 workbooks become a liability instead of an asset.

### ChatGPT's flagged risks (scope control — important)

These risks were independently identified and match BA concerns:

| Risk | Mitigation |
|---|---|
| Over-engineering "reusable engines" too early | Build Task Pack engine for SWMS/SOP first; generalise only after Week 4 is proven |
| No clear MVP definition → scope creep | Lock the 15-item MVP feature list in Week 1 and do not add to it |
| Offline not properly addressed | Phase 1 = "offline-aware" (cached pages); Phase 2 = offline draft saving; Phase 3 = conflict sync |
| AI-assisted dev without control = technical debt | Code reviews at end of each week; domain boundaries enforced from Week 1 |

### Core MVP flow (agreed commercial proposition)

```
Business created
  → Workers added
  → Task pack imported (from workbook)
  → Task assigned to worker / team / site
  → Worker reads SWMS / SOP
  → Worker completes pre-start checklist
  → Worker completes training quiz
  → Worker signs (digital signature)
  → Worker uploads photo evidence
  → PDF generated and stored
  → Dashboard updated
```

**MVP positioning:** "Task-based SWMS and SOP compliance for Australian businesses — worker sign-off, pre-starts, training records, photo evidence and audit-ready PDFs." Not: "Complete WHS management system."

---

## 3.20 Production SWMS Workbook — Full 65-Tab Structure (DEFINITIVE)

- **Source:** `WHS_App_OpsFortress_SWMS_Only_Mixing_Of_Mortar_v1_production.xlsm`
- **All 50 QA checks: PASS** — this is the locked, production-ready template
- **Key corrections vs. previous records:** 65 tabs (not 20), training = 15Q (not 12Q), blockchain = hash-chain

### Complete 65-Tab Inventory

**WHSAPP layer — Content & Registers (58 tabs):**
```
Core delivery (MVP):
  README, WHSAPP_Index, WHSAPP_SWMS_Data,
  WHSAPP_Worker_App_View_Map,          ← 34-col reduced worker view with icons
  WHSAPP_PreStart_SWMS_15,             ← 15 Yes/No questions
  WHSAPP_PostTask_SWMS_15,             ← 15 Yes/No questions
  WHSAPP_Training_SWMS                 ← 15 questions, pass = 12/15 (80%)

Permit system (per-task, embedded):
  WHSAPP_SWMS_Permit_Map,
  WHSAPP_Permit_Type_Register,
  WHSAPP_Permit_Trigger_Matrix,
  WHSAPP_Permit_Wording_Library

Risk & control architecture:
  WHSAPP_Control_Hazard_Link_Map,
  WHSAPP_Critical_Control_Verif

Stakeholder registers:
  WHSAPP_Worker_Register,
  WHSAPP_Contractor_Register,          WHSAPP_Contractor_Task_Link_Map,
  WHSAPP_Supplier_Register,            WHSAPP_Supplier_AssetChem_Link,
  WHSAPP_Workplace_Register

Content registers (task decomposition):
  WHSAPP_Task_Register,                WHSAPP_Activity_Register,
  WHSAPP_Hazard_Register,              WHSAPP_Control_Register,
  WHSAPP_Asset_Register,               WHSAPP_Asset_Task_Link_Map,
  WHSAPP_Asset_Inspection_Rules,
  WHSAPP_HazChem_Register,             WHSAPP_HazChem_Task_Link_Map,
  WHSAPP_HazChem_Control_Rules,
  WHSAPP_Training_Register,            WHSAPP_Task_Training_Link_Map,
  WHSAPP_Activity_Training_Link,
  WHSAPP_Workplace_Induction,          ← ANSWERS OPEN QUESTION: induction IS here
  WHSAPP_Task_Induction_Register,
  WHSAPP_Competency_Register,
  WHSAPP_Licence_Register,
  WHSAPP_PPE_Register,
  WHSAPP_Emergency_Equip_Reg

Incident & corrective action:
  WHSAPP_Incident_Register,
  WHSAPP_Hazard_Report_Register,
  WHSAPP_Near_Miss_Register,
  WHSAPP_Corrective_Action_Reg,
  WHSAPP_Inspection_Register,
  WHSAPP_Maintenance_Register

Governance & audit:
  WHSAPP_Audit_Register,
  WHSAPP_Consultation_Register,
  WHSAPP_Change_Register,
  WHSAPP_Document_Register

Rules & platform config:
  WHSAPP_Dashboard_Rules,              WHSAPP_Dashboard_Data_Map,
  WHSAPP_Alert_Rules,                  WHSAPP_Dashboard_KPIs,
  WHSAPP_Role_Permissions,             WHSAPP_PDF_Rules,
  WHSAPP_Digital_Signatures,           WHSAPP_Photo_Evidence,
  WHSAPP_Audit_Events,                 WHSAPP_Blockchain_Logic
```

**OPSF layer — Core data (7 tabs):**
```
  OPSF_Index,
  OPSF_Occupation_Master,              OPSF_Industry_Master,
  OPSF_Task_Occupation_Access,         OPSF_Task_Industry_Access,
  OPSF_Laravel_Table_Map,              ← 44-row mapping (see below)
  OPSF_QA_Checklist                    ← 50 checks, all PASS
```

### OPSF_Laravel_Table_Map — Complete 44-Row Mapping

```
LTM-001  README                           → tasks
LTM-002  WHSAPP_Index                     → swms_records
LTM-003  WHSAPP_SWMS_Data                 → swms_activity_risks
LTM-004  WHSAPP_Worker_App_View_Map       → swms_responsible_roles (worker view)
LTM-005  WHSAPP_SWMS_Permit_Map           → worker_app_view_map
LTM-006  WHSAPP_Permit_Type_Register      → swms_permit_map
LTM-007  WHSAPP_Permit_Trigger_Matrix     → permit_trigger_matrix
LTM-008  WHSAPP_Permit_Wording_Library    → permit_wording_library
LTM-009  WHSAPP_Control_Hazard_Link_Map   → control_hazard_link_map
LTM-010  WHSAPP_Critical_Control_Verif    → critical_control_verifications
LTM-011  WHSAPP_PreStart_SWMS_15          → prestart_swms_questions
LTM-012  WHSAPP_PostTask_SWMS_15          → posttask_swms_questions
LTM-013  WHSAPP_Training_SWMS             → swms_training_questions
LTM-014+ [remaining 31 tabs → corresponding Laravel tables, all Active]
```
**Key principle from map:** ID relationships only — no SWMS risk logic is duplicated in the database.
**Import order matters:** Tables must be seeded in the order shown (1 → 44) to satisfy foreign key constraints.

### Pre-start / Post-task Column Schema (16 columns)
```
question_id | task_id | task_name | question_number | question_text |
response_type | na_eligibility | critical_fail | failed_response_action |
score_logic | dashboard_metric | alert_trigger | related_activity_id |
evidence_required | responsible_role | active_status
```
Scoring: Yes = 1 pt | No = 0 pt | N/A = excluded from denominator

### Training Column Schema (15 columns)
```
training_question_id | task_id | task_name | question_number | question_text |
response_type | option_a | option_b | option_c | option_d |
correct_answer | critical_fail | corrective_action_required |
feedback_if_incorrect | learning_message
```

### Worker App View Map — 34-Column Schema
```
worker_view_id | task_id | activity_id | step_number | activity_name |
source_swms_column | source_activity_risk_block |
activity_icon_id | activity_icon_name | activity_icon_category |
activity_icon_reference | activity_icon_alt_text |   ← Column 12: icon for workers
hazards_from_nw | hazard_description | consequences_from_nw |
initial_risk_level | controls_from_nw_dm | residual_risk_level |
residual_risk_reason | checks_verification | verification_method |
stop_work_trigger | critical_control_failure_action |
evidence_required | evidence_prompt |
asset_ids | chemical_ids | permit_trigger_ids | critical_control_ids |
primary_task_performer | [5 additional cols]
```
One row per activity (10 rows per task). Hazards/controls preserved as bullet strings from SWMS columns N-W and D-M.

### Blockchain Hash-Chain (Corrected from v10)
```
HASH-001: Digital signature finalisation
  hash_required: Yes
  previous_hash_required: Yes   ← CHAIN — references prior hash
  anchor: signature event

HASH-002: Post-task closeout approval
  hash_required: Yes
  previous_hash_required: Yes   ← CHAIN — references prior hash
  anchor: closeout event
```
Laravel implementation: `audit_events` table needs `hash` and `previous_hash` columns. On write: compute SHA-256 of record data, store alongside SHA-256 of the previous event's hash.

### Role Permissions Schema (18 columns)
```
role_permission_id | role_name | digital_view_allowed |
swms_acknowledgement_allowed | pre_start_submit_allowed |
post_task_submit_allowed | training_required | training_submit_allowed |
photo_upload_allowed | signature_required | manager_review_allowed |
pdf_export_allowed | print_allowed | dashboard_view_level |
audit_view_allowed | admin_edit_allowed | active_status | notes
```

### SWMS Data Structure — Task Decomposition Model
```
1 Task
  ├── Column B: description / requirements / risk overview
  ├── Column C: 10 activities (numbered list)
  ├── Columns D-M: 10 control layers
  │     (authorisation, equipment, permits, compliance,
  │      communication, competency, monitoring, emergency,
  │      review, verification)
  └── Columns N-W: activity risk assessments (1 per activity)
        Each block: hazards | consequences | initial risk |
                    controls | residual risk | responsible role
```
Single data row per task. Activities are in the same cell as bullet list (NOT split into rows).

**Implication for import engine:** Column C must be parsed as a bullet list into `activity_register` rows. Columns N-W each map to one activity row with hazard/control sub-records.

---

## 3.21 OpsFortress Central Occupation Industry Source Pack v4

- **Source:** `OpsFortress_Central_Occupation_Industry_Source_Pack_v4_schema_locked.xlsx`
- **59 sheets** organized in 3 governance layers
- **Status:** Schema defined, ingestion registers empty — awaiting first production load from SWMS workbooks

### Three-Layer Architecture

```
Layer 1 — RAW (23 sheets, 1,000 rows each):
  Direct copy-paste from SWMS workbooks, NO central metadata added.
  Schema locked to match workbook tabs exactly.
  Key tabs: RAW_All_Occupation_Master, RAW_All_Industry_Master,
  RAW_All_Task_Register, RAW_All_Activity_Register,
  RAW_All_Hazard_Register, RAW_All_Control_Register,
  RAW_All_Asset_Register, RAW_HazChem_Register,
  RAW_All_PPE_Register, RAW_All_Training_Register,
  RAW_All_Competency_Register, RAW_All_Licence_Register,
  + 11 link map / rule sheets

Layer 2 — Governance (6 sheets):
  OPSF_Ingestion_Log       → tracks every source workbook load (currently empty)
  OPSF_Schema_Validation_Log → header/column matching per import
  OPSF_Collation_Log       → RAW→CLEAN promotion decisions
  OPSF_Duplicate_Review    → candidate key duplicate resolution
  OPSF_Merge_Register      → record consolidation decisions
  OPSF_Source_Copy_Map     → 23 mandatory RAW tab pairings

Layer 3 — CLEAN (20 sheets):
  Approved authoritative records with:
  approved_status | approved_by | approved_at | central_notes
  Key: OPSF_Occupation_Master_CLEAN, OPSF_Industry_Master_CLEAN,
  OPSF_Task_Master, OPSF_Activity_Master, OPSF_Hazard_Master,
  OPSF_Control_Master, OPSF_Asset_Master, OPSF_HazChem_Master,
  OPSF_PPE_Master, OPSF_Training_Master, OPSF_Competency_Master,
  OPSF_Licence_Master, OPSF_Hazard_Control_Library,
  + 7 link/profile tabs
```

### Occupation / Industry Separation (Parallel, Independent Streams)

```
OCCUPATION:
  RAW_All_Occupation_Master (26 cols)
    occupation_record_id | task_id | task_name | occupation_group |
    occupation_sub_group | occupation_leaf | occupation_candidate_key |
    swms_view_access | pre_start_access | post_task_access |
    training_access | menu_visibility | ...
  ↓
  RAW_All_Task_Occupation_Access (26 cols)
    task_id → occupation_record_id
    swms_view_access | acknowledgement_required | training_required | supervision_required

INDUSTRY:
  RAW_All_Industry_Master (26 cols)
    industry_record_id | task_id | task_name | industry_group |
    industry_sub_group | industry_leaf | industry_candidate_key | ...
  ↓
  RAW_All_Task_Industry_Access (26 cols)
    task_id → industry_record_id
    swms_applicability | worker_access | management_access
```
No cross-linking between occupations and industries. Both map independently to the same task.

### Candidate Key Pattern (Duplicate Prevention)
All records use pipe-separated hierarchical keys: `"construction|masonry|block_laying"`
Used by Duplicate_Review and Merge_Register to detect and resolve collisions across 1,700 workbooks.

### Critical Control System
- `critical_control_flag` marks controls where failure = stop work
- Per `RAW_Critical_Control_Ver`: failure modes, detection methods, immediate actions, evidence types
- Example: `CC-02: "Mixer guard and electrical protection verified"` — critical, required on every use

### Data Flow for Laravel Import
```
Step 1: Ingest SWMS workbooks → copy exact tabs into RAW sheets
Step 2: Schema_Validation_Log validates headers match locked schema
Step 3: Collation_Log decides RAW → CLEAN promotion
Step 4: Duplicate_Review uses candidate keys to deduplicate
Step 5: Merge_Register consolidates near-duplicate records
Step 6: CLEAN layer = authoritative source for Laravel seeding
Step 7: OPSF_Laravel_Table_Map_C maps CLEAN sheets to Laravel tables
```

### Evidence Types (Standardised Across All Sheets)
`Digital acknowledgement` | `Digital check` | `Checklist` | `Photo` | `Signature` | `Audit event`

### Privacy Access Levels (All CLEAN Tabs)
`Internal` | `Internal WHS` | `Worker task access` | `Restricted` | `Worker and supervisor`

---

## 3.22 Voice Call Series — Key Business Decisions (v12–v14)

### Corrected Worker Task Flow (v12 — authoritative)

The flow order recorded in previous versions was incorrect. Kevin confirmed the definitive sequence:

```
SWMS read (Worker App View — columns N-W)
      ↓
Digital signature  (HASH-001 anchor)
      ↓
Pre-start check  [optional — supervisor-configured]
      ↓
Perform work
      ↓
Post-task check  [optional — supervisor-configured]
      ↓
HASH-002 closeout anchor
      ↓
Training assessment  [periodic — only if training_due = true]
```

Key corrections:
- Signature happens **before** pre-start (not after)
- Pre-start and post-task are **configurable, not mandatory**
- Training is **periodic**, not triggered every task execution
- Induction is a **one-time** flow on first site arrival, separate from this loop

---

### Pre-start & Post-task — Configurable Frequency

Supervisors configure frequency per task, not per worker:

| Setting | Behaviour |
|---|---|
| `daily` | Required once per day; same worker doing same task again today → skip |
| `as_needed` | Required every task execution |
| `off` | Disabled entirely for this task |

**Laravel implications:**
- `tasks` table needs `prestart_frequency` and `posttask_frequency` enum columns (`daily` / `as_needed` / `off`)
- `worker_task_sessions` table tracks whether pre-start was completed today (for `daily` deduplication)
- Frontend checks config before rendering pre-start / post-task screens

---

### Training — Periodic Refresh, Not Every Session

| Parameter | Value |
|---|---|
| Refresh interval | Configured per task (monthly / quarterly / half-yearly / annually) |
| Pass mark | 12/15 (80%) |
| Critical fail | Wrong answer = immediate block regardless of total score |
| Expiry behaviour | Training due date reached → forced retake before next task start |

**Laravel implications:**
- `worker_training_completions` table needs `completed_at` + `expires_at`
- On task start: check `expires_at < now()` → trigger training if true

---

### PDF — Dual Document Layer (Builder → Client, Not Worker)

```
Document A — Full SWMS PDF  (builder → client)
  Source:   WHSAPP_SWMS_Data tab (complete risk assessment matrix)
  Content:  All hazards, controls, responsible roles, evidence requirements
  Purpose:  Compliance document handed to client (owner/project principal)
  Format:   Formal PDF with logo, ABN, signature page
  Trigger:  Manager / supervisor action after task completion

Document B — Worker App View  (worker → screen only)
  Source:   WHSAPP_Worker_App_View_Map tab (columns N-W, 10 cols)
  Content:  Simplified steps + icons + hazard prompts
  Purpose:  On-screen guide worker reads on phone before/during task
  Format:   Mobile UI — never generates a PDF
  Trigger:  Rendered at task start
```

Key implication: PDF generation is a **management-side feature**. Workers never generate or receive PDFs.

---

### Worker → Supervisor → Manager — Three-Tier Scope

```
Manager
  │  Scope: ALL workplaces + ALL workers under their account
  │  Actions: Configure task frequency, generate PDFs, global dashboard
  │
  ├── Workplace A
  │     Supervisor
  │       Scope: Their workplace only
  │       Actions: Configure pre-start/post-task frequency (their site)
  │       │
  │       ├── Worker 1 — sees only their own assigned tasks
  │       └── Worker 2
  │
  └── Workplace B
        Supervisor (different)
```

**Laravel implications:**
- Manager ↔ Workplace: `many-to-many`
- Supervisor ↔ Workplace: `many-to-one`
- All API queries must carry `workplace_id` scope to prevent cross-site data leaks

---

### Content Pipeline & Market Timeline

| Metric | Confirmed value |
|---|---|
| Production rate | ~150 workbooks/day (Kevin + Brodie + Lisa) |
| Total target | ~1,700 workbooks |
| ChatGPT template | Locked — keyword in → all tabs out (fully automated) |
| Content completion ETA | ~2–3 weeks from April 29 (mid-May 2026) |
| Market launch target | July 2026 (paid pilot) |
| Initial marketing budget | $600 |
| First pilot targets | 5–10 businesses from Kevin's existing network |

---

### Payment Model (first confirmed — v13)

| Dimension | Value |
|---|---|
| Payment gateway | **Stripe** (embedded in-app) |
| Billing model | **Per-seat** (bill scales with number of workers added) |
| Onboarding trigger | Successful payment → tenant provisioned automatically |
| Legacy reference | AppSheet version used Zapier for payment → onboarding |

**Laravel implications:**
- Stripe webhook listens for `payment_intent.succeeded`
- Webhook triggers `ProvisionTenantJob`: create tenant → create admin account → send welcome email
- Per-seat: `workers` table row count = billing basis; sync to Stripe monthly (or use Stripe metered billing)
- MVP: manual provisioning acceptable; webhook automation is Phase 2

---

### Worker App View — 10 Columns Verbally Confirmed (v13)

Kevin explained to his son (who works in demolition):
> "We take out 10 columns of data and just show that — that's it."

This confirms WHSAPP_Worker_App_View_Map columns N–W (exactly 10 columns) is the definitive worker-facing content. No architecture change needed — this validates the existing record.

---

### Worker Question Language — Simplified to Plain English (v13)

Kevin used ChatGPT to rewrite all question text to plain worker-friendly language:
- **Before:** Formal technical documentation style
- **After:** Direct conversational ("Did you check the electric lead isn't in the water? Yes / No")
- **Goal:** Workers don't feel interrogated; short answers only; no academic language

Demo mock data can retain current wording as placeholder — real workbook import will replace it naturally.

---

## 3.23 Third Voice Call — Additional Findings (v14)

### PDF Generation Mechanism — How It Actually Works

Kevin demonstrated live how the Google Sheets PDF system works:

```
WHSAPP_SWMS_Data column mapping:
  Column N = Activity 1  → RMP 1 in PDF template
  Column O = Activity 2  → RMP 2
  ...
  Column W = Activity 10 → RMP 10

RMP = Risk Management Plan
Each column = one complete activity risk assessment block.

PDF template (CodSops Template / SWMS Template):
  → Pulls RMP 1–10 sequentially from the data tab
  → Assembles 10 risk blocks into one complete SWMS PDF
  → One template, one data source, unlimited PDF outputs
```

**Two PDF template files:**
- `CodSops Template` (SOP version) — confirmed in Google Drive, handles ~1,200 SOP files
- `SWMS Template` (SWMS version) — Kevin to locate from Luke's files

**Kevin's philosophy:** "Write it once, use it a million times."

**Laravel PDF engine implication:** Query → render model. Fetch the task's 10 activity blocks from DB, inject into template → PDF. Do not store PDF content as data. `WHSAPP_PDF_Rules` defines layout; DomPDF is sufficient for MVP.

---

### Alert Escalation Chain (New Business Rule)

Time-driven escalation confirmed by Kevin:

```
Worker triggers alert (critical fail / training fail / hazard report)
      ↓
Notification → Supervisor (direct line)
      ↓
[Supervisor does NOT acknowledge within timeout period]
      ↓
Auto-escalate → Manager
      ↓
Manager sees: "Supervisor [name] has not acted on this alert"
```

**Laravel implications:**
- `alerts` table: add `escalated_at` and `escalation_level` columns
- Queue job `EscalateUnacknowledgedAlerts` runs on schedule, scans overdue alerts
- Default escalation timeout: TBC with Kevin (suggest 2 hours as default)

---

### Minimum Read Time — UX Rule (Proposed by Damon, Approved by Kevin)

Every SWMS step page in the Worker App View must enforce a minimum dwell time before "Next" is enabled:

- **Duration:** 3–5 seconds (exact value TBC)
- **Purpose:** Prevent workers tapping through without reading; improves genuine compliance
- **Legal value:** Combined with timestamp audit trail, proves worker had the opportunity to read each step

**Frontend implementation:** Button starts disabled; countdown timer on page load; button enables when countdown reaches zero.

---

### Immutable Records — Design Principle (Formally Confirmed)

Kevin explicitly stated: submitted records can **never** be edited or backdated.

```
Correct approach:
  All submission tables: INSERT only — no UPDATE, no DELETE
  Stable UUID primary key on every record
  Corrections: new record with supersedes_id pointing to the original

Wrong approach:
  Allowing workers or supervisors to edit submitted records (tampering risk)
  Allowing backdated timestamps (legal risk)
```

Background: Kevin experienced this risk in AppSheet — records being altered after incidents. Combined with the hash-chain (HASH-001/002), this forms a complete tamper-evident audit trail. Non-negotiable.

---

### System Peak Load Window

Confirmed from Kevin's real-world experience (two sons in construction/demolition):

| Time | Activity | Load |
|---|---|---|
| 6–8 am | All workers: pre-starts, SWMS sign-off, signatures | **Peak** |
| Midday | Occasional SWMS viewing | Low |
| 3–4 pm | Post-task check submissions | Secondary peak |

**Implication:** Infrastructure capacity and DB connection pools sized for morning peak. Heavy queue jobs (PDF generation, alert dispatch) should be deferred away from peak window.

---

### Competitor Intelligence

Kevin mentioned: **Siculture.com**
- ~1 million monthly users
- Kevin described them as primarily paper-based and "clunky"
- Worth researching their feature set and pricing for positioning reference

---

### OpsFortress Multi-App Platform Vision (Re-confirmed)

> "WHS App is just the first skin. OpsFortress is the core database. In the future we put any app on top — asset register app, whatever — they all pull from the same database with different skins."

- WHS App = first skin / first revenue stream
- Asset Register App = next candidate
- All apps share: businesses, workplaces, workers, occupations, industries from OpsFortress

---

## 3.17 AppSheet Form Creation Pipeline (Two PowerPoint Files)

### Source A: `Docs/Prepare Forms for Appsheet.pptx`
- **What it is:** A 7-step internal guide for Kevin's team on how to turn a Google Form into an AppSheet-ready data source
- **Key insight:** The AppSheet plugin auto-inserts branching logic into Google Sheets — no manual branching column setup required

**7-step process:**
```
Step 1: Open Google Forms and install the AppSheet plugin
        → Google Workspace Marketplace → search "AppSheet" → Install

Step 2: Open the Google Form that needs branching

Step 3: Link the Form to Google Sheets
        → Responses tab → Link to Sheets

Step 4: Create a new spreadsheet and name it
        (this becomes the AppSheet data source)

Step 5: Open the AppSheet plugin from the Forms sidebar

Step 6: Click "Prepare" and wait

Step 7: Done — the plugin automatically inserts branching information
        from the Form's question logic into the Sheets columns
        → Sheet is now ready to import into AppSheet
```

**Practical implication for rebuild:** This is how all ~110 AppSheet apps were originally built. The Google Forms branching configuration was the "source of truth" — not the Excel columns. For Laravel, we replicate the branching logic in PHP controllers/validation, not in spreadsheet formulas.

---

### Source B: `Marketing/App Creation Process.pptx`
- **What it is:** Kevin's full end-to-end pipeline for creating a new WHS App — from raw safety document to live AppSheet application
- **Key insight:** The pipeline is a 5-stage assembly line. The PDF → Excel → Forms → AppSheet → Cloud sequence explains why all the legacy data lives in Google Sheets/Drive

**Full 5-stage pipeline:**
```
Stage 1: PDF → Excel database
  → Source safety document (regulation, standard, code of practice)
    is converted into a structured Excel database
  → This becomes the content source (hazards, controls, procedures)

Stage 2: Excel → Microsoft Forms (branching setup)
  → Content is loaded into Microsoft Forms
  → Branching logic is configured (conditional questions)
  → Forms handles the survey-style question flow

Stage 3: Microsoft Forms → AppSheet
  → AppSheet plugin auto-generates a branching Excel file
    from the Forms question logic
  → This Excel becomes the AppSheet data source table

Stage 4: AppSheet display (5 view types available)
  → Home Page    — navigation hub
  → Form         — data capture (the main worker-facing view)
  → Video        — embedded training video
  → Graph        — summary statistics
  → Pie Chart    — compliance breakdown

Stage 5: Cloud storage (Google Drive)
  → Every form submission saved to Google Drive as:
    (a) Excel/Sheets row — live data
    (b) Auto-generated PDF — permanent record
  → Real-time sync — no manual export needed
```

**Key finding for rebuild — what Larry replaces:**
| Legacy (AppSheet pipeline) | Laravel equivalent |
|---|---|
| PDF → Excel database | Seeded DB tables (tasks, hazards, controls) |
| Microsoft Forms branching | Laravel form validation + conditional rendering (Inertia.js) |
| AppSheet plugin auto-generates Excel | N/A — Laravel renders from DB directly |
| AppSheet Form view | React/Inertia form page |
| AppSheet Home Page | Laravel dashboard controller |
| AppSheet Graph / Pie Chart | Recharts / Chart.js component |
| Google Drive Excel row | Eloquent model → PostgreSQL row |
| Auto-generated PDF on Drive | Laravel PDF generation (e.g. DomPDF / Browsershot) |
| Real-time sync | Database writes are immediate — no sync layer needed |

**Architectural implication:** The entire pipeline Kevin built exists because AppSheet required Google Sheets as its data source. Laravel + PostgreSQL eliminates the need for the pipeline entirely. Content (hazards, questions, procedures) goes directly into seeded DB tables. Workers interact with forms rendered by React/Inertia. Submissions go straight to the DB and trigger PDF generation via a queue job.

---

## 4. Key Architectural Insights for the Rebuild

### 4.0 CRITICAL UPDATE — Two Systems, Not One

**OpsFortress (the core platform)**
- Holds all shared data: Businesses, Users, Workplaces, Industry classifications, Occupation classifications
- Single source of truth — does NOT contain WHS workflows
- The scalable, permanent system being built

**WHS App (the application layer)**
- Sits ON TOP of OpsFortress
- Delivers: SWMS/SOP, Inductions, Pre-starts, Post-assessments, Training, Permits to Work, WHS Reporting
- The user-facing workflow layer — does NOT own core data

**Platform: Laravel (PHP)** — confirmed in the Excel README
> "Laravel is the target platform. AppSheet may be treated only as a legacy data source."

**Core system flow:**
```
Worker → Occupation → Task/Requirement → Activity → Compliance
```

**Everything a worker does = an Activity:**
- Sign-in, Inductions, Pre-starts, Permits, Training, Reporting

**Geo-location is a core feature:**
- Workplace is geo-tagged
- System detects when worker arrives → prompts sign-in (Yes/No)
- Loads required tasks automatically based on location + occupation

**UX principle (non-negotiable):**
- Minimise typing at all costs
- Inputs: Next / Yes / No / N/A / Submit / Photo / Digital signature only

---

### 4.1 One Platform, Many Modules — Not Many Apps
The current system has one app per equipment type. The rebuild should be a **single multi-tenant platform** where:
- Equipment/task types = content modules (not separate apps)
- Businesses = tenants with their own branded workspace
- Workers = users within a business tenant

### 4.2 Core Document Types to Support
| Type | Description |
|---|---|
| SWMS | Safe Work Method Statement (risk table per task) |
| SOP | Standard Operating Procedure (step-by-step) |
| Inspection Checklist | Pre/during/post operation yes/no checklist |
| Knowledge Assessment | Multiple-choice quiz linked to SOP/SWMS content |
| Incident Report | Multi-party investigation form |

### 4.3 User Hierarchy (combined from boss's letter + Team Directory)
- **Group Administrator** — first user created after payment; sets up business identity and org structure
- **Division / Sub-division / Group managers** — 3-level org hierarchy for large enterprises
- **Employees** — multiple subtypes: permanent, casual, apprentice, student, volunteer
- **Labour Hire** — separate track with own safety manager role
- **Contractors** — complex taxonomy (asbestos, cleaning, construction, demolition, maintenance, service)
- **Emergency/Medical** — fire brigade, ambulance, first aid, GP, nurse
- **External** — client, guest, inspector, regulator, insurer, solicitor, union rep

This is **enterprise-grade** role complexity. Permissions must be role + hierarchy aware.

### 4.4 Business Identity Flow (boss's starting point)
```
Payment received → Group Admin created → Business Identity established →
Branding applied (logo, ABN, address) → Workers onboarded → Documents assigned
```

### 4.5 Web-First, Field-Ready
- Workers use this on phones in the field
- Must work offline or with poor connectivity (PWA recommended)
- Simple, fast UI — not a complex dashboard

---

### 4.6 Full System Flow (v14 corrected — Kevin voice calls)

```
1. Worker created in OpsFortress, assigned industry + occupations
2. Worker arrives at geo-tagged workplace
3. System detects location → sign-in prompt
   [First visit to this site] → Induction (one-time)
4. System loads tasks relevant to worker's occupations

5. Per task execution (repeating loop):
   a. Read SWMS  (Worker App View — columns N-W, 10 activities)
      [Minimum read time enforced per step: 3–5 sec before Next unlocks]
   b. Digital signature  (HASH-001 anchor)
   c. [If prestart_frequency ≠ 'off' AND not already done today] → Pre-start check
      → Scored; critical_fail answer = immediate stop-work block
   d. Perform actual work
   e. [If posttask_frequency ≠ 'off'] → Post-task check
      → Scored; critical_fail = corrective action triggered
   f. HASH-002 closeout anchor
   g. [If training_due = true (expires_at < now)] → Training assessment
      → 15 questions, ≥80% to pass; critical_fail = immediate block

6. All steps recorded as immutable audit events (INSERT only, no UPDATE/DELETE)
7. Compliance calculated automatically (green / amber / red)
8. Supervisor / Manager dashboard updates in real-time
9. [Manager / Supervisor] → trigger full SWMS PDF → deliver to client
10. [If alert unacknowledged by supervisor within timeout] → auto-escalate to manager
```

**Critical rules (v14):**
- No occupation = no SWMS/SOP access
- No activity = no compliance tracking
- No location = no site workflow
- Occupation mismatch = wrong SWMS shown (high-risk error)
- Signature happens **before** pre-start ⚠️ (corrected from earlier versions)
- Pre-start / post-task are **supervisor-configurable** (`daily` / `as_needed` / `off`)
- Training is **periodic** — triggered by expiry, not every task execution
- PDF is a **management output** sent to clients — workers never generate or receive it
- All submitted records are **immutable** — corrections create new superseding records

---

## 5. What We Don't Know Yet — Open Questions

| Question | Priority | Notes |
|---|---|---|
| ~~What does the "Business Identity" document say in full?~~ | ✅ Resolved | Kevin's "final prompt" for BII onboarding was ready April 28; Yiming building frontend in Cody based on BII document |
| How does the OpsFortress ↔ WHS App API/data contract work? | 🔴 High | Modular monolith (one codebase) confirmed in TARGET_ARCHITECTURE.md — no separate API needed |
| ~~How are Permits to Work structured?~~ | ✅ Resolved | Hot Work Permit: 3-phase flow, 6-level fire danger gates, 6 Laravel tables (see 3.14) |
| How is geo-location implemented technically? | 🔴 High | GPS? Geofencing radius? QR code fallback? Not addressed in any source file |
| How many real clients/businesses are currently active? | 🟡 Medium | Qld Health pitch was 2022 — unclear if they signed |
| ~~What is the current subscription/billing model?~~ | ✅ Resolved | **Stripe + per-seat billing**; payment triggers automatic tenant provisioning; MVP can use manual provisioning (see 3.22) |
| How does multi-occupation concatenation work exactly? | 🟡 Medium | Kevin mentioned it; OPSF_Task_Occupation_Access tab in each workbook handles this |
| What does the induction flow look like (data structure)? | 🟡 Medium | Mentioned in email, not yet found in any workbook tab |
| What happens when a SWMS is outdated/needs review? | 🟡 Medium | WHSAPP_Dashboard_Rules tab in workbook likely contains this — read in Phase 2 |
| ~~How does the WHS reporting module work?~~ | ✅ Resolved | 62 form templates surveyed (see 3.15) — universal header pattern + 5 form categories mapped |
| ~~"Blockchain" — real chain or hash?~~ | ✅ Resolved | MD5/SHA-256 hash; WHSAPP_Blockchain_Logic tab in every workbook. No Web3 needed (see 3.16) |
| ~~Is offline sync a hard requirement?~~ | ✅ Resolved (strategy) | Phase 1 = offline-aware (cached); Phase 2 = offline draft saving; Phase 3 = conflict sync |
| ~~What platform is the rebuild targeting?~~ | ✅ Resolved | **Laravel + PostgreSQL + React/Inertia.js** confirmed |
| ~~What triggers a SWMS vs SOP?~~ | ✅ Resolved | Occupation-based; OPSF_Task_Occupation_Access and OPSF_Task_Industry_Access tabs control access |
| What exactly is in OPSF_Laravel_Table_Map? | 🔴 High | Kevin has built DB column mappings inside each workbook — need to read one to get the exact schema |
| How does the Python Google Sheets → PostgreSQL migration script work? | 🔴 High | Confirmed needed; not yet scoped — required before Week 3 import engine build |
| What workbook should be read first as the import template reference? | 🔴 High | Kevin mentioned "mortar mixing / lay concrete blocks" as the template — already in `others/` folder |
| ~~Multi-tenancy: single DB + tenant_id, or DB-per-tenant?~~ | ✅ Resolved (2026-05-12) | **Single shared PostgreSQL with `tenant_id` row-level scoping.** Government clients needing stricter isolation get a separate full-stack deployment, not a runtime dual-mode system. See `opsfortress-demo/TARGET_ARCHITECTURE.md` §Tenancy Strategy for enforcement plan |

---

## 6. Recommended Next Steps

1. **Read the Business Identity document (INTERNS - Business Identity Information 1.docx)** — boss confirmed this is the primary reference for data fields, structure, relationships and logic
2. **Respond to Kevin's email on the "Hanging a Door" data pack** — he wants feedback on: does the structure work? any gaps? does it reduce workload?
3. **Map the OpsFortress data model** — Business → Workplace → Worker structure is the first build focus
4. **Map occupation → SWMS/SOP relationships** from Google Drive industry/occupation data
5. **Clarify geo-location implementation** — GPS geofencing vs QR code sign-in fallback
6. **Draft a data model diagram** for OpsFortress core tables + WHS App overlay

---

## 7. File Index

| File | Type | Relevance |
|---|---|---|
| `luke@...WHS Apps.pdf` | PDF | SWMS output sample — shows end product format |
| `2023.04.28 Home page.docx` | DOCX | Website copy — business positioning and product categories |
| `4WD_Vehicle_Inspection_Checklist.xlsx` | XLSX | Checklist template — 20-item pre/during/post operation |
| `4WD_Knowledge_Assessment_Quiz_With_Feedback.xlsx` | XLSX | Quiz template — 20 MCQs with feedback per question |
| `4WD_Knowledge_Assessment_Quiz.xlsx` | XLSX | Quiz without feedback (simpler version) |
| `4WD_Knowledge_Assessment_Quiz_GoogleSheets.xlsx` | XLSX | Google Sheets variant of above |
| `Incident Investigation Report.pdf` | PDF | Incident form — multi-section, multi-party |
| `321220_Chakradhar Reddy Garlapati.pdf` | PDF | Intern resume — not relevant to architecture |
| `321383_Zhongda Qu.pdf` | PDF | Intern resume — not relevant to architecture |
| `Resume_Kaushik MALIGELI.docx` | DOCX | Intern resume — not relevant to architecture |
| `Qld Health Final Draft.docx` | DOCX | ✅ Sales proposal to Queensland Health — pricing model, feature list, app creation process |
| `Team Directory Input.xlsx` | XLSX | ✅ Full user role taxonomy + profile field schema — critical for data model |
| `appsheet/data/` | Folder | 600+ app folders — mostly empty, structure already mapped |

---

---

## 8. System Architecture Diagram (Summary)

```
┌─────────────────────────────────────────────────────┐
│                   OpsFortress                        │
│            (Core Platform — Laravel)                 │
│                                                      │
│  businesses → workplaces → workers                   │
│  industries → occupations                            │
│  (Single source of truth)                            │
└─────────────────────┬───────────────────────────────┘
                      │ data feed
┌─────────────────────▼───────────────────────────────┐
│                   WHS App                            │
│          (Application Layer — Laravel)               │
│                                                      │
│  Worker arrives at geo-tagged workplace              │
│       ↓                                              │
│  System detects location → Sign-in prompt            │
│       ↓                                              │
│  Occupation match → Load relevant tasks              │
│       ↓                                              │
│  Task flow per content pack:                         │
│  SWMS ack → Pre-start → Induction → Work             │
│  → Post-task → Training assessment                   │
│       ↓                                              │
│  Activity recorded → Compliance calculated           │
│       ↓                                              │
│  Dashboard updated (Green/Amber/Red)                 │
└─────────────────────────────────────────────────────┘

Content Pack per Task (10-sheet Excel → Laravel import):
tasks | swms_records | sop_records | prestart_questions
posttask_questions | training_questions | occupation_access
dashboard_rules | submissions | corrective_actions
```

*This document will be updated as analysis continues.*
> Status: business and product background. Last full update 2026-05-06.
> Schema details predate the v0.3 reset (2026-05-17). For schema truth
> see the migration files plus the regenerated DBML.
>
> 状态：业务与产品背景资料。最后完整更新：2026-05-06。
> 本文中的数据库细节早于 v0.3 schema reset（2026-05-17）。
> 当前 schema 以 Laravel migrations 和重新生成的 DBML 为准。
