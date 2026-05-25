# OpsFortress 29-Tab Workbook v0.3 — 数据库接受度评审

## 总体判断

29-tab workbook framework 的方向是正确的，并且和当前 OpsFortress / WHSAPP v0.3 数据库方向基本一致。

但是，当前 backend **还不能直接完整导入这个 29-tab workbook**。

数据库可以承接核心内容层，但部分 tab 需要字段转换，部分 tab 只能作为配置/规则使用，还有部分 tab 需要未来新增 schema 或 importer 后才能落地。

## 当前 Importer 限制

当前 Laravel importer 只支持三个 SRC-001 tab：

- `RAW_All_Industry_Master`
- `RAW_All_Occupation_Master`
- `RAW_All_Task_Register`

新的 29-tab workbook 使用的是新的 sheet 名，例如：

- `WHSAPP_SWMS_Data`
- `WHSAPP_Worker_App_View_Map`
- `WHSAPP_PreStart_SWMS_15`
- `OPSF_Task_Industry_Access`
- `OPSF_Task_Occupation_Access`

所以，这个 workbook 需要新的 importer slices 才能被处理。

---

## 逐 Tab 评审

| # | Tab | 当前数据库接受度 | 映射方式 | 缺口 | 建议 |
|---:|---|---|---|---|---|
| 1 | `README` | 可以读取，不入库 | Workbook metadata | 没有 DB target | 保持 control-only。 |
| 2 | `WHSAPP_Index` | 可以读取，不入库 | Canonical sheet map | 当前 importer 还没有使用它 | v0.4 importer 应该通过这个 tab 解析 sheet。 |
| 3 | `OPSF_Laravel_Table_Map` | 可以读取，不入库 | Import mapping spec | 不是生产数据 | 作为 importer contract 使用，不写入 DB。 |
| 4 | `OPSF_Validation_Rules` | 部分可接 | validation results 可进入 `import_validation_results` | 没有 validation-rule definition 表 | 暂时保持 config-only，除非需要动态 DB 规则。 |
| 5 | `OPSF_Import_Order` | 部分可接 | Import sequencing | 没有 import-order definition 表 | 由 importer 代码读取，不直接入库。 |
| 6 | `WHSAPP_SWMS_Data` | 可以接，但需要转换 | `tasks` + `swms_versions` | `task_description` 没有 typed DB column；control sections 大概率进入 JSONB | 适合作为 SWMS/PDF 主内容源。 |
| 7 | `WHSAPP_Worker_App_View_Map` | 可以接，但需要转换 | `swms_activity_steps` | headers 和 DB columns 不完全一致 | 映射到 step title、instruction、hazards、controls、minimum read seconds。 |
| 8 | `WHSAPP_PreStart_SWMS_15` | 可以接，但需要规则转换 | `prestart_questions` | 更丰富的 worker guidance 字段需要 JSON mapping | 如果定义好 scoring/guidance JSON schema，可以导入。 |
| 9 | `WHSAPP_PostTask_SWMS_15` | DB 已准备，代码未覆盖 | `posttask_questions` | 还没有 importer/model 覆盖 | 数据库可以接，但 backend code 需要补。 |
| 10 | `WHSAPP_Training_SWMS` | 部分可接 | `training_questions` | 没有 `external_question_id`；feedback 字段需要 JSONB/metadata | 增加 external id，或明确 metadata mapping。 |
| 11 | `WHSAPP_Activity_Register` | 部分可接 | `swms_activity_steps` | 没有 dedicated activity table | 当前可用于 worker steps；如后续 analytics 需要，再 normalize。 |
| 12 | `WHSAPP_Hazard_Register` | 只能 JSONB 接 | `swms_activity_steps.hazards` 或 `swms_versions.full_swms_content` | 没有 hazard table | 先存 JSON；需要复用/报表时再 normalize。 |
| 13 | `WHSAPP_Control_Register` | 只能 JSONB 接 | `swms_activity_steps.controls` 或 `swms_versions.full_swms_content` | 没有 control table | 先存 JSON；需要复用/报表时再 normalize。 |
| 14 | `WHSAPP_Control_Hazard_Link_Map` | 当前没有直接表 | 未来 hazard/control link layer | 没有 link table | 暂时作为 validation/config。 |
| 15 | `WHSAPP_Critical_Control_Verif` | 只适合作为规则配置 | prestart/posttask/training rules | 没有 dedicated rule table | 保持 configuration；真实 runtime verification 由 App 生成。 |
| 16 | `Industries` | 可以接，但需要转换 | `industries` | workbook 字段名和 DB 不完全一致 | 映射到 `industry_group`、`industry_sub_group`、`industry_leaf`。 |
| 17 | `Position or Role` | 可以接，但需要转换 | `occupations` + access metadata | access 字段不完全属于 `occupations` | occupation master data 和 access mapping 要拆开。 |
| 18 | `OPSF_Task_Industry_Access` | 可以接，但 header 太粗 | `task_industry_access` | DB 需要五个 access columns，不是一个 `access_permission` | 拆成五个 access 字段，或定义默认映射。 |
| 19 | `OPSF_Task_Occupation_Access` | 可以接，但 header 太粗 | `task_occupation_access` | DB 需要五个 access columns，不是一个 `access_permission` | 拆成五个 access 字段，或定义默认映射。 |
| 20 | `Management` | 只适合作为配置/参考 | user/access policy reference | 没有 management-role config table | 暂不导入。 |
| 21 | `WHSAPP_Role_Permissions` | 只适合作为配置 | policy/access reference | 没有 role-permission matrix table | 先用 config/code；不要直接写 `user_*_access` rows。 |
| 22 | `WHSAPP_Task_Training_Link_Map` | 部分可接 | `training_questions.task_id`、metadata | 没有 dedicated task-training link table | 简单 link 可以做；复杂 retraining rules 需要 JSONB 或新表。 |
| 23 | `WHSAPP_Activity_Training_Link` | 当前没有合适表 | 未来 activity/training relationship | 没有 activity table | 在 activity layer 明确前，保持 config-only。 |
| 24 | `WHSAPP_Dashboard_Data_Map` | 只适合作为配置 | Dashboard/API layer | 没有 dashboard config table | 暂不导入 DB。 |
| 25 | `WHSAPP_Dashboard_KPIs` | 只适合作为配置 | Dashboard/API layer | 没有 KPI definition table | 暂不导入 DB。 |
| 26 | `WHSAPP_Alert_Rules` | runtime-rule only | alert rule configuration | DB 有 `alerts`，但那是真实 runtime records | 不要导入成 `alerts`。 |
| 27 | `WHSAPP_Photo_Evidence` | runtime-rule only | evidence prompt/rule configuration | DB 有 `evidence_files`，但那是真实 evidence records | 不要导入成 `evidence_files`。 |
| 28 | `WHSAPP_Digital_Signatures` | runtime-rule only | signature rule configuration | DB 有 `signatures`，但那是真实 signature records | 不要导入成 `signatures`。 |
| 29 | `WHSAPP_Audit_Events` | runtime-rule only | audit rule configuration | DB 有 `audit_events`，但那是真实 hash-chain records | 绝不能从 Excel 导入真实 audit rows。 |

---

## 必须修正的关键 Mapping

### Industries

当前 workbook/spec mapping 不应该指向 `industries.name`。

应该使用：

- `industry_level_1` → `industries.industry_group`
- `industry_level_2` → `industries.industry_sub_group`
- `industry_leaf_value` → `industries.industry_leaf`

### Occupations / Position or Role

当前 workbook/spec mapping 不应该指向 `occupations.name`。

应该使用：

- `occupation_group` 或 role group → `occupations.occupation_group`
- `occupation_family` 或 sub-group → `occupations.occupation_sub_group`
- `role_value` / `occupation_leaf_value` → `occupations.occupation_leaf`

### Task Access Maps

workbook 当前使用单一字段 `access_permission`。

但数据库需要五个独立字段：

- `swms_view_access`
- `pre_start_access`
- `post_task_access`
- `training_access`
- `menu_visibility`

允许值是：

- `full`
- `conditional`
- `supervised`
- `none`

v0.4 workbook 应该二选一：

1. 把 `access_permission` 拆成五个和 DB 对齐的 columns；或
2. 定义一个确定性的 mapping rule，把一个 source value 展开成五个 DB 字段。

更干净的 importer contract 是直接拆成五列。

---

## Runtime Boundary

这个边界应该在 v0.4 contract 里锁死：

Excel 可以定义：

- content
- questions
- access rules
- dashboard rules
- alert rules
- evidence prompts
- signature requirements
- audit-event rule definitions

Excel 不应该导入真实 worker runtime records。

以下记录必须由 WHS App 在 runtime 生成：

- `worker_task_sessions`
- `swms_step_events`
- `prestart_submissions`
- `prestart_responses`
- `posttask_submissions`
- `posttask_responses`
- `training_attempts`
- `training_responses`
- `signatures`
- `evidence_files`
- `alerts`
- `audit_events`

这很重要，因为真实 runtime records 需要真实 timestamps、user identity、device context、evidence files、signatures 和 audit hash-chain integrity。

---

## Kevin 建议增加的 Feedback / Guidance 字段

Kevin 提出的字段是合理的，应该进入 v0.4 importer contract。

### Training

建议字段：

- `feedback_if_incorrect`
- `learning_message`
- `safety_reason`

建议 DB 处理方式：

- 存到 `training_questions.scoring_rules`、`training_questions.metadata`，或未来专门的 structured learning-feedback schema。

### Pre-Start and Post-Task

建议字段：

- `n_a_eligibility`
- `scoring_logic`
- `failed_response_action`
- `corrective_action_trigger`
- `why_it_matters`
- `common_mistake_to_avoid`
- `quick_tip`

建议 DB 处理方式：

- 存到 `prestart_questions.scoring_rules` / `metadata`
- 存到 `posttask_questions.scoring_rules` / `metadata`

这些字段能让 pre-start/post-task 成为互动式风险控制检查，而不是简单 checklist。

---

## v0.4 Contract 建议先确认的决策

在这个 workbook 成为 code-level importer contract 前，需要确认：

1. 最终 Excel sheet names 和 canonical tab names。
2. 每个 direct-import tab 的最终 column names。
3. access maps 是否使用五个 DB-aligned access columns。
4. `training_questions` 是否需要 `external_question_id`。
5. `task_description` 是成为 typed DB column，还是先放在 metadata/full content JSON。
6. hazard/control registers 是继续放 JSONB，还是现在就建 normalized tables。
7. dashboard、alert、evidence、signature、audit rules 应该存在哪里。
8. 精确 FK lookup rules：
   - `task_id`
   - `industry_id`
   - `occupation_id`
   - `swms_version_id`
   - `training_question_id`
9. 精确 upsert rules：
   - duplicate task IDs
   - duplicate question numbers
   - duplicate access map rows
   - version replacement vs new version creation
10. Runtime boundary：Excel 只定义 rules/config；App 生成 runtime records。

---

## 最终结论

当前数据库可以承接 29-tab framework 的 **core content layer**，但还不能直接完整导入整个 workbook。

这个 framework 适合作为 **v0.4 importer contract** 的起点，但必须先锁定：

- field-level Excel-to-DB mapping；
- access-map column structure；
- JSONB vs normalized table decisions；
- config/rule-only tab handling；
- runtime record boundary；
- FK lookup and upsert rules。

一句话总结：

> 29-tab framework 是一个很好的产品和 importer 基础，但在 Laravel importer 实现前，需要先 refinement 成更严格的 v0.4 technical mapping。
