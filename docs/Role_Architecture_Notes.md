# Role Architecture — Issue Notes

## Core Finding

The Team Directory mixes two different concepts that need to be kept separate in the database:

- **Permission Role** — what the person can *do* in the system (Worker / Supervisor / Manager / Admin / Platform Admin)
- **Person Identity Type** — what kind of person they *are* (Employee / Contractor / Labour Hire / Volunteer etc.)

These are independent dimensions. A "Cleaning Contractor" can be a Worker *or* a Supervisor. A "Full-Time Permanent Employee" can be a Worker *or* a Manager. They need separate fields.

---

## Dimension 1 — Platform Permission Roles

These control what a user can see and do in the app.

| Role | What they can do |
|------|-----------------|
| **Worker** | Complete pre-starts, SWMS steps, post-task checks, training |
| **Supervisor** | All of Worker + assign tasks, review submissions, manage their team |
| **Manager** | All of Supervisor + configure task/SWMS settings, view reports across teams |
| **Admin** | Full customer-account access, billing, user management, account settings |
| **Platform Admin** | OpsFortress operator-level support and platform administration |

In v0.3, permission role is stored on access rows, not on `users`.

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
- Worker placed by a labour hire agency

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

Contractors create cross-business complexity that does not exist for employees:

- An ABC Cleaning contractor may work at an XYZ site.
- They need a worker account associated with their home business.
- They also need access to the host business's workplace, tasks, and SWMS content.
- A single `business_id` on `users` is not enough.

---

## How v0.3 Implements This

The v0.3 schema resolves the original DB gap:

| Need | v0.3 implementation |
|------|---------------------|
| Person identity type | `users.person_type` |
| Contractor subtype | `users.contractor_type` |
| Permission role | `user_business_access.permission_role` and `user_workplace_access.permission_role` |
| Cross-business contractor relationship | `contractor_relationships` |
| Per-workplace access for host sites | `user_workplace_access` |
| Worker occupation integrity | `user_occupations` linking `users` to `occupations` |

The important distinction remains: person identity lives on `users`; permission lives on access rows.

---

## Resolved

- MVP needs `person_type`; it is implemented on `users`.
- Contractor-specific subtype is implemented as nullable `users.contractor_type`.
- Cross-business contractor access is represented by `contractor_relationships` plus `user_workplace_access`.
- Worker-to-task eligibility can now be resolved through `user_occupations` → `task_occupation_access`.

## Still Open

- Contractor-specific onboarding UX and induction rules are still product workflow work.
- More detailed contractor workplace scope should only be added if Kevin confirms contract terms differ by workplace.
- Richer person identity categories from the Team Directory can remain controlled values until a separate lookup table is justified.
