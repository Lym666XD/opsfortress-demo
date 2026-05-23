# Open Decisions for v0.3 Refactor

> Status: Confirmed by Yiming on 2026-05-23.
> Created: 2026-05-23.
> Scope: database semantics that must be confirmed before writing the
> `2026_05_23_*` schema migrations.
>
> The decision rows below are confirmed and unblock the `2026_05_23_*`
> schema migrations.

## Summary

| ID | Decision | Recommended default | Status |
| --- | --- | --- | --- |
| D1 | `workplace_environments` semantics | Global lookup table | Confirmed |
| D2 | Per-user occupation integrity | Add `user_occupations` | Confirmed |
| D3 | `business_entities.blockchain_id` | Move UUID to `business_entities` | Confirmed |
| D4 | SWMS worker-view fields | Promote 9 high-value fields | Confirmed |
| D5 | Contractor workplace scope | Keep relationship business-level for now | Confirmed |
| D6 | `audit_events` subject linkage | Polymorphic plus session FK | Confirmed |
| D7 | `audit_events` delete behaviour | Use `restrictOnDelete` | Confirmed |

## D1. `workplace_environments`

### Question

Should `workplace_environments` be a global lookup table or a per-workplace
child table?

### Options

- Option A: Global lookup table with values such as Construction, Federal,
  Mine, Petroleum, and Other, referenced by `workplaces.environment_id`.
- Option B: Per-workplace child table allowing each workplace to define
  multiple environment zones.

### Impact

- Option A gives a clean jurisdiction classifier and matches the current
  business notes.
- Option B supports multiple zones per workplace, but overloads the meaning
  of `workplace_environments` and makes master data harder to govern.

### Recommended Default

Confirm Option A. If multi-zone modelling is required later, add a separate
`workplace_zones` table instead of overloading `workplace_environments`.

## D2. Per-user occupation integrity

### Question

Should the schema add a `user_occupations` join table?

### Options

- Option A: Add `user_occupations` linking `users` to `occupations`.
- Option B: Do not add the table and leave occupation resolution outside FK
  integrity.

### Impact

- Option A makes `task_occupation_access` reachable from the worker side and
  supports a verified worker-to-task eligibility path.
- Option B leaves "Alex is a welder" as application metadata rather than a
  relational fact.

### Recommended Default

Confirm Option A with an active unique index on `(user_id, occupation_id)`
where `deleted_at IS NULL`.

## D3. `business_entities.blockchain_id`

### Question

Should `blockchain_id` live on `business_entities` instead of `users`?

### Options

- Option A: Add `business_entities.blockchain_id uuid not null unique default
  gen_random_uuid()` and remove the legacy `users.blockchain_id`.
- Option B: Keep `users.blockchain_id` and leave `business_entities` without
  the DBML-required identifier.

### Impact

- Option A aligns the schema with the DBML and the business-entity identity
  model.
- Option B preserves scaffold history but keeps the wrong ownership boundary.

### Recommended Default

Confirm Option A.

## D4. SWMS worker-view fields

### Question

Which extra worker-view workbook fields should be promoted from JSON metadata
to first-class `swms_activity_steps` columns?

### Options

- Option A: Promote the 9 fields most likely to be queried or used in worker
  branching logic.
- Option B: Keep all extra fields in JSON metadata.
- Option C: Promote a different confirmed subset.

### Impact

- Option A supports dashboards, risk filtering, and worker-flow branching
  without JSON-heavy queries.
- Option B keeps the schema smaller but hides important workflow semantics.
- Option C is valid if Kevin confirms a different importer contract.

### Recommended Default

Confirm Option A and promote:

- `initial_risk_level`
- `residual_risk_level`
- `residual_risk_reason`
- `stop_work_trigger`
- `evidence_required`
- `evidence_prompt`
- `quick_view_summary`
- `primary_task_performer`
- `supervisory_verification`

## D5. `contractor_relationships.workplace_id`

### Question

Should contractor relationships include a direct `workplace_id`?

### Options

- Option A: Keep `contractor_relationships` at host-business to
  contractor-business level.
- Option B: Add `workplace_id` directly to `contractor_relationships`.
- Option C: Add a separate `contractor_workplace_scope` table only if
  workplace-specific terms are confirmed.

### Impact

- Option A keeps contract terms at the business relationship level and avoids
  premature per-workplace complexity.
- Option B makes workplace-specific relationships easy but can duplicate
  business-level contract semantics.
- Option C preserves the business-level relationship while allowing later
  workplace overrides.

### Recommended Default

Confirm Option A for now. Revisit Option C only if Kevin confirms contract
terms differ per workplace.

## D6. `audit_events` subject linkage

### Question

Should `audit_events` use polymorphic subject fields only, or also include a
direct FK for the most common subject?

### Options

- Option A: Keep only `subject_type` and `subject_id`.
- Option B: Keep polymorphic fields and add nullable
  `worker_task_session_id` with FK integrity.

### Impact

- Option A is flexible but cannot enforce the main worker-session chain.
- Option B keeps flexibility and adds FK integrity where audit events are most
  frequently attached.

### Recommended Default

Confirm Option B, with a check constraint requiring either
`worker_task_session_id` or both `subject_type` and `subject_id`.

## D7. `audit_events` cascade behaviour

### Question

Should deleting an account cascade-delete audit evidence?

### Options

- Option A: Keep `audit_events.account_id` as `cascadeOnDelete`.
- Option B: Change to `restrictOnDelete` and treat account deletion as
  soft-delete-only for forensic records.

### Impact

- Option A can destroy forensic history when an account is deleted.
- Option B preserves audit history and makes destructive account deletion
  impossible while dependent audit records exist.

### Recommended Default

Confirm Option B. Apply the same non-cascade posture to `signatures` and
`evidence_files`.

## Sign-off

Update this section when decisions are confirmed.

| ID | Confirmed option | Confirmed by | Date | Notes |
| --- | --- | --- | --- | --- |
| D1 | Option A | Yiming | 2026-05-23 | Global lookup table. |
| D2 | Option A | Yiming | 2026-05-23 | Add `user_occupations`. |
| D3 | Option A | Yiming | 2026-05-23 | Move UUID to `business_entities`. |
| D4 | Option A | Yiming | 2026-05-23 | Promote 9 SWMS worker-view fields. |
| D5 | Option A | Yiming | 2026-05-23 | Keep contractor relationship business-level. |
| D6 | Option B | Yiming | 2026-05-23 | Polymorphic subject plus session FK. |
| D7 | Option B | Yiming | 2026-05-23 | Restrict deletion for forensic records. |
