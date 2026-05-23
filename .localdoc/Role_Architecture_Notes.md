# Role Architecture — Issue Notes


---

## Core Finding

The Team Directory mixes two different concepts that need to be kept separate in the database:

- **Permission Role** — what the person can *do* in the system (Worker / Supervisor / Manager / Admin)
- **Person Identity Type** — what kind of person they *are* (Employee / Contractor / Labour Hire / Volunteer etc.)

These are independent dimensions. A "Cleaning Contractor" can be a Worker *or* a Supervisor. A "Full-Time Permanent Employee" can be a Worker *or* a Manager. They need separate fields.

---

## Dimension 1 — Platform Permission Roles

These control what a user can see and do in the app.

| Role | What they can do |
|------|-----------------|
| **Worker** | Complete pre-starts, SWMS steps, post-task checks, training |
| **Supervisor** | All of Worker + assign tasks, review submissions, manage their team |
| **Manager** | All of Supervisor + configure task packs, view reports across teams |
| **Admin** | Full platform access, billing, user management, tenant settings |

One user = one permission role (at least for MVP).

---

## Dimension 2 — Person Identity Types

These describe the employment/engagement relationship. From the Team Directory:

**Employee**
- Apprentice
- Casual
- Full-Time Permanent / Casual
- Part-Time Permanent / Casual
- Student
- Volunteer

**Labour Hire**
- (worker placed by a labour hire agency)

**Contractor**
- Asbestos
- Cleaning
- Construction — Building Services / Works / Trades / Land Housing / Heritage
- Demolisher
- Maintenance
- Property Management
- Service

**Other**
- Fire / Emergency
- Medical
- Supplier

---

## The Contractor Problem

Contractors create a cross-business complexity that doesn't exist for employees:

- An ABC Cleaning contractor may work at an XYZ site
- They need a Worker account that belongs to *their own* business (ABC)
- But they need access to *XYZ's* task packs and workplace
- The current schema only has one `business_id` per user — this breaks for contractors

**MVP options:**
1. Treat contractors as employees of the host business (simplest, but loses their identity)
2. Add a `host_workplace_id` on the assignment record (cleaner, defers full contractor model)

---

## Current DB Gap

The existing migrations are missing:

| Missing | Why it matters |
|---------|---------------|
| `person_type` field on `users` | No way to distinguish Employee / Contractor / Labour Hire |
| `contractor_type` field on `users` | No way to capture Cleaning vs Construction vs Maintenance etc. |
| `host_business_id` on assignments | Can't model a contractor working at a different business's site |
| Contractor-specific onboarding flow | Different induction requirements for contractors vs employees |

---

## Questions for Kevin

### Q1 — Do we need person_type in MVP?

| Option | What it means |
|--------|--------------|
| Yes, add `person_type` now | Team Directory is visible in the app; contractors are identifiable |
| No, defer to Phase 2 | Everyone is treated the same; simpler DB, faster to build |

### Q2 — Contractor cross-business access

| Option | What it means |
|--------|--------------|
| Treat them as host business employees | ABC contractor gets an XYZ account — loses their ABC identity |
| `host_workplace_id` on assignment | ABC contractor keeps their ABC account, assigned to XYZ workplace |
| Full contractor portal (Phase 2) | Proper cross-business model, separate onboarding — significant scope |

---

## Recommended MVP Approach

1. Add `person_type` as a simple enum on the `users` table:  
   `employee | labour_hire | contractor | other`

2. Add `contractor_type` as a nullable string (e.g. `"cleaning"`, `"construction"`) — only filled when `person_type = contractor`

3. For cross-business access: use `host_workplace_id` on the `workplace_user_assignments` table (one extra column, minimal schema change)

4. Full contractor portal (separate business identity, cross-tenant permissions, contractor-specific induction flow) → **Phase 2**

This keeps MVP simple while making the data model honest about who people are.
