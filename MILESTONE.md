# OpsFortress Demo Milestones

## Status Legend

- `todo`
- `in-progress`
- `done`
- `blocked`

## Milestones

| ID | Milestone | Status | Notes |
|---|---|---|---|
| M1 | Confirm demo scope and architecture | done | Single end-to-end workflow selected |
| M2 | Create project folder and planning docs | done | Initial architecture plan captured |
| M3 | Verify local runtime/toolchain | done | Laravel Herd PHP/Composer/Laravel + Bun |
| M4 | Scaffold Laravel application | done | Laravel 13 + React starter scaffolded |
| M5 | Wire Inertia.js with React + TypeScript | done | Starter kit integrated and building |
| M6 | Configure local database/queue/filesystem runtime | done | PostgreSQL + `database` queue/cache + `local` disk |
| M7 | Build core schema migrations | done | Core + workflow migrations added and migrated |
| M8 | Build WHS preview shell | done | `/preview` and `/preview/{slug}` static prototype pages |
| M8.5 | Phase 0 refactor — domain folders + tenancy enforcement | done | All 9 P0 steps merged 2026-05-12; 50/52 tests passing (2 skipped registration tests are intentional) |
| M9 | Build core domain models and seed sample content | done | All 17 models, idempotent platform + demo-tenant seeders, person_type/contractor_type columns; end-to-end login as `admin@demo.test` verified 2026-05-12 |
| M10 | Build admin onboarding flow | in-progress | Decided 2026-05-12 to ship **one vertical slice at a time** (plan C). First slice = "Add Workplace". See "M10 Delivery Plan" section below |
| M11 | Build worker task flow | todo | Check-in, SWMS acknowledgement, pre-start, submit |
| M12 | Build compliance services and document jobs | todo | Scoring, audit trail, PDF generation |
| M13 | Add PWA/offline groundwork | todo | Deferred |
| M14 | Verify locally and document current behavior | done | App runs via Herd, build/tests pass, docs synced |

## Phase 0 Refactor — Decided 2026-05-12

Before M9 starts, lay the foundations the rest of the build assumes. These items are cheap now and expensive later.

| Step | Task | Status | Why |
|---|---|---|---|
| P0-1 | Create `app/Domain/{OpsFortress, Whs, Shared}/...` namespaces matching `TARGET_ARCHITECTURE.md` | done | Migrations are currently flat; new code must land in domain folders, not `app/Models/` root |
| P0-2 | Split `2026_04_28_212500_create_workflow_tables.php` into per-domain migration files | deferred | Existing migration already ran on dev DB; splitting would force a destructive rollback. Future workflow migrations land in per-domain files; if/when we wipe the dev DB before pilot, also split this one |
| P0-3 | Create Eloquent models for all 17 existing tables, placed in their domain namespaces | done | 16 new models created (User already existed). All wired with relations |
| P0-4 | Add `BelongsToTenant` global scope + `SetTenantContext` middleware | done | `TenantContext` singleton + `TenantScope` + `BelongsToTenant` trait + middleware registered in `bootstrap/app.php` |
| P0-5 | Add `audit_events` table + `Shared\Audit\AuditService` with SHA-256 hash-chain (`hash`, `previous_hash`) | done | Migration `2026_05_12_100000_create_audit_events_table.php`; AuditService uses canonical-JSON + SHA-256 + lockForUpdate |
| P0-6 | Fix nullable-unique constraints (use Postgres partial indexes) on `user_roles`, `user_occupations` | done | Migration `2026_05_12_100100_fix_nullable_unique_assignment_indexes.php` |
| P0-7 | Disable public registration; add admin-invite-only user creation | done (disable) / todo (invite flow) | `Features::registration()` commented out in `config/fortify.php`. The invite flow itself is built in M9-M10 |
| P0-8 | Reconcile `file_uploads.disk` default (`s3`) with `.env` (`FILESYSTEM_DISK=local`) | done | Migration `2026_05_12_100200_fix_file_uploads_disk_default.php` |
| P0-9 | Add tenant-isolation pest/phpunit suite (cross-tenant read + write must fail) | done | `tests/Feature/Tenancy/{TenantContextTest,TenantIsolationTest}.php` and `tests/Feature/Audit/AuditServiceTest.php` |

**Exit criteria:** all items merged, `php artisan test` green locally, no controller code yet — Phase 1 (M9+) starts on a clean foundation.

### Local verification commands (run on Herd machine)

```powershell
$env:PATH="C:\Users\User\.config\herd\bin;C:\Users\User\.bun\bin;" + $env:PATH
Set-Location "C:\Users\User\Desktop\WHSAPP\opsfortress-demo"
composer dump-autoload
php artisan migrate
php artisan test
```

### Still to schedule (post-Phase 0)

- ~~Add `person_type` and `contractor_type` enum columns to `users`~~ — **done** 2026-05-12 (migration `2026_05_12_110000_add_person_type_to_users_table.php`).
- ~~Add `host_workplace_id` to `workplace_user_assignments`~~ — **not needed**: re-analysis shows the existing `assignment.business_id` already represents the host business, distinct from `user.business_id` (the home / employer business). See clarifying docblock on `WorkplaceUserAssignment` model.
- Install `spatie/laravel-permission` — deferred to M10 when authz checks first appear in controllers. Adding it earlier would be empty plumbing.

## Audit Hardening Pass — 2026-05-14

After Slice 1 landed we did an architecture audit and locked down four issues that
would have compounded across future slices:

| ID | Fix | Files |
|---|---|---|
| A1 | `Submission.payload` and `Activity.payload` are now immutable post-creation (model `booted()` observer throws `RuntimeException` on update). Tests in `tests/Feature/AuditHardening/PayloadImmutabilityTest.php`. | `app/Domain/Whs/Submissions/Models/Submission.php`, `app/Domain/Whs/Activities/Models/Activity.php` |
| A2 | `task_packs.code` is no longer globally unique. Replaced with two partial indexes: `(tenant_id, code)` unique for tenant-scoped packs, `code` unique for platform-shared (`tenant_id IS NULL`) packs. | migration `2026_05_14_100000_fix_task_packs_code_uniqueness.php` |
| A3 | `UserPolicy` added with hard tenant boundary on `view`/`update`/`delete` and role-based `viewAny`/`create`. Slice 3 (Invite Worker) will be the first consumer. | `app/Policies/UserPolicy.php`, `app/Providers/AppServiceProvider.php` |
| A4 | `User::hasRole()` and `User::roleCodes()` now explicitly filter `user_roles.tenant_id` to match the user's own `tenant_id`. Defends against corrupted cross-tenant `user_roles` rows. Tests in `tests/Feature/Tenancy/HasRoleTenantBoundaryTest.php`. | `app/Models/User.php` |

## Kevin-driven schema hardening — 2026-05-14 (done)

Kevin's 2026-05-13 reply clarified business identity rules. K1+K2+K3 landed
before Slice 2 so the data model is correct before customer-creatable rows
exist:

| ID | Change | Files |
|---|---|---|
| K1 | `blockchain_id` columns widened from `varchar(16)` to `varchar(26)` (ULID size) on `businesses`, `users`, `activities`, `submissions`. Business model auto-generates ULID on create via `creating` observer. All four models have `updating` observer that throws if `blockchain_id` is changed after being set. | migration `2026_05_14_100100_widen_blockchain_id_and_abn_unique.php`; `Business.php`, `User.php`, `Activity.php`, `Submission.php` |
| K2 | Global partial unique index on `businesses.abn WHERE abn IS NOT NULL`. Null ABN allowed many times (sole traders during onboarding). | same migration |
| K3 | `blockchain_id` added to `$hidden` (Business/Activity/Submission) and to `#[Hidden]` attribute (User). Hidden from `toArray()` / JSON serialisation / Inertia props by default. | model classes above |

Tests: `tests/Feature/AuditHardening/BusinessIdentityHardeningTest.php` (8 cases:
ULID generation, explicit-id preserved, Business + User immutability, ABN
duplicate rejected, null ABN allowed, Business + User serialisation hidden).

Seeder change: `DemoTenantSeeder` now uses `legal_name` as the firstOrCreate
key instead of `blockchain_id`. Fresh seeds get ULIDs via the observer;
re-runs find existing rows via legal_name. The legacy `'acme0001'` value on
the already-seeded demo row stays — backfill not needed in dev.

## Slice 3 deferred items

| ID | Defer | Why |
|---|---|---|
| K4 | Business-level scoping inside a tenant: today `BelongsToTenant` only filters by `tenant_id`, but Kevin clarified that managers scoped to one business should not see another business in the same tenant. Slice 1 (admin-only) doesn't trigger this; Slice 3 (multi-role users) must solve it. | Architectural — needs design discussion before implementation. |

## Slice 2 (Add Business) — pending Kevin's onboarding design

Kevin said (2026-05-13): "I am working through the onboarding flow for a paying
customer in WHS App, and I am hoping to have it finished tomorrow or Friday."

Slice 2 starts after Kevin's design is in. Open questions captured for him:

1. **Status semantics**: "worker active = billable" — is active read from `users.status` or `workplace_user_assignments.active_from`?
2. **ABN duplicate UX**: hard-reject, soft "flag for review" workflow, or "link existing business" option?
3. **Child-business isolation**: strict, or admin can override?
4. **Contractor relationship data model**: stub business under host tenant vs separate tenant + link?
5. **Business `status` value list**: enum values and transition rules?

## M9 Demo Seed — Reference

Run after `php artisan migrate`:

```powershell
php artisan db:seed
```

Seeds (idempotent — safe to re-run):

- **Platform catalog** (no tenant): 4 industries, 4 occupations, 4 baseline roles (admin/manager/supervisor/worker)
- **Demo tenant**: `Demo Tenant` (slug=demo)
  - Business: `Acme Construction Pty Ltd` (blockchain_id=acme0001)
  - Workplace: `Brisbane Site 1` (code=BNE-01, geofenced 100m around -27.4810, 153.0244)
  - Users (all password = `password`):
    - `admin@demo.test` — Admin role, no occupation
    - `supervisor@demo.test` — Supervisor role, Site Supervisor occupation
    - `worker@demo.test` — Worker role, Carpenter occupation
  - All three assigned to Brisbane Site 1
  - One sample TaskPack: `lay-concrete-blocks` v7.0.0 (eligible to Carpenter + General Labourer)

Quick sanity check after seeding (any PG client):

```sql
SELECT id, slug, name FROM tenants;
SELECT id, legal_name, blockchain_id FROM businesses;
SELECT u.email, r.code AS role, o.code AS occupation
FROM users u
LEFT JOIN user_roles ur ON ur.user_id = u.id
LEFT JOIN roles r ON r.id = ur.role_id
LEFT JOIN user_occupations uo ON uo.user_id = u.id
LEFT JOIN occupations o ON o.id = uo.occupation_id
WHERE u.tenant_id = (SELECT id FROM tenants WHERE slug = 'demo');
```

## M10 Delivery Plan — Vertical Slices (Decided 2026-05-12)

Rather than building all admin CRUD endpoints first (plan A) or building the whole admin UI shell with mock data (plan B), M10 ships **one end-to-end vertical slice at a time** (plan C). Each slice exercises the full stack — route, controller, FormRequest, Policy, BelongsToTenant trait, AuditService, Inertia/React page, phpunit test — for a single business action.

### Why slice-first

- Proves the architecture works in real controllers, not just in seeders and tests
- Yiming has a demoable feature at the end of each slice (no "two weeks of invisible backend work")
- Subsequent slices are mostly copy-paste-and-modify from the first

### Slice order (proposed)

| Order | Slice | Why this order |
|---|---|---|
| **1** | **Add Workplace** | Simplest entity (no email invite, no occupation/role wiring). Validates the full stack once. |
| 2 | Add Business | Same shape as workplace, slightly different validation rules. Mostly copy-paste from slice 1. |
| 3 | Invite Worker | Introduces email/notification flow + user provisioning. Builds on slices 1-2. |
| 4 | Assign Worker → Workplace | Wires up `workplace_user_assignments`. |
| 5 | Assign Occupation → Worker | Wires up `user_occupations`. |

### M10 Slice 1 — Add Workplace (active)

**Scope (in):**

- New route group `/admin/workplaces` behind `auth` middleware
- `App\Http\Controllers\Admin\WorkplaceController` with `index`, `create`, `store` actions
- `App\Http\Requests\Admin\StoreWorkplaceRequest` for validation
- `App\Policies\WorkplacePolicy` — only `admin` or `manager` role can create
- Inertia pages: `resources/js/pages/admin/workplaces/{index,create}.tsx`
- Sidebar nav: add "Workplaces" link under Platform (visible to admin/manager only)
- `AuditService::record($workplace, ANCHOR_SIGNATURE, 'workplace.created', [...])` on store
- phpunit tests:
  - Admin from tenant A can create a workplace and see it on the list page
  - Worker (non-admin) cannot reach the create form (403)
  - Admin from tenant A cannot see workplaces from tenant B
  - Creating a workplace writes one audit event with HASH-001 anchor

**Out of scope (explicitly deferred):**

- Editing or deleting workplaces (slice 1.5 if needed; otherwise revisit later)
- Map-picker UI for lat/long (text inputs only for slice 1)
- Geofence radius preview
- Multiple business selector — slice 1 assumes the workplace belongs to the user's `users.business_id`
- `spatie/laravel-permission` — slice 1 uses our existing `user_roles` table via a custom `User::hasRole($code)` helper. Spatie installation moves to slice 2 if the custom helper feels awkward to extend.

**Files to create:**

```
app/Http/Controllers/Admin/WorkplaceController.php
app/Http/Requests/Admin/StoreWorkplaceRequest.php
app/Policies/WorkplacePolicy.php
app/Providers/AuthServiceProvider.php           # policy registration
resources/js/pages/admin/workplaces/index.tsx
resources/js/pages/admin/workplaces/create.tsx
tests/Feature/Admin/WorkplaceManagementTest.php
```

**Files to modify:**

```
routes/web.php                                  # add admin route group
app/Models/User.php                             # hasRole($code) helper
resources/js/layouts/...                        # sidebar "Workplaces" link
```

**Exit criteria:**

1. Logged in as `admin@demo.test`, can navigate to `/admin/workplaces`, see "Brisbane Site 1" listed.
2. Click "Add Workplace", fill form, submit → redirected back to list, new row visible.
3. Logged in as `worker@demo.test`, navigating to `/admin/workplaces` returns 403.
4. New phpunit tests green.
5. `audit_events` table shows one new row per workplace creation, with `previous_hash` correctly chained.

## Current Verified State (2026-05-12)

- `php artisan test` — **50 passed / 2 skipped** (the 2 are intentionally-disabled registration tests)
- `php artisan migrate` — all 10 migrations applied
- `php artisan db:seed` — 1 tenant, 1 business, 1 workplace, 3 users, 1 task pack created idempotently
- End-to-end login confirmed: `admin@demo.test` / `password` → `/dashboard` renders with "Demo Admin" in sidebar
- Herd serves the app at `http://opsfortress-demo.test`

## Current Product State

Implemented (architecture + foundations):

- Multi-tenant domain model (17 tables, 17 models in `app/Domain/...`)
- `BelongsToTenant` trait + `TenantContext` singleton + `SetTenantContext` middleware
- SHA-256 hash-chained audit (`audit_events` + `AuditService`)
- Idempotent platform + demo seeders
- Phase 0 hardening (partial unique indexes, public registration disabled, file-disk default fixed)
- Starter auth/settings flows; demo admin/supervisor/worker can log in

Not implemented yet:

- Any business CRUD controller (M10 slice 1 starts here)
- Worker mobile task flow (M11)
- Submission scoring, corrective actions, PDF generation (M12)
- PWA / offline (M13)
- spatie/laravel-permission (deferred to slice 2 evaluation)
- Python Google Sheets → PG import script (separate track, starts when content packs stabilise)

## Review Findings — Status

| # | Finding | Status |
|---|---|---|
| 1 | Fix PostgreSQL uniqueness behavior for nullable `business_id` assignment rows | done (P0-6) |
| 2 | Decide whether registration should be disabled or tenant-scoped | done (P0-7, disabled — invite flow in M10 slice 3) |
| 3 | Clean encoding artifacts in preview copy before using the prototype in demos | todo (cosmetic; do before any external demo) |
| 4 | Make seed data idempotent if `db:seed` is expected to be rerun | done (M9 — all `firstOrCreate`) |

## Immediate Next Actions

1. Build M10 Slice 1 — Add Workplace (active work, see plan above).
2. After slice 1 lands: review whether `User::hasRole($code)` custom helper held up, or whether to install `spatie/laravel-permission` for slice 2.
3. After M10 slices 1-3 land: M11 worker task flow (workplace sign-in → SWMS acknowledgement → pre-start submission).
