# Google Drive Markdown Update Guide — 2026-05-17

> Purpose: provide manual update text for Google Drive Markdown files that may not be directly editable from this repository.

## 1. Files to Update Manually

Recommended Google Drive files to update or supplement:

```text
WHS_Architecture_Record.md
WHS架构分析记录.md
Role_Architecture_Notes.md
FILE_INDEX.md
FILE_INDEX1.md
```

Do not delete old sections. Add the following as new dated addendum sections so prior research is preserved.

---

# Addendum A — WHS_Architecture_Record.md

Suggested new section:

```markdown
## 2026-05-17 Addendum — OpsFortress / WHSAPP Platform Split and v0.3 Schema Reset

### Summary

The latest architecture review and meeting notes confirm that the project should be treated as a two-layer platform:

1. **OpsFortress** — the reusable core platform / software engine / database layer.
2. **WHSAPP** — the WHS-specific customer-facing product layer built on top of OpsFortress.

OpsFortress should hold platform-level reusable data such as customer accounts, legal business entities, workplaces, users, occupations, industries, business identifiers, contractors, suppliers, assets, evidence, audit records, and import pipelines.

WHSAPP should provide the WHS-specific workflows: SWMS, SOP, worker mobile view, pre-start, post-task, training, signatures, photo evidence, PDFs, reports, dashboards, and alerts.

### Commercial Implication

Kevin's current business logic is that WHSAPP may operate as a branded app or sellable business, while OpsFortress remains the underlying platform asset and software licence layer. If WHSAPP is sold in the future, the buyer would still need OpsFortress to run the product.

### P0 Reinterpretation

P0 should not be only a static UI or a worker viewing one SWMS. P0 should prove the architecture:

- account -> business entity -> workplace -> user access;
- country-specific business identifiers;
- contractor relationships;
- importer can load approved source workbooks;
- worker can view imported SWMS worker steps;
- worker actions create runtime/evidence/audit records;
- signatures and critical events are hash-chain auditable;
- the schema can expand to more countries, industries, documents, and modules.

### v0.3 Schema Reset

The current `opsfortress-demo` schema is a useful Laravel scaffold, but it is not the authoritative product schema. The v0.3 ERD / Database Spec / Column Mapping / Importer Source Index should supersede old demo tables such as:

- tenants
- businesses
- task_packs
- activities
- submissions

The recommended next step is:

`v0.3 Schema Reset + Importer-first P0`

### Critical Modelling Decisions

1. `customer_accounts` replaces the old tenant concept.
2. `business_entities` represent legal entities.
3. `account_businesses` links a customer account to one or more business entities.
4. `business_identifiers` replaces direct ABN-only modelling.
5. `contractor_relationships` must be first-class P0 data.
6. `user_business_access` is required for business-level visibility.
7. `tasks`, `swms_versions`, and `swms_activity_steps` replace the old `task_packs` concept.
8. `swms_versions.full_swms_content` should preserve the full formal SWMS as JSONB.
9. `swms_activity_steps` should store the worker-facing 10-step mobile view.
10. Runtime/evidence records should be append-only with correction via `supersedes_id`.
11. Audit events should support a SHA-256 hash chain using `previous_hash` and `event_hash`.
12. Importer tracking tables are required early: `import_batches`, `import_source_files`, `import_validation_results`.

### Meeting Notes to Preserve

Recent meeting notes also confirm:

- Kevin sees himself as the data/content provider and the technical team as responsible for structure, importer, frontend/backend, and automation.
- Damon should review source index and column mapping for importer feasibility.
- AppSheet is now treated as prototype/reference, not target architecture.
- Globalisation and jurisdiction-specific terminology/legislation are long-term requirements.
```

---

# Addendum B — WHS架构分析记录.md

Suggested Chinese section:

```markdown
## 2026-05-17 补充记录 — OpsFortress / WHSAPP 双层架构与 v0.3 数据库重置

### 总体结论

最新会议和技术审查确认：本项目不应被理解为一个单一 WHS App，而应被理解为两层架构：

1. **OpsFortress** — 底层核心平台 / software engine / database platform。
2. **WHSAPP** — 面向客户的 WHS 安全合规产品 / branded app / app skin。

OpsFortress 负责保存通用平台数据，例如 customer accounts、legal business entities、workplaces、users、occupations、industries、business identifiers、contractors、suppliers、assets、evidence、audit records、import pipelines 等。

WHSAPP 负责 WHS 相关的具体业务流程，例如 SWMS、SOP、worker mobile view、pre-start、post-task、training、signature、photo evidence、PDF、report、dashboard、alert 等。

### 商业含义

Kevin 的长期设想是：WHSAPP 可以作为一个对外销售/授权/甚至未来被收购的产品，但 OpsFortress 作为底层核心软件平台应被保留下来。即使未来 WHSAPP 被卖掉，买方也需要继续使用 OpsFortress 才能运行系统。

### P0 的重新定义

P0 不应该只是证明“工人可以看到一个 SWMS 页面”。P0 应该证明整个架构可行：

- customer account → business entity → workplace → user access 的关系成立；
- 支持不同国家的 business identifier，不是 ABN-only；
- contractor relationship 是一等实体；
- importer 可以从指定 workbook/tab 导入数据；
- worker 可以看到由数据库驱动的 SWMS worker steps；
- worker action 可以产生 runtime/evidence/audit 记录；
- signature 和关键事件可以进入 hash-chain audit trail；
- 后续可以扩展到更多国家、行业、文档和模块。

### v0.3 Schema Reset

当前 `opsfortress-demo` 代码仓库是有价值的 Laravel 技术脚手架，但其旧数据库结构不再是权威模型。v0.3 ERD / Database Spec / Column Mapping / Importer Source Index 应取代旧表结构，例如：

- tenants
- businesses
- task_packs
- activities
- submissions

推荐下一步：

`v0.3 Schema Reset + Importer-first P0`

### 关键建模决定

1. `customer_accounts` 替代旧 tenant 概念。
2. `business_entities` 表示法律实体，不等同于付费账户。
3. `account_businesses` 连接 customer account 与多个 legal business entities。
4. `business_identifiers` 替代直接 ABN 字段，支持 ABN / ACN / NZBN / EIN 等多国家标识。
5. `contractor_relationships` 是 P0，不能只靠 user.business_id 与 assignment.business_id 不同来推断。
6. `user_business_access` 是 P0，用于控制 group/child/sibling business visibility。
7. `tasks`、`swms_versions`、`swms_activity_steps` 替代旧 `task_packs`。
8. `swms_versions.full_swms_content` 使用 JSONB 保存完整正式 SWMS 内容。
9. `swms_activity_steps` 保存工人端 10-step mobile view。
10. runtime/evidence 记录必须 append-only，修正只能通过 `supersedes_id` 新建记录。
11. `audit_events` 需要 SHA-256 hash-chain，包含 `previous_hash` 与 `event_hash`。
12. importer tracking tables 必须提前建立：`import_batches`、`import_source_files`、`import_validation_results`。

### 最近会议需要补充的点

- Kevin 明确把 OpsFortress 看作底层 engine，而 WHSAPP 是其中一个产品皮肤。
- Kevin 希望未来可以保留 OpsFortress 的长期软件资产价值，即使 WHSAPP 被出售也继续收取 licence fee。
- Damon 需要重点检查 source index 和 column mapping 是否足够 importer 开发。
- AppSheet 是 prototype/reference，不是最终技术架构。
- 全球化、多国家、多语言、jurisdiction-specific legislation/terminology 是长期需求，因此数据库不能写死澳洲 ABN-only 模型。
```

---

# Addendum C — Role_Architecture_Notes.md

Suggested section:

```markdown
## 2026-05-17 Addendum — Role, Identity, Business Access, and Contractor Scope

### Permission Role vs Person Identity

Keep these dimensions separate.

Permission role answers: what can the user do?

Examples:

- worker
- supervisor
- manager
- admin
- platform_admin

Person identity type answers: what kind of person/organisation relationship is this?

Examples:

- employee
- labour_hire
- contractor
- visitor
- regulator
- supplier
- other

Do not model detailed contractor categories, employee categories, or visitor/regulator types as permission roles.

### Access Scope

Access should be controlled by explicit scope tables, not only by role name:

- `user_business_access` controls business-level visibility and rights.
- `user_workplace_access` or `workplace_user_assignments` controls workplace-level visibility.
- `contractor_relationships` controls host business to contractor business relationships.
- `task_occupation_access` and `task_industry_access` control task/SWMS visibility.

### Contractor Relationship

The previous demo used the idea that `assignment.business_id` can represent the host business while `user.business_id` represents the user's home/employer business. This is useful but not enough for v0.3.

v0.3 should model contractor relationships explicitly:

- host business entity;
- contractor business entity;
- relationship status;
- start/end dates;
- approved scope;
- future insurance/licence/compliance metadata.

### Worker / Supervisor / Manager Scope

Recommended visibility model:

- worker: sees only assigned/relevant tasks for their workplace and occupation;
- supervisor: sees workers and tasks for their workplace;
- manager: may see multiple workplaces/businesses depending on `user_business_access`;
- group admin: may see the whole customer account depending on account-level permissions;
- contractor: sees only host-approved workplaces/tasks.
```

---

# Addendum D — FILE_INDEX.md / FILE_INDEX1.md

Suggested section:

```markdown
## 2026-05-17 Documentation Additions

New repo-side documentation files were created in `opsfortress-demo/docs/`:

- `V0_3_SCHEMA_RESET_PLAN.md` — v0.3 schema reset and importer-first plan.
- `TECHNICAL_REVIEW_OPSFORTRESS_DEMO_2026_05_17.md` — professional review of current repo strengths/weaknesses.
- `MEETING_NOTES_2026_05_17_WHSAPP_OPSFORTRESS.md` — cleaned meeting notes from the latest WHSAPP/OpsFortress discussion.
- `CODEX_PROMPT_V0_3_MIGRATIONS.md` — prompt for generating fresh Laravel migrations from v0.3 schema.
- `GOOGLE_DRIVE_MARKDOWN_UPDATE_GUIDE_2026_05_17.md` — manual update snippets for Drive Markdown files.

These files should be treated as addenda to, not replacements for, the existing architecture records.
```

---

## 2. Recommended Manual Process

1. Open each Google Drive Markdown file.
2. Add the relevant section near the end under a new dated heading.
3. Do not delete previous analysis sections.
4. Add a short cross-reference to the repo docs.
5. If the Drive file becomes too long, create a new file named:

```text
WHS_Architecture_Record_Addendum_2026_05_17.md
WHS架构分析记录_补充_2026_05_17.md
```

and link to it from the original file.

---

## 3. Important Preservation Note

Previous Markdown files contain valuable historical research about:

- AppSheet legacy structure;
- 65-tab workbooks;
- PDF generation logic;
- hash-chain / blockchain clarification;
- worker flow sequence;
- role/person identity analysis;
- OHSMS module scan;
- business identity onboarding;
- importer source structure.

Do not overwrite those sections. The 2026-05-17 update should be additive.