# WHS App — Architecture & Discovery Record

> **Purpose:** Running record of system analysis for the WHS App rebuild.
> **Last updated:** 2026-04-28 (v9 — Work Directory Path re-read: OpenAI integration, QLDHealth app, versioned app timeline, full OHSMS count confirmed)
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

**Assessment structure — MANDATORY 12-question fixed sequence:**
```
Row  2-4:  Multiple Choice — Hazard Identification  (3 questions)
Row  5-7:  Multiple Choice — Risk Controls           (3 questions)
Row  8-9:  True/False      — Emergency Procedures   (2 questions)
Row 10-11: Multiple Choice — Plant & Equipment      (2 questions)
Row 12-13: True/False      — PPE                    (2 questions)
```
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

**"Blockchain anchor" for key events** (from v7 data pack) likely means: store a hash of the event data at the time of the event, and log it to an append-only audit table. Nothing more.

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

### 4.6 Full System Flow (from Kevin's email)
```
1. Worker is created in OpsFortress
2. Assigned industry + occupations (can have multiple)
3. Worker arrives at geo-tagged workplace
4. System detects location → prompts sign-in
5. System loads tasks relevant to worker's occupations
6. Worker completes tasks:
   a. SWMS acknowledgement
   b. Pre-start checklist (scored, critical fail = blocked)
   c. Induction (if first time)
   d. Task execution
   e. Post-task report (scored, critical fail = corrective action)
   f. Training assessment (if required, ≥80% to pass)
7. All steps recorded as Activities
8. Compliance calculated automatically
9. Dashboard updated in real-time
```

**Critical rules:**
- No occupation = no SWMS/SOP access
- No activity = no compliance tracking
- No location = no site workflow
- Incorrect occupation matching = wrong SWMS shown (high risk error)

---

## 5. What We Don't Know Yet — Open Questions

| Question | Priority | Notes |
|---|---|---|
| What does the "Business Identity" document say in full? | 🔴 High | Boss says this is the true starting point — not yet received |
| How does the OpsFortress ↔ WHS App API/data contract work? | 🔴 High | Two-system architecture needs clear interface definition |
| ~~How are Permits to Work structured?~~ | ✅ Resolved | Hot Work Permit: 3-phase flow, 6-level fire danger gates, 6 Laravel tables (see 3.14) |
| How is geo-location implemented technically? | 🔴 High | GPS? Geofencing radius? QR code fallback? |
| How many real clients/businesses are currently active? | 🟡 Medium | Qld Health pitch was 2022 — unclear if they signed |
| What is the current subscription/billing model? | 🟡 Medium | 2022 pricing found; current pricing unknown |
| How does multi-occupation concatenation work exactly? | 🟡 Medium | Kevin mentioned it but no data spec provided |
| What does the induction flow look like (data structure)? | 🟡 Medium | Mentioned in email, not in the Excel pack |
| What happens when a SWMS is outdated/needs review? | 🟡 Medium | Dashboard rules mention review_due_date alert |
| ~~How does the WHS reporting module work?~~ | ✅ Resolved | 62 form templates surveyed (see 3.15) — universal header pattern + 5 form categories mapped |
| ~~"Blockchain" — real chain or hash?~~ | ✅ Resolved | MD5/SHA-256 hash stored on creation; compare on read. No Web3 needed (see 3.16) |
| Is offline sync a hard requirement? | 🟡 Medium | Implied by field-worker use; Laravel PWA or native? |
| ~~What platform is the rebuild targeting?~~ | ✅ Resolved | **Laravel (PHP)** confirmed |
| ~~What triggers a SWMS vs SOP?~~ | ✅ Resolved | Occupation-based matching, auto-delivered |

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
