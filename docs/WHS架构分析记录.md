# WHS App — 架构分析记录（中文版）

> **用途：** WHS App 重建项目的系统分析运行记录
> **最后更新：** 2026-05-06（v14 — 第三段语音通话：PDF 生成底层机制首次揭露（RMP 1–10 = N-W列模板拉取）、告警升级链条、SWMS 最小阅读时间 UX 规则、不可篡改记录设计、系统峰值负载窗口、竞品线索 Siculture.com）
> **业务分析师：** Yiming（Claude 协助）

---

## 1. 业务概览

**公司：** Workplace Health and Safety（网址：workplacehealthandsafety.com.au / whsapps.com）

**销售产品：** 行业专属的职业健康与安全（WHS）文档套餐，包括：
- **SWMS** — 安全工作方法声明（适用于高风险施工活动）
- **SOP** — 标准操作规程（设备/任务级别的安全操作说明）
- **管理体系** — 完整的 WHS 管理系统文档

**服务行业：** 农业、建筑、林业、制造业、工程、采矿、公共服务、交通运输、公用事业

**核心价值主张：**
- 预制、合规、易于理解的安全文档
- 免费文档品牌化服务（添加企业 Logo、名称、ABN 税号）
- 价格实惠，开箱即用

**产品愿景（来自 2023 年主页）：**
> "WHS Apps — 简洁与可视化的完美结合。随时随地实现实时职业健康与安全沟通和合规报告。"

---

## 2. 现有 AppSheet 系统 — 实际发现

### 规模
| 指标 | 数量 |
|---|---|
| Google Drive 文件总数 | 5,436 个 |
| 文件夹总数 | 2,212 个 |
| AppSheet 应用数量（约） | 600+ 个 |

### 关键发现 — 并不是 600 个独立应用
600+ 个 AppSheet"应用"实际上是**同一套模板**套用在数百种不同设备类型和任务上的重复复制。Google Drive 中每个应用文件夹里几乎只有一个 `empty.txt` 和一张图标图片，真正的应用逻辑存在于 AppSheet 云端，而非 Google Drive。

### 应用分类（来自 `Appsheet Apps/` 文件夹）
```
SOPS（标准操作规程）应用/
  ├── 机动车辆
  ├── 厂房及设备
  │   └── 混凝土泵（臂架泵、线泵等）
  ├── 工具
  └── 工作场所安全规程

SWMS（安全工作方法声明）应用/
  └── （大部分为空 / 未完成）
```

### 资产登记类别（共 13 类）
1. 通道、高空作业及工作平台
2. 电器及数字资产
3. 电气、机械及压力资产
4. 环境及监测资产
5. 固定厂房、机械及加工资产
6. 危险化学品及危险货物资产
7. 装卸及物料搬运设备
8. 移动工厂及重型设备
9. 个人防护装备（PPE）、安全及应急资产
10. 专业及行业专属资产
11. 工具、设备及贸易资产
12. 车辆及运输资产
13. 工作场所基础设施及设施

---

## 3. 文档分析

### 3.1 WHS Apps PDF（SWMS 输出样本）
- **是什么：** 一份已完成的 SWMS 输出文档，即应用为最终用户*生成*的内容
- **格式：** 结构化列的风险评估表
- **采集字段：**
  - 用户 ID、公司名称、ABN 税号
  - 单元号/街道/郊区/城市/州/国家/邮编
  - 工作场所名称及地址
  - 姓名、邮箱、手机
  - 已选 SWMS（任务类型）
  - 每项危险任务的风险评估表

- **风险评估表结构：**

| 列名 | 说明 |
|---|---|
| 危险任务 | 工人正在执行的操作 |
| 危险描述 | 可能出现的问题 |
| 初始风险等级 | 采取控制措施前（低/中/高/极高） |
| 控制措施 | 降低风险的步骤 |
| 残余风险等级 | 采取控制措施后 |

- **示例：** 屋顶桁架安装 — 识别出 10 项危险，包括高空作业（极高）、结构不稳定（极高）、使用电动工具（高）

---

### 3.2 主页文档（2023.04.28）
- **是什么：** workplacehealthandsafety.com.au 的网站文案
- **关键发现：** 2023 年该应用被描述为"即将上线"，说明开发已持续超过 3 年
- **销售产品类别：** SWMS 套餐、SOP 套餐、管理体系 — 均为行业专属
- **文档品牌化服务：** 添加 Logo、企业名称、地址、ABN — 这是重建中需要保留的核心工作流

---

### 3.3 四驱车辆检查清单（Excel）
- **是什么：** 四驱车辆操作前/操作中的检查清单
- **结构：** 20 个检查项，每项均为是/否合规问题
- **字段：** 条目编号 + 问题文本（回答列为空白，可能在应用内填写）
- **示例问题：**
  - 是否确认有效驾驶执照及所需培训/认证？
  - 是否完成车辆启动前检查（液位、刹车、轮胎、灯光、后视镜）？
  - 是否准备好并穿戴所有必要 PPE？
  - 救援装备是否齐全且状态良好？
  - 是否评估路线的天气、地形及潜在危险？
  - 操作后是否检查车辆损坏、故障或磨损？

- **规律：** 启动前 → 操作中 → 操作后 三阶段结构

---

### 3.4 四驱知识评估测验（Excel）
- **是什么：** 带错误答案反馈的多选题知识测试
- **结构：** 单张工作表包含 20+ 道题（预留 1000 行）
- **每道题字段：**
  1. 题目编号
  2. 题目文本
  3. 正确答案（前缀 A/B/C/D）
  4. 错误选项（分号分隔）
  5. 答错时的反馈

- **涵盖主题：** 四驱系统操作、PPE、地形评估、救援规程、风险等级、启动前检查、危险识别

- **关键发现：** 测验与 SWMS/SOP 内容紧密关联，本质上是对安全文档的理解测试。这是一项值得在重建中保留的强力合规学习功能。

---

### 3.5 昆士兰卫生部销售提案（Qld Health Final Draft.docx）
- **是什么：** Kevin Gowdie（WHS Apps）向 Tracey Wyatt（昆士兰卫生部）提交的正式销售提案
- **时间背景：** Beta 测试邀请截止 2022 年 7 月 1 日 — 提供了清晰的时间锚点
- **文档中的核心产品描述：**
  > "WHS Apps 是一款 SaaS 工具，用于简化职业健康与安全管理并提供信息报告。"

**解决的问题（文档中明确列出）：**
- 无集中的 WHS 合规状态仪表板
- 团队仍使用纸质或电子表格系统
- 没有统一的检查计划安排方式
- 各部门间无法统一查看合规状态

**承诺的功能：**
- 集中管理危险源、事故及检查
- 提交时实时推送通知
- 离线支持 — 数据本地保存，联网后同步
- 数据可视化：视频、图表、饼图
- 按地点、行业、产品、管理者筛选数据
- 每个客户可自定义菜单和选项

**应用创建流程（AppSheet 应用的构建方式）：**
```
PDF 文档 → 转换为 Excel → 上传至 AppSheet → 含分支逻辑的数据库 → 云存储
```
这证实了现有 AppSheet 工作流的本质：将纸质 WHS 表单数字化为 Excel，再导入 AppSheet。重建应彻底消除这一手动转换过程。

**定价模型（2022 年报价 — 供参考）：**

| 方案 | 单价/用户 | 数量（昆士兰卫生部） | 合计 |
|---|---|---|---|
| 月付 | $20/月 | 4,500 人 | $90,000/月 |
| 年付 | $240/年 | 4,500 人 | $864,000/年（含 20% 折扣） |
| 早期用户月付 | $15/月 | 4,500 人 | $67,500/月 |

**附加服务：**
- 入职培训：$250/小时 或 $2,000/天
- 定制开发：按范围报价

**关键发现：** 这是面向大型企业的 SaaS 产品（昆士兰卫生部 = 4,500 名用户）。重建必须支持企业级多租户，并具备完善的订阅/计费模型。

---

### 3.6 事故调查报告（PDF）
- **是什么：** 用于记录工作场所事故的结构化表单
- **主要章节：**
  1. 事故分类及报告信息（日期、时间、报告人）
  2. 工作场所识别（机构名称、ABN、地址、联系人）
  3. 调查人员详细信息（资质、职位）
  4. 事故调查团队（支持多名成员）
  5. WHS 咨询安排
- **关键发现：** 支持多名调查团队成员 — 需要适当的关系型数据模型，而非平面电子表格

---

### 3.7 团队目录输入（Excel）
- **是什么：** 团队目录的模式/模板 — 定义所有用户类型和资料字段
- **结构：** 4,804 行 × 30 列 — 作为用户分类的分支决策树

**用户资料字段：**
| 字段 | 备注 |
|---|---|
| 员工 ID | 唯一标识符 |
| 照片 | 视觉记录 |
| 称谓 | 先生/女士/博士等 |
| 姓 | |
| 名 | |
| 出生日期 | |
| 性别 | |
| 街道地址、郊区、城市、州、邮编 | 完整地址 |
| 手机 | |
| 邮箱 | |
| 行业 | |
| 部门 | 组织层级第 1 级 |
| 子部门 | 组织层级第 2 级 |
| 小组 | 组织层级第 3 级 |
| 用户状态 | 活跃/停用等 |
| 用户角色 | 见下方角色分类 |

**完整用户角色分类（数据模型关键）：**

```
分支
├── 员工
│   ├── 合同制员工
│   └── 正式员工
│       ├── 学徒
│       ├── 临时工
│       ├── 全职正式
│       ├── 全职临时
│       ├── 兼职正式
│       ├── 兼职临时
│       ├── 学生
│       └── 志愿者
├── 劳务派遣
│   ├── 劳务派遣经理
│   ├── 劳务派遣安全经理
│   ├── 学徒
│   └── 员工
├── 承包商
│   ├── 石棉清除人员
│   ├── 清洁 → 清洁承包商
│   ├── 建筑
│   │   ├── 建筑工程
│   │   ├── 建筑服务
│   │   ├── 贸易技工
│   │   ├── 土地/住宅
│   │   └── 遗产保护顾问
│   ├── 拆除 → 拆除承包商
│   ├── 维护承包商
│   ├── 物业管理及维护
│   └── 服务承包商
├── 消防及应急服务
│   ├── 应急服务（危险化学品管理机构）
│   └── 消防队
├── 医疗人员（工作场所）
│   ├── 救护人员
│   ├── 急救员
│   ├── 全科医生
│   └── 护士
└── 其他
    ├── 客户
    ├── 访客
    ├── 检查员/监管机构
    ├── 保险公司
    ├── 律师
    └── 工会代表
```

**关键发现：** 远比"工人 + 管理员"复杂。重建的数据模型需要支持丰富的角色分类，并配合三级组织层级（部门 → 子部门 → 小组）。权限和文档可见性很可能取决于角色和层级。

---

## 3.8 企业身份信息 — AppSheet 原型（LUKE - BII 1.docx）
- **是什么：** 73 张 AppSheet 企业身份应用的截图，展示新企业的完整入职流程
- **关键发现：** 这是 Kevin 要求我们在 OpsFortress/Laravel 中复现的原型

**完整入职界面流程：**

```
企业身份仪表板
        ↓
企业类型
  [自动生成区块链 ID，自动填入用户邮箱]
  → "您是个体经营者、公司还是合伙企业？"
        ↓
  ┌─────────────────────────────────┐
  │ 个体经营者 / 公司分支：          │
  │  → 交易名称、ABN 税号、企业 Logo │
  │  → 营业地址（主要工作场所）       │
  │     街道/单元号、郊区、城市      │
  │  [仅公司] → 公司结构             │
  │     （如：Standard Pty Ltd）     │
  └─────────────────────────────────┘
        ↓
  ┌─────────────────────────────────┐
  │ 合伙企业分支：                   │
  │  → 合伙结构                      │
  │  → 合伙人角色（主动/限制）        │
  │  → 合伙人身份（个人/公司/信托）   │
  │  → 合伙人详情（姓名、ABN、地址）  │
  │  → "是否有其他合伙人？" 是→循环   │
  └─────────────────────────────────┘
        ↓
行业分类
  → 行业组 1 或 2
  → 公司、邮箱、姓名、行业
  → 行业子类别（如：建筑 → 承包商/服务/供应商）
        ↓
集团管理员信息
  → "您是否是应用的集团管理员？" 是/否
  → 如是：称谓、名字、姓氏、邮箱、手机
  → 管理员类型：是/否
        ↓
用户身份管理员确认
  → 名字、姓氏、邮箱、手机、管理员类型
        ↓
职业组选择
  → 文职及行政人员
  → 社区及个人服务人员
  → 劳工
  → 机械操作员和司机
  → 管理人员
     → 首席执行官/总经理/立法人员
     → 农场主及农场经理
     → 酒店/零售/服务经理
     → 专业管理人员
  → 专业人员
        ↓
"是否有其他用户？" 是→循环 / 否→完成
```

**入职过程中采集的关键数据字段：**

| 实体 | 字段 |
|---|---|
| 企业 | 区块链 ID、企业类型、交易名称、ABN、Logo、地址 |
| 合伙关系 | 合伙人角色、合伙人类型（个人/公司/信托）、ABN、地址 |
| 行业 | 行业组、行业子组、子类别 |
| 集团管理员 | 称谓、名字、姓氏、邮箱、手机、管理员类型 |
| 职业 | 组 → 子组 → 子子组 → 末端职业 |
| 其他用户 | 循环录入直至选"否" |

**关键发现 — 区块链 ID：**
系统中每条记录都会自动生成区块链 ID（如 `dad90c29`、`3f9a89c2`）。这是核心系统功能，不可省略。

---

## 3.9 "安装门" 数据包 v1（Excel — 第一次试验）
- **是什么：** Kevin 准备的一份完整的 10 张工作表 Excel 文件，作为所有任务内容生成的标准模板，可直接导入 Laravel
- **目的：** 验证内容生成方式，并定义精确的数据库结构
- **状态：** 已被 v7 混凝土块数据包取代，结构已大幅进化

**10 张工作表及其对应 Laravel 表：**

| 工作表 | Laravel 表 | 用途 |
|---|---|---|
| README | — | 工作簿说明 |
| SWMS_Data | `swms_records` | 完整 SWMS 内容（A–W 列） |
| SOP_Data | `sop_records` | 分步骤 SOP，共 13 步 |
| PreStart_Checklist | `prestart_questions` | 30 道问题，含关键失败标记 |
| PostTask_Report | `posttask_questions` | 12 道任务完成后问题 |
| Training_Assessment | `training_questions` | 12 道多选/是否/判断题 |
| Occupation_Access | `occupation_access` | 22 个职业角色及访问级别映射 |
| Dashboard_Rules | `dashboard_rules` | 评分、预警及状态逻辑 |
| Laravel_Table_Map | — | 工作表与数据库表的映射关系 |
| Scoring_Logic | — | 通过/不通过计算规则 |

**已确认的 Laravel 数据库表：**
```
tasks               → 任务基本信息（task_id、标题、行业）
swms_records        → SWMS 内容（外键：task_id）
sop_records         → SOP 步骤及章节（外键：task_id）
prestart_questions  → 启动前检查项目（外键：task_id）
posttask_questions  → 任务后报告项目（外键：task_id）
training_questions  → 评估测题（外键：task_id）
occupation_access   → 访问权限控制（外键：task_id）
dashboard_rules     → 评分及预警规则（外键：task_id）
submissions         → 工人运行时提交的答案（外键：task_id、user_id、business_id、workplace_id）
corrective_actions  → 不合格时生成的整改记录（外键：submission_id、task_id）
```

**评分逻辑（已确认）：**

| 阶段 | 规则 |
|---|---|
| 启动前 — 通过 | 得分 ≥ 90% 且无关键失败项 |
| 启动前 — 有条件通过 | 得分 80–89% 且无关键失败项 |
| 启动前 — 被阻止 | 任何关键失败 = 禁止开始任务 |
| 任务后 — 失败完成 | 任何关键任务后"否" = 需整改 |
| 培训 — 通过 | 得分 ≥ 80% 且无关键安全题答错 |
| 培训 — 需重新培训 | 不通过 或 评估已过期 |
| 仪表板绿色 | 得分 ≥ 90% |
| 仪表板黄色 | 得分 80–89% |
| 仪表板红色 | 得分 < 80% 或关键失败 |

**职业访问级别（4 层）：**
- `完全访问` — 主要工种（木工、细木工等）
- `监督访问` — 仅限学徒
- `辅助访问` — 技术助理、劳工（仅辅助）
- `管理访问` — 领班、现场主管、项目经理
- `有条件访问` — 橱柜制造商、维护工人
- `培训访问` — 培训师和评估师
- `审查访问` — WHS 官员、安全顾问

**关键发现：** 这份 Excel 文件是整个系统的蓝图。每项任务（SWMS/SOP）都遵循这个完全相同的 10 表结构。Kevin 的计划是为每项任务生成此数据包，然后批量导入 Laravel。团队的工作是验证这一方案并找出缺口。

---

## 3.10 "砌混凝土块" 数据包 v7（Excel — 当前标准）
- **是什么：** 最新进化版数据包，20 张工作表（⚠️ v11 注：生产版实际为 65 个 Tab — 见 3.20），明确拆分为 WHSAPP 层和 OPSF 层
- **任务：** 砌混凝土块（砌砖/砌块行业）

**20 张工作表 — 双层结构：**

| 层级 | 工作表 | 用途 |
|---|---|---|
| WHSAPP | `WHSAPP_SWMS_Data` | SWMS A–W 内容（单行） |
| WHSAPP | `WHSAPP_PreStart_SWMS_15` | **精确 15 道**启动前问题 |
| WHSAPP | `WHSAPP_PostTask_SWMS_15` | **精确 15 道**任务后问题 |
| WHSAPP | `WHSAPP_Training_SWMS` | **15 道**培训问题（通过分 12/15 = 80%）|
| WHSAPP | `WHSAPP_Dashboard_Rules` | 评分和预警规则 |
| WHSAPP | `WHSAPP_Role_Permissions` | 基于角色的权限 |
| WHSAPP | `WHSAPP_PDF_Rules` | PDF 导出控制 |
| WHSAPP | `WHSAPP_Digital_Signatures` | 数字签名要求 |
| WHSAPP | `WHSAPP_Photo_Evidence` | 照片证据规则 |
| WHSAPP | `WHSAPP_Audit_Events` | 带时间戳的审计记录 |
| WHSAPP | `WHSAPP_Blockchain_Logic` | 哈希链和区块链锚定逻辑 |
| OPSF | `OPSF_Occupation_Master` | 4 级职业分类 |
| OPSF | `OPSF_Industry_Master` | 4 级行业分类 |
| OPSF | `OPSF_Task_Occupation_Access` | 任务 → 职业关联 |
| OPSF | `OPSF_Task_Industry_Access` | 任务 → 行业关联 |
| OPSF | `OPSF_Laravel_Table_Map` | 数据库表映射 |
| OPSF | `OPSF_QA_Checklist` | 质量检查（全部通过） |

**角色权限（已正式化）：**

| 角色 | 查看 | 提交 | 管理审查 | PDF 导出 |
|---|---|---|---|---|
| 工人 | ✅ | ✅ | ❌ | ❌ |
| 学徒/实习生 | ✅ | ✅（监督下） | ❌ | ❌ |
| 主管 | ✅ | ✅ | ✅ | 有条件 |
| 经理 | ✅ | ❌ | ✅ | ✅ |
| WHS 官员 | ✅ | ❌ | ✅ | ✅ |
| 管理员 | ✅ | ✅ | ✅ | ✅ |

**工人不能导出 PDF** — 仅限管理层。这是一项刻意的合规控制。

**区块链锚定事件（已确认为核心功能）：**

| 事件 | 数据库记录 | 哈希链 | 区块链锚定 |
|---|---|---|---|
| 页面查看 | ✅ | ✅ | ❌（高频，仅内部存储） |
| 提交启动前检查 | ✅ | ✅ | ✅ |
| 确认 SWMS | ✅ | ✅ | ✅ |
| 完成培训 | ✅ | ✅ | ✅ |
| 提交任务后报告 | ✅ | ✅ | ✅ |
| 导出 PDF | ✅ | ✅ | ✅ |

**数字签名：** 工人必须对 SWMS 确认进行数字签名（必填）

**照片证据：** 有条件必填 — 启动前危险源和任务后缺陷

**OpsFortress 中央数据源包（全局共享，不含在任务包中）：**
- `jurisdiction_profiles` — 司法管辖区/监管机构/法律数据
- `regulator_contacts`
- `terminology_profiles`

**QA 检查清单 — 每个数据包导入前必须全部通过：**
- 精确 15 道启动前问题 ✅
- 精确 15 道任务后问题 ✅
- 工人不能导出 PDF ✅
- 区块链逻辑完整 ✅
- 无重复主数据表 ✅

---

## 3.11 工作目录路径（Excel — 完整系统范围图）
- **是什么：** 系统中所有 AppSheet 应用和数据表的完整层级目录 — 重建范围的权威地图
- **关键发现：** 系统规模远超 SWMS/SOP 试验包所呈现的。共约 110 个独立 AppSheet 应用，分布在 5 大模块中。

**完整模块层级：**

```
WHS Apps Data  [OpsFortress 层]
├── 核心应用文件
│   └── AppSheet 应用：WHSAPPSLauncher、WHSAppsSetPermissions、
│       WHSAPPSSignUp、WHSAppsStart、WHSAppsUserPermissions
├── 公司信息
│   ├── AppSheet 应用（9个）：BusinessIdentity、CompanyIdentity、
│   │   CompanyInformation、PartnershipIdentity、PrivateInformation、
│   │   Public、TradieIdentity、WorkerIdentity、WorkplaceLocations
│   └── 数据表（12个）：企业身份、公司身份、公司信息、合伙企业身份、
│       私人信息、公开信息、权限设置、个体经营者身份、用户身份、
│       用户权限、工人身份、工作场所地址
├── 行业组
│   ├── AppSheet 应用（6个）：IndustryClassification、IndustryGroup2–5、
│   │   IndustryGroups
│   └── 数据表（2个）：行业分类、行业组
└── 职业组
    └── AppSheet 应用（1个）：WHSAPPSOccupation

WHS Apps Registers（登记册）
└── 资产登记册
    ├── AppSheet 应用（2个）：WHSAppsAgitators、WHSAppsAssetRegister
    └── 数据：搅拌机、资产登记册

WHS Apps SOPS（标准操作规程）
├── 通用（5个应用）：SOPSPostAssessment、SOPSPostInspection、
│   SOPSPreStartInspection、SOPSTrainingAssessment、WHSAppsChecklists
├── 机动车辆（2个）：DocumentsMotorVehicles、SOPSMotorVehicles
├── 厂房及设备（9个）：ConcretePumps、Cranes、EarthmovingEquipment、
│   HoistsLiftingGear、HeightAccessEquipment、IndustrialTrucks、
│   LandscapingEquipment、PilingDrillRigs、PlantandEquipment（主）
├── 工具（7个）：ConstructionTools、CuttingTools、EPT-REBATTOOLS、
│   IndustrialToolsandEquipment、Lasers/ToolSafety/Welding、SOPSTools
└── 工作场所安全（2个）：DocumentsWorkplaceSafety、
    SOPSWorkplaceProcedures

WHS Apps SWMS（安全工作方法声明）
├── 核心（2个）：WHSAppsDigitalSWMS、WHSSWMS
├── 建筑施工行业（4个）：文档、任务后、启动前、培训
├── 精装行业（5个）：文档、任务后、启动前、SWMS、培训
├── 安装行业（4个）：文档、任务后、启动前、培训
├── 非建筑（4个）：文档、任务后、启动前、培训
├── 前期工程（5个）：文档、任务后、启动前×2、培训
└── 高空作业（4个）：文档、任务后、启动前、培训

OHSMS / WHS 报告模块  [约40+个应用]
├── 协商：同意协商程序、协商记录、安全委员会议程/纪要、
│   WHS 协商、工具箱会议
├── 能力评估：承包商能力评估、员工能力评估
├── 演练：应急疏散演练、消防疏散演练、
│   工作场所应急计划、工作场所消防疏散计划
├── 检查：起重机检查、急救设备/室检查、机动车辆检查、
│   移动设备/操作前检查、便携式设备检查、静态设备检查、
│   脚手架检查、工作场所检查、物料产品、通用检查
├── 通知：改善通知、禁止通知、WHS 合规通知
├── OHS 差距分析 + OHSMS 系统审计
├── 许可证：热工作业许可证
├── 登记册：危险化学品登记册、PPE 登记册
├── 康复：工伤康复及重返工作岗位
├── 报告：危险源报告、危险源与事故管理、事故报告、急救报告
├── 风险评估：化学品风险评估、风险评估
├── SWMS 模板：在线 SWMS
├── 培训：WHS 培训
└── 访客：访客交付登记、访客登记、访客日志、工作场所管理
```

**完整重建范围 — 模块数量：**

| 模块 | AppSheet 应用数 | 备注 |
|---|---|---|
| 核心数据 / OpsFortress | ~15 | 用户、企业、工作场所、行业、职业 |
| 登记册 | 2 | 资产登记册 |
| SOPS | ~25 | 4 类设备/场所 |
| SWMS | ~28 | 6 类施工行业 |
| OHSMS / 报告 | ~40 | 12+ 子类 — 最大模块 |
| **合计** | **~110** | **1 个统一 Laravel 平台** |

**关键发现：** OHSMS 报告模块是系统中规模最大的模块，在试验数据包中几乎完全缺席。它包含安全委员会、工具箱会议、作业许可证、工伤康复、系统审计和 5 种不同类型的检查 — 这些都需要在 Laravel 中建立独立的数据结构。

**已知遗留问题：** 目录中有一条记录被标注为 `FIX - Height`（紧邻高空设备应用条目）— 遗留系统中一个已知未完成的应用。

**v9 新增发现（完整精读后）：**

| 发现 | AppSheet ID | 对重建的意义 |
|---|---|---|
| **OpenAI 集成** | `WHSAppsOpenAi-4238531` | Kevin 已在 AppSheet 中接入 OpenAI — AI 内容生成是已上线功能，不只是规划 |
| **昆士兰卫生部专属 App** | `QLDHealthApp-4238531` | 存在客户定制 App — 多租户白标功能是真实需求，不是假设 |
| **Alpha 测试 App** | `WHSAPPSAlphaTesting-4238531` | 有专属 Alpha 环境 — 说明需要开发/测试/生产三套环境 |
| **视频版公司信息 App** | `CompanyInformatiomVideoApp-4238531` | 通过视频传递公司信息是独立功能（注意：名称有拼写错误"Informatiom"） |
| **设施资产模块** | `FacilityAssets-4238531` | 与 `WHSAppsAssetRegister` 不同 — 设施级资产追踪是独立概念 |
| **带日期的建筑施工 SOPS** | `20250106-SOPS-FINAL-CONSTRUCTION-4238531` | 建筑施工 SOPS 于 2025 年 1 月 6 日定稿 — 近期内容，可直接用于数据库种子 |
| **备份 App** | `WHSAppsBackup-4238531` | Kevin 手动备份 AppSheet 配置 — 确认无 CI/CD 流程 |

**版本化 AppSheet ID 证实了活跃的开发时间线：**
```
SWMSFinishingTrades-4238531-25-05-16         → 2025 年 5 月 16 日
SOPSPostInspection-4238531-25-07-01          → 2025 年 7 月 1 日
SOPSPreStartInspection-4238531-25-08-13      → 2025 年 8 月 13 日
SOPSTrainingAssessment-4238531-25-08-13      → 2025 年 8 月 13 日
PreStartPreliminaryWorks-4238531-25-10-30    → 2025 年 10 月 30 日
```
Kevin 直到 2025 年 10 月仍在持续更新和发布 AppSheet 版本 — 遗留系统在重建决策前一直处于活跃使用状态。部分带日期版本与原版并存（非替换），说明存在 A/B 测试或分阶段发布。

**OHSMS AppSheet 应用完整确认列表（共 50 个）：**
```
协商层：
  AgreedConsultationProcedures（同意协商程序）、RecordofConsultation（协商记录）
  SafetyCommitteeAgenda（安全委员会议程）、SafetyCommitteeMinutes（安全委员会纪要）
  WHSConsultation（WHS 协商）、WHSAppsToolboxMeeting（工具箱会议）

能力评估：
  CapabilityAssessmentContract（承包商能力评估）
  CapabilityAssessmentEmployee（员工能力评估）

风险 / 化学品：
  ChemicalRiskAssessment（化学品风险评估）、WHSAppsRiskAssessment（风险评估）
  WHSAppsHazardousChemicalRegister（危险化学品登记册）

事故 / 危险源：
  HazardandIncidentManagement（危险源与事故管理）、WHSAppsHazardReporting（危险源报告）
  WHSAppsIncidentReporting（事故报告）、WHSAppsFirstAidReporting（急救报告）

检查 — 通用：
  Inspections（通用检查）、WHSAppsWorkplaceInspection（工作场所检查）
  WHSAppsScaffoldInspection（脚手架检查）、WHSAppsMaterialsProducts（物料产品）

检查 — 设备：
  WHSAppsMobilePlant（移动设备）、WHSAppsMobilePlantPreOp（移动设备操作前检查）
  WHSAppsPortablePlant（便携式设备检查）、WHSAppsStaticPlant（静态设备检查）
  WHSAppsCraneInspections（起重机检查）

检查 — 车辆：
  WHSAppsMotorVehicle、WHSAppsMotorVehicles  ← 两个机动车辆 App（疑似重复或版本差异）

急救：
  WHSAppsFirstAidEquipment（急救设备检查）、WHSAppsFirstAidInspections（急救检查）
  WHSAppsFirstAidRoom（急救室检查）

通知：
  WHSAppsImprovementNotice（改善通知）、WHSAppsProhibitionNotice（禁止通知）
  WHSCompliance（WHS 合规通知）

演练 / 应急：
  EmergencyEvacuationDrill（应急疏散演练）、WHSAppsFireEvacuationDrill（消防疏散演练）
  WorkplaceEmergencyPlan（工作场所应急计划）
  WorkplaceFireEvacuationPlan（工作场所消防疏散计划）

登记册：
  Registers（通用登记册）、WHSAppsPPERegister（PPE 登记册）
  （+ RegistersAugust2024 — 2024 年 8 月遗留版本数据）

许可证：
  Hot Work Permits（热工作业许可证）

报告 / 审计：
  OHSGapAnalysisReport（OHS 差距分析报告）、WHSAppsOHSMSSystemsAudit（OHSMS 系统审计）
  WHSAppsOnlineSWMS（在线 SWMS）、WHSAppsRehabilitation（工伤康复）

访客：
  VisitorDeliveryRegistration（访客交付登记）、WHSAppsVisitorRegistration（访客登记）
  WHSAppsVisitorsLog（访客日志）、WorkplaceManagement（工作场所管理）
```

**机动车辆重复标记：** `WHSAppsMotorVehicle-4238531` 与 `WHSAppsMotorVehicles-4238531` 同时存在于 OHSMS 模块下。可能是版本遗留，也可能是两种不同表单（如：检查 vs 登记）。在建立 `motor_vehicle_inspections` 表之前需向 Kevin 确认。

---

## 3.12 INTERNS BII — Yiming 版本（合伙企业路径完整确认）
- **是什么：** 65 张与 3.8 节相同的企业身份入职截图，但包含所有合伙企业子路径（含信托结构）的完整操作演示
- **可见的区块链 ID（多次测试运行）：** `dad90c29`、`3f9a89c2`、`65e5552c`、`df4ebbae`、`caa5f195`、`71475a7d`、`0b4d0e31`、`adae964c`、`6dcd7abd`

**合伙企业 → 合伙人身份完整分支（对 3.8 节的补充）：**

```
合伙人详情
  合伙人角色？
  ├── 主动合伙人 / 管理合伙人（Active or managing partner）
  └── 有限合伙人（Limited partner）

合伙人身份（"合伙人不一定是自然人，公司或信托也是'法人'"）
  您是个人、公司还是信托？
  ├── 个人（Individual）
  │   区块链 ID、用户邮箱、称谓、名字、姓氏
  │   个人信息：性别、出生日期
  │   居住地址（街道/单元号、街道、郊区、城市、州/领地、邮编）
  │   联系方式（邮箱、手机）
  ├── 公司（Company）
  │   区块链 ID、用户邮箱、公司名称、ABN
  │   公司结构（如：Standard Pty Ltd Company）
  │   州/领地、联系人（称谓、名字、姓氏）
  │   联系方式（邮箱、手机）、邮编
  └── 信托（Trust）
      区块链 ID、用户邮箱
      信托结构（如：Fixed trust / Unit trust）
      信托名称、信托 ABN
      受托人类型：公司 或 个人
      └── [子字段同公司/个人路径]
```

**用户身份确认（2种变体已确认）：**

| 界面 | 管理员类型字段值 |
|---|---|
| 用户身份 — 管理员 | **是**（绿色切换按钮） |
| 用户身份 — 非管理员 | **否**（绿色切换按钮） |

字段：名字、姓氏、邮箱、手机、管理员类型（是/否）

**行业 → 建筑行业子路径（字段详情确认）：**
```
建筑（Construction）→ "承包商、服务及供应商"
  → [承包商] | [服务] | [供应商]（按钮选择器）
    → 承包商子类下拉（如：砌砖/砌块 Bricklaying）
    → 客户信息字段：公司名称、邮箱、名字、姓氏、行业
```

**与 Luke 版 BII 的关键区别：** Luke 的文档展示了整体流程；Yiming 的版本展示了所有分支中每个独立字段的顺序 — 这是 OpsFortress 数据建模的字段级权威规范。

---

## 3.13 Kevin 的 AI 内容生成提示词（Prompts/ 文件夹 — 9 个 Excel 文件）
- **是什么：** Kevin 用于 AI 自动生成数据包内容（启动前清单、任务后检查表、培训评估题）的提示词模板，输入来源为安全文档
- **关键发现：** 数据包中所有问题内容均由 AI 从源 PDF/Excel 安全文档生成，而非手写

**提示词系统概览：**

| 文件 | 适用范围 | 输出内容 |
|---|---|---|
| SOPS - PRE-START | 85 个车辆 SOP（四驱、卡车、拖车等） | 启动前是/否清单（单行 Excel） |
| SOPS - POST-START | 同上 85 个 SOP | 任务后完成清单 |
| SOPS - ASSESS | 同上 85 个 SOP | 12 道培训评估题 |
| SWMS - PRE-START | 40 个建筑/木工 SWMS | 启动前是/否清单 |
| SWMS - POST-TASK | 同上 40 个 SWMS | 任务后完成清单 |
| SWMS - ASSESS | 同上 40 个 SWMS | 12 道培训评估题 |
| PRE START PROMPT | 通用主提示词 | 启动前清单模板 |
| POST TASK PROMPT | 通用主提示词 | 任务后清单模板 |
| ASSESS PROMPT | 通用主提示词 | 评估题模板 |

**启动前/任务后清单输出格式（已确认）：**
```
列：时间戳 | 区块链 ID | 用户邮箱 | 企业名称 |
    [按 WHS 控制层级分组的是/否问题] |
    照片_1 … 照片_6 | 数字签名
```
问题分组方式：工作区准备 → 材料与设备 → 手工操作 → 化学/粉尘暴露 → 安全操作规程 → PPE → （任务特定类别）

**培训评估 — CORRECTED：15 题固定结构（非 12 题）：**
```
每套培训共 15 道题，通过分 = 12/15（80%）
题型混合：是/否题、判断题、多选题（4 个选项 A/B/C/D）
每题包含：correct_answer、critical_fail 标志、
  corrective_action_required、feedback_if_incorrect、learning_message
```
> ⚠️ **v11 更正：** 早期记录误写为 12 道题。生产版工作簿 QA-028 确认为 15 道题。"12"是通过分（12/15 = 80%），不是题数。

**每题列格式：**
- A = 主题（从文档标题提取）
- B = 子主题（按行号自动分配）
- C = 题型（多选题 / 判断题）
- D = 题目文本
- E–H = 选项 A/B/C/D（判断题只用 A）
- I = 正确答案（"选项 A/B/C/D"）
- J = 解释/理由

**4 种答案变体模式**（A/C/B/D 轮换），用于在题库中随机化正确答案位置。

**关键架构含义：** Laravel 导入系统需要接受这个精确的列格式。15 道题的固定结构和启动前单行格式是 Kevin 已标准化的输出规范，必须严格遵守。

---

## 3.14 热工作业许可证 — 分支流程图（Adam/Hot Work Permit BRANCHING.xlsx）
- **是什么：** 热工作业许可证的完整分支逻辑 — 迄今为止找到的唯一一份作业许可证数据结构
- **关键发现：** 作业许可证有基于环境条件的条件授权门控，不是简单的表单

**三阶段工作流：**
```
阶段 1：热工作业开始前
  → 检查作业区域 + 确定安全预防措施
  → 根据危害等级确定火警监视时长
  → 检查是否需要中断消防系统 → 获取授权
  → 填写许可证表单并签名
  → 将已签名副本提交给经理/主管

阶段 2：热工作业期间
  → 按许可证要求执行特定预防措施
  → 在限定时间内完成工作
  → 全程保持火警监视
  → 已签名许可证必须留在现场

阶段 3：作业完成后
  → 完成后持续火警监视指定时长
  → 签署火警监视完成确认
  → 进行最终检查 + 签署许可证关闭
  → 将已关闭许可证提交给经理/主管
```

**条件授权门控（分支逻辑）：**
```
是否已完成风险评估？          → 是 / 否
该任务是否涉及热工作业？      → 是 / 否
热工作业是否在指定区域？      → 是 / 否
是否有禁火令？                → 是（禁止进行）/ 否
火灾危险等级：
  → 低-中等     → 按标准预防措施进行
  → 高          → 加强预防措施
  → 很高        → 未经经理或应急服务批准，禁止进行
  → 严重        → 未经经理或应急服务批准，禁止进行
  → 极端        → 未经经理或应急服务批准，禁止进行
  → 灾难性      → 未经经理或应急服务批准，禁止进行
授权人已批准？                → 是 / 否
```

**采集的关键数据字段：**
```
许可证：开始日期/时间、到期日期/时间、工单编号
热工作业类型：下拉（27 种 — 焊接、钎焊、切割、
  打磨、锡焊、热喷涂、火炬屋面等）
作业描述：自由文本
工作场所：机构名称、ABN、名称、街道、郊区、城市、
  州、邮编、电话、传真
工作场所分类：低风险 / 高风险 / 偏远
业务性质：自由文本
联系人：称谓、姓氏、名字、职位、手机、邮箱
主要承包商：商号、ABN、企业结构
  （担保有限公司、个体经营者、私人有限公司等）
承包商（PCBU）：与主要承包商相同字段
授权人：姓名 + 签名 + 日期
火警监视：监视员姓名 + 签字 + 时间记录
```

**对应 Laravel 数据库表：**
```
permits               → 许可证头信息（类型、日期、工单、状态）
permit_workplace      → 工作场所标识（外键：permit_id）
permit_parties        → 主要承包商 + PCBU（外键：permit_id）
permit_hazard_gates   → 决策分支结果（外键：permit_id）
permit_fire_watch     → 火警监视日志（外键：permit_id）
permit_authorisations → 带时间戳的授权审批（外键：permit_id）
```

---

## 3.15 OHSMS 表单模板全量扫描（Old WHS APPS Excels/ — 62 个文件）
- **是什么：** OHSMS/报告模块所有遗留 AppSheet 数据源文件的完整集合 — 62 个 Excel 文件，覆盖所有表单类型
- **关键发现：** 可见两种明显的架构层 — 仪表板/导航（3列）+ 数据表单（160–1019列）

**两种结构模式：**

| 类型 | 数量 | 列数 | 用途 |
|---|---|---|---|
| 仪表板/视图层 | 27 个文件 | 3列（视图名称 \| 详情 \| 图标） | 应用菜单/导航结构 |
| 表单数据导出 | 35 个文件 | 87–1019列 | 实际表单字段和响应数据 |

**通用表单头部（所有 35 个数据表单）：**
```
时间戳 | Logo | 名字 | 姓氏 | 邮箱 | 报告 ID |
页面标题 | 图片 Logo | [内容列] | 声明 | 签名 | 日期
```

**主要表单类别及列数：**

| 类别 | 主要表单 | 列数 | 特殊字段 |
|---|---|---|---|
| **检查类** | 工作场所检查清单 | 1,019 | 设备、电气、通风矩阵 |
| | 起重机操作前检查 | 848 | 详细起重机专项检查 |
| | 化学品风险评估 | 656 | 枚举工作表用于下拉；暴露控制 |
| | 事故调查报告 | 597 | 20个收件人通知槽 |
| | 消防疏散演练 | 497 | 完整疏散程序验证 |
| **评估类** | 风险评估 | 160 | 可能性/后果矩阵 |
| | OHSMS 系统审计 | 193 | 是/否合规问题 |
| | 工具箱会议表单 | 277 | 天气、议程、20人花名册 |
| | 工伤返岗计划 | 331 | 伤后协调 |
| **登记册类** | 急救伤害登记册 | 215/177（2张表） | 地点字段；治疗记录 |
| | 访客日志 | 190 | 进出追踪 |
| **通知类** | 改善通知 | 146 | 违规日期、整改范围 |
| | 禁止通知 | 128 | |

**遗留 OHSMS 表单中没有区块链字段** — 区块链是 Sushma 后来作为原型加上的（见 3.16）。

---

## 3.16 区块链 — 真实实现揭秘（重要技术澄清）
- **来源：** `Sushma/Capability Assessment Contractor Blockchain.xlsx`
- **关键发现：** 系统中的"区块链"不是真正的分布式账本 — 而是基于 MD5 哈希的篡改检测机制

**在 Excel 原型中的实现方式：**
```
A列（实时哈希）：    =MD5(CONCATENATE(C2:C4000))
B列（原始哈希）：    d41d8cd98f00b204e9800998ecf8427e  [创建时存储]
C列：时间戳
```
- 记录创建时：计算所有数据的哈希值，存储为 `OriginalHash`
- 之后每次读取：重新计算实时哈希并与 `OriginalHash` 对比
- 如果不一致 → 数据已被篡改

**"区块链 ID"（如 `dad90c29`）的真实含义：**
- 8位截断哈希，用作唯一记录标识符
- 创建时自动生成
- 提供防篡改审计追踪，无需任何真实区块链网络

**Laravel 实现 — 极其简单：**
```php
// 创建记录时：
$record->blockchain_id = substr(md5(uniqid()), 0, 8);
$record->original_hash = hash('sha256', json_encode($record->toArray()));

// 读取/验证时：
$live_hash = hash('sha256', json_encode($record->fresh()->toArray()));
$is_tampered = ($live_hash !== $record->original_hash);
```
无需 Web3、无需以太坊、无需任何外部区块链服务。只需 SHA-256（比 MD5 更安全）存入数据库即可。

> ⚠️ **v11 更正：** 生产版工作簿中 `previous_hash_required: Yes` 确认这是一条**哈希链**，而非孤立的时间点哈希。每条已签名记录必须引用上一条记录的哈希值。定义了两个锚定事件：`HASH-001` = 数字签名完成；`HASH-002` = 任务后关闭审批。Laravel 实现必须在每条审计记录上存储并串联 `previous_hash`。

**v7 数据包中"区块链锚定"关键事件**的含义：在事件发生时存储事件数据的哈希值，并记录到一张只追加（append-only）的审计表中。仅此而已。

---

## 3.18 工作簿内部 Tab 结构 — 导入合同（关键发现）

- **来源：** Kevin 与 ChatGPT 的对话（2026 年 4 月 29 日分享）+ 语音录音
- **核心发现：** 每本 SWMS/SOP 工作簿都有一套标准化的 20+ 个命名 Tab。Kevin 已在每本工作簿里内置了 `OPSF_Laravel_Table_Map` 和 `OPSF_QA_Checklist`——他在主动把自己的内容映射到 Laravel 表！

**这是 Task Pack 导入引擎的精确输入合同。**

### WHSAPP_ 前缀 Tab — WHS App 内容层

| Tab 名称 | 用途 |
|---|---|
| `README` | 数据包元信息、导入说明 |
| `WHSAPP_Index` | 任务包索引和任务名称 |
| `WHSAPP_SWMS_Data` | SWMS 正文内容 — PDF 主要来源 |
| `WHSAPP_PreStart_SWMS_15` | 15 道启动前检查题 |
| `WHSAPP_PostTask_SWMS_15` | 15 道任务后检查题 |
| `WHSAPP_Training_SWMS` | 培训测验题库 |
| `WHSAPP_Dashboard_Rules` | 仪表板显示和评分逻辑 |
| `WHSAPP_Dashboard_Data_Map` | 角色报表字段映射 |
| `WHSAPP_Role_Permissions` | 每个任务的角色可见性规则 |
| `WHSAPP_PDF_Rules` | PDF 生成和排版规则 |
| `WHSAPP_Digital_Signatures` | 签名要求和采集规则 |
| `WHSAPP_Photo_Evidence` | 照片上传要求和规则 |
| `WHSAPP_Audit_Events` | 审计日志事件规则 |
| `WHSAPP_Blockchain_Logic` | 哈希防篡改规则 |

### OPSF_ 前缀 Tab — OpsFortress 核心层

| Tab 名称 | 用途 |
|---|---|
| `OPSF_Index` | 数据包级别索引 |
| `OPSF_Occupation_Master` | 该任务的职业清单 |
| `OPSF_Industry_Master` | 该任务的行业清单 |
| `OPSF_Task_Occupation_Access` | 哪些职业可以访问该任务 |
| `OPSF_Task_Industry_Access` | 哪些行业可以看到该任务 |
| `OPSF_Laravel_Table_Map` | **字段到 Laravel 数据库表的直接映射** |
| `OPSF_QA_Checklist` | 导入验证规则和管理员 QA 清单 |
| Permit maps | 热工/密闭空间/高空作业许可证规则（后续阶段） |

**架构启示：** Task Pack 导入引擎（建设第 3 周）应先读取 `OPSF_Laravel_Table_Map` 确定列到表的映射，再用 `OPSF_QA_Checklist` 验证，通过后才提交入库。Kevin 已经做完了最难的映射工作——Laravel 导入器只需遵从这套规则即可。

### MVP 阶段 Tab 开放优先级

**MVP 阶段立即开放：**
`README`、`WHSAPP_Index`、`WHSAPP_SWMS_Data`、`WHSAPP_PreStart_SWMS_15`、`WHSAPP_PostTask_SWMS_15`、`WHSAPP_Training_SWMS`、`WHSAPP_Role_Permissions`、`WHSAPP_PDF_Rules`、`WHSAPP_Digital_Signatures`、`WHSAPP_Photo_Evidence`、`WHSAPP_Audit_Events`、`OPSF_Occupation_Master`、`OPSF_Industry_Master`、`OPSF_Task_Occupation_Access`、`OPSF_Task_Industry_Access`、`OPSF_Laravel_Table_Map`、`OPSF_QA_Checklist`

**延后到第 2 阶段以后：**
`WHSAPP_Dashboard_Rules`、`WHSAPP_Dashboard_Data_Map`（MVP 先用简单计数）、`WHSAPP_Blockchain_Logic`（有价值但不是上线必需）、Permit maps（第 3 阶段）

---

## 3.19 修正后的系统规模、数据迁移路径与三套 UI 层

### 规模修正（语音录音 — 2026 年 4 月 29 日）

| 维度 | 之前的理解 | 修正后的理解 |
|---|---|---|
| AppSheet 应用数 | ~110 个 | ~110 个——正确（这是**应用**层） |
| 内容工作簿数 | 未知 | **约 1,700 本**——每个 SWMS 或 SOP 各一本 |
| 内容创作者 | Kevin | Kevin + Brodie + Lisa（现在已在积极构建） |
| 内容完成时间 | 未知 | 距 4 月 29 日约 **2–3 周** |

**启示：** 导入引擎必须支持 1,700 份文件的批量处理。性能、错误处理和 `import_validation_results` 表不是可选项——从第 3 周起就是必需品。批量 CLI 命令（`php artisan import:taskpack`）比 UI 上传界面更适合初始数据入库。

### 数据迁移路径确认（语音录音）

```
Kevin / Brodie / Lisa
  → 在 Google Sheets 中构建工作簿（临时存储）
  → Python 脚本通过 Google Sheets API 读取数据
  → 转换并加载到 PostgreSQL
  → Laravel Eloquent 模型从 PostgreSQL 读取
```

开发团队需要 **Python 迁移脚本** 和 Laravel 导入引擎两套工具：
- Python 脚本：从 Google Sheets 一次性批量迁移（内容准备阶段使用）
- Laravel 导入引擎：持续使用——Kevin 上线后新增任务包时使用

### 三套 UI 层确认（语音录音 + Yiming 的 Cody 构建）

| UI 层 | 用户 | 状态 |
|---|---|---|
| **管理员 / 经理仪表板** | 企业主、主管 | Yiming 正在用 Cody（Cursor）构建 |
| **企业身份设置界面** | 新企业入职 | 基于 BII 文档（Kevin 的"最终提示词"4 月 28 日晚完成） |
| **工人移动端** | 现场工人 | 移动优先，快速任务执行 |

### ChatGPT 8 周 MVP 建设计划 — 已验证，采纳为交付顺序

ChatGPT 独立得出的建设计划与 TARGET_ARCHITECTURE.md 的阶段顺序完全一致：

| 周次 | 目标 | 关键交付 |
|---|---|---|
| 第 1 周 | 锁定 MVP + 数据库骨架 | Laravel 项目、PostgreSQL、Auth、租户模型、5 个基础角色、首批 Migration |
| 第 2 周 | 核心业务实体 | tenants、businesses、workplaces、workers、teams、roles、permissions、品牌占位 |
| 第 3 周 ⚠️ | **Task Pack 导入引擎**（关键周） | 上传 xlsx、解析 Tab、验证结构、存储版本、导入 SWMS/SOP/启动前/任务后/培训内容 |
| 第 4 周 | SWMS/SOP 推送给工人 | 任务分配、工人任务列表、SWMS 查看器、SOP 查看器、阅读确认、审计事件 |
| 第 5 周 | 启动前/任务后/培训运行时 | 表单运行时 + 评分 + 关键失败标记 + 基础整改措施 |
| 第 6 周 | 签名 + 照片 + PDF | 数字签名采集、S3 照片上传、PDF 生成队列、证据包 |
| 第 7 周 | 仪表板 + 试验加固 | 管理员/主管仪表板、移动端优化、Bug 修复 |
| 第 8 周 | **付费试验上线** | 5–10 个试验企业、监控、支持流程、备份 |

**第 3 周是关键路径。** 导入引擎干净，1,700 本工作簿就是核心资产；导入引擎混乱，这 1,700 本就会变成负担。

### ChatGPT 标记的风险（范围控制——重要）

| 风险 | 应对措施 |
|---|---|
| 过早追求通用引擎，拖慢进度 | 先为 SWMS/SOP 建 Task Pack Engine；第 4 周验证后再泛化 |
| MVP 定义不清 → 范围蔓延 | 第 1 周锁定 15 项 MVP 功能清单，不再增加 |
| 离线策略不明确 | 第 1 阶段=离线感知（缓存页面）；第 2 阶段=离线草稿保存；第 3 阶段=冲突同步 |
| AI 辅助开发失控 = 技术债 | 每周末做代码审查；从第 1 周起强制执行领域边界 |

### 核心 MVP 流程（已确认的商业主张）

```
创建企业账户
  → 添加工人
  → 导入任务包（从工作簿）
  → 将任务分配给工人/团队/工地
  → 工人阅读 SWMS / SOP
  → 工人完成启动前检查
  → 工人完成培训测验
  → 工人签名（数字签名）
  → 工人上传照片证据
  → PDF 生成并存储
  → 仪表板更新
```

**MVP 市场定位：** "面向澳大利亚企业的任务型 SWMS 和 SOP 合规系统——工人签字确认、启动前检查、培训记录、照片证据、审计就绪 PDF。" 而非"完整 WHS 管理系统"。

---

## 3.20 生产版 SWMS 工作簿 — 完整 65 个 Tab 结构（权威版）

- **来源：** `WHS_App_OpsFortress_SWMS_Only_Mixing_Of_Mortar_v1_production.xlsm`
- **全部 50 项 QA 检查：全部通过** — 这是已锁定的生产就绪模板
- **对比之前记录的关键修正：** 65 个 Tab（非 20 个），培训题 15 道（非 12 道），区块链 = 哈希链

### 完整 65 个 Tab 清单

**WHSAPP 层 — 内容与登记册（58 个 Tab）：**
```
核心交付（MVP）：
  README, WHSAPP_Index, WHSAPP_SWMS_Data,
  WHSAPP_Worker_App_View_Map,          ← 34列精简工人视图（含图标）
  WHSAPP_PreStart_SWMS_15,             ← 15 道是/否题
  WHSAPP_PostTask_SWMS_15,             ← 15 道是/否题
  WHSAPP_Training_SWMS                 ← 15 道题，通过分 = 12/15（80%）

作业许可证系统（每任务内嵌）：
  WHSAPP_SWMS_Permit_Map,
  WHSAPP_Permit_Type_Register,
  WHSAPP_Permit_Trigger_Matrix,
  WHSAPP_Permit_Wording_Library

风险与管控架构：
  WHSAPP_Control_Hazard_Link_Map,
  WHSAPP_Critical_Control_Verif

利益相关方登记册：
  WHSAPP_Worker_Register,
  WHSAPP_Contractor_Register,          WHSAPP_Contractor_Task_Link_Map,
  WHSAPP_Supplier_Register,            WHSAPP_Supplier_AssetChem_Link,
  WHSAPP_Workplace_Register

内容登记册（任务分解）：
  WHSAPP_Task_Register,                WHSAPP_Activity_Register,
  WHSAPP_Hazard_Register,              WHSAPP_Control_Register,
  WHSAPP_Asset_Register,               WHSAPP_Asset_Task_Link_Map,
  WHSAPP_Asset_Inspection_Rules,
  WHSAPP_HazChem_Register,             WHSAPP_HazChem_Task_Link_Map,
  WHSAPP_HazChem_Control_Rules,
  WHSAPP_Training_Register,            WHSAPP_Task_Training_Link_Map,
  WHSAPP_Activity_Training_Link,
  WHSAPP_Workplace_Induction,          ← 解答了未解问题：入职培训确实存在于此
  WHSAPP_Task_Induction_Register,
  WHSAPP_Competency_Register,
  WHSAPP_Licence_Register,
  WHSAPP_PPE_Register,
  WHSAPP_Emergency_Equip_Reg

事故与纠正措施：
  WHSAPP_Incident_Register,
  WHSAPP_Hazard_Report_Register,
  WHSAPP_Near_Miss_Register,
  WHSAPP_Corrective_Action_Reg,
  WHSAPP_Inspection_Register,
  WHSAPP_Maintenance_Register

治理与审计：
  WHSAPP_Audit_Register,
  WHSAPP_Consultation_Register,
  WHSAPP_Change_Register,
  WHSAPP_Document_Register

规则与平台配置：
  WHSAPP_Dashboard_Rules,              WHSAPP_Dashboard_Data_Map,
  WHSAPP_Alert_Rules,                  WHSAPP_Dashboard_KPIs,
  WHSAPP_Role_Permissions,             WHSAPP_PDF_Rules,
  WHSAPP_Digital_Signatures,           WHSAPP_Photo_Evidence,
  WHSAPP_Audit_Events,                 WHSAPP_Blockchain_Logic
```

**OPSF 层 — 核心数据（7 个 Tab）：**
```
  OPSF_Index,
  OPSF_Occupation_Master,              OPSF_Industry_Master,
  OPSF_Task_Occupation_Access,         OPSF_Task_Industry_Access,
  OPSF_Laravel_Table_Map,              ← 44 行映射（见下）
  OPSF_QA_Checklist                    ← 50 项检查，全部通过
```

### OPSF_Laravel_Table_Map — 完整 44 行映射

```
LTM-001  README                           → tasks
LTM-002  WHSAPP_Index                     → swms_records
LTM-003  WHSAPP_SWMS_Data                 → swms_activity_risks
LTM-004  WHSAPP_Worker_App_View_Map       → swms_responsible_roles（工人视图）
LTM-005  WHSAPP_SWMS_Permit_Map           → worker_app_view_map
LTM-006  WHSAPP_Permit_Type_Register      → swms_permit_map
LTM-007  WHSAPP_Permit_Trigger_Matrix     → permit_trigger_matrix
LTM-008  WHSAPP_Permit_Wording_Library    → permit_wording_library
LTM-009  WHSAPP_Control_Hazard_Link_Map   → control_hazard_link_map
LTM-010  WHSAPP_Critical_Control_Verif    → critical_control_verifications
LTM-011  WHSAPP_PreStart_SWMS_15          → prestart_swms_questions
LTM-012  WHSAPP_PostTask_SWMS_15          → posttask_swms_questions
LTM-013  WHSAPP_Training_SWMS             → swms_training_questions
LTM-014+ [剩余 31 个 Tab → 对应 Laravel 表，全部状态为 Active]
```
**映射关键原则：** 仅存 ID 关联关系 — 数据库中不复制任何 SWMS 风险逻辑。
**导入顺序至关重要：** 必须按 1 → 44 的顺序导入，以满足外键约束。

### 启动前/任务后列结构（16 列）
```
question_id | task_id | task_name | question_number | question_text |
response_type | na_eligibility | critical_fail | failed_response_action |
score_logic | dashboard_metric | alert_trigger | related_activity_id |
evidence_required | responsible_role | active_status
```
评分规则：是 = 1 分 | 否 = 0 分 | 不适用 = 从分母中排除

### 培训列结构（15 列）
```
training_question_id | task_id | task_name | question_number | question_text |
response_type | option_a | option_b | option_c | option_d |
correct_answer | critical_fail | corrective_action_required |
feedback_if_incorrect | learning_message
```

### 工人 App 视图映射 — 34 列结构
```
worker_view_id | task_id | activity_id | step_number | activity_name |
source_swms_column | source_activity_risk_block |
activity_icon_id | activity_icon_name | activity_icon_category |
activity_icon_reference | activity_icon_alt_text |   ← 第 12 列：工人图标渲染
hazards_from_nw | hazard_description | consequences_from_nw |
initial_risk_level | controls_from_nw_dm | residual_risk_level |
residual_risk_reason | checks_verification | verification_method |
stop_work_trigger | critical_control_failure_action |
evidence_required | evidence_prompt |
asset_ids | chemical_ids | permit_trigger_ids | critical_control_ids |
primary_task_performer | [5 个附加列]
```
每行对应一个活动（每个任务 10 行）。危险源/管控措施保存为 SWMS 第 N-W 列和 D-M 列的项目符号字符串。

### 区块链哈希链（对 v10 的修正）
```
HASH-001：数字签名完成
  hash_required: Yes
  previous_hash_required: Yes   ← 哈希链 — 引用上一条记录的哈希
  锚定事件：签名事件

HASH-002：任务后关闭审批
  hash_required: Yes
  previous_hash_required: Yes   ← 哈希链 — 引用上一条记录的哈希
  锚定事件：关闭事件
```
Laravel 实现：`audit_events` 表需要 `hash` 和 `previous_hash` 两列。写入时：计算记录数据的 SHA-256，同时存储上一个事件哈希的 SHA-256。

### 角色权限列结构（18 列）
```
role_permission_id | role_name | digital_view_allowed |
swms_acknowledgement_allowed | pre_start_submit_allowed |
post_task_submit_allowed | training_required | training_submit_allowed |
photo_upload_allowed | signature_required | manager_review_allowed |
pdf_export_allowed | print_allowed | dashboard_view_level |
audit_view_allowed | admin_edit_allowed | active_status | notes
```

### SWMS 数据结构 — 任务分解模型
```
1 个任务
  ├── B 列：说明/要求/风险概述
  ├── C 列：10 个活动（编号列表）
  ├── D-M 列：10 个管控层
  │     （授权、设备、许可证、合规、
  │      沟通、能力、监控、应急、
  │      审查、验证）
  └── N-W 列：活动风险评估（每个活动一个）
        每个块：危险源 | 后果 | 初始风险 |
                管控措施 | 剩余风险 | 责任角色
```
每个任务单行数据。活动存储在同一单元格内以项目符号列表呈现（不拆分为多行）。

**对导入引擎的含义：** C 列必须解析为项目符号列表，导入到 `activity_register` 行中。N-W 列各自映射到一个活动行，包含危险源/管控措施子记录。

---

## 3.21 OpsFortress 中央职业行业数据源包 v4

- **来源：** `OpsFortress_Central_Occupation_Industry_Source_Pack_v4_schema_locked.xlsx`
- **59 张工作表**，按 3 个治理层组织
- **当前状态：** Schema 已定义，导入登记册为空 — 等待首次从 SWMS 工作簿加载生产数据

### 三层架构

```
第 1 层 — RAW（23 张工作表，每张 1,000 行）：
  直接从 SWMS 工作簿复制粘贴，不添加任何中央元数据。
  Schema 已锁定，与工作簿 Tab 精确匹配。
  关键 Tab：RAW_All_Occupation_Master、RAW_All_Industry_Master、
  RAW_All_Task_Register、RAW_All_Activity_Register、
  RAW_All_Hazard_Register、RAW_All_Control_Register、
  RAW_All_Asset_Register、RAW_HazChem_Register、
  RAW_All_PPE_Register、RAW_All_Training_Register、
  RAW_All_Competency_Register、RAW_All_Licence_Register、
  + 11 张链接映射/规则工作表

第 2 层 — Governance（6 张工作表）：
  OPSF_Ingestion_Log       → 追踪每次源工作簿加载（当前为空）
  OPSF_Schema_Validation_Log → 每次导入的表头/列匹配情况
  OPSF_Collation_Log       → RAW→CLEAN 提升决策
  OPSF_Duplicate_Review    → 候选键重复解决
  OPSF_Merge_Register      → 记录合并决策
  OPSF_Source_Copy_Map     → 23 对强制 RAW Tab 配对关系

第 3 层 — CLEAN（20 张工作表）：
  已审批的权威记录，包含以下字段：
  approved_status | approved_by | approved_at | central_notes
  关键 Tab：OPSF_Occupation_Master_CLEAN、OPSF_Industry_Master_CLEAN、
  OPSF_Task_Master、OPSF_Activity_Master、OPSF_Hazard_Master、
  OPSF_Control_Master、OPSF_Asset_Master、OPSF_HazChem_Master、
  OPSF_PPE_Master、OPSF_Training_Master、OPSF_Competency_Master、
  OPSF_Licence_Master、OPSF_Hazard_Control_Library、
  + 7 张链接/配置 Tab
```

### 职业 / 行业分离（平行独立流）

```
职业（OCCUPATION）：
  RAW_All_Occupation_Master（26 列）
    occupation_record_id | task_id | task_name | occupation_group |
    occupation_sub_group | occupation_leaf | occupation_candidate_key |
    swms_view_access | pre_start_access | post_task_access |
    training_access | menu_visibility | ...
  ↓
  RAW_All_Task_Occupation_Access（26 列）
    task_id → occupation_record_id
    swms_view_access | acknowledgement_required | training_required | supervision_required

行业（INDUSTRY）：
  RAW_All_Industry_Master（26 列）
    industry_record_id | task_id | task_name | industry_group |
    industry_sub_group | industry_leaf | industry_candidate_key | ...
  ↓
  RAW_All_Task_Industry_Access（26 列）
    task_id → industry_record_id
    swms_applicability | worker_access | management_access
```
职业与行业之间没有交叉关联。两者各自独立映射到同一个任务。

### 候选键模式（重复防止）
所有记录使用管道符分隔的层级键：`"construction|masonry|block_laying"`
由 Duplicate_Review 和 Merge_Register 使用，在 1,700 本工作簿中检测并解决冲突。

### 关键管控系统
- `critical_control_flag` 标记一旦失败即需停工的管控措施
- 通过 `RAW_Critical_Control_Ver`：失败模式、检测方法、即时行动、证据类型
- 示例：`CC-02: "混合机防护罩和电气保护已验证"` — 关键项，每次使用均须确认

### Laravel 导入数据流
```
第 1 步：导入 SWMS 工作簿 → 将精确 Tab 复制到 RAW 工作表
第 2 步：Schema_Validation_Log 验证表头与锁定 Schema 匹配
第 3 步：Collation_Log 决定 RAW → CLEAN 提升
第 4 步：Duplicate_Review 用候选键去重
第 5 步：Merge_Register 合并近似重复记录
第 6 步：CLEAN 层 = Laravel 数据种子的权威来源
第 7 步：OPSF_Laravel_Table_Map_C 将 CLEAN 工作表映射到 Laravel 表
```

### 证据类型（所有工作表标准化）
`数字确认（Digital acknowledgement）` | `数字检查` | `清单` | `照片` | `签名` | `审计事件`

### 隐私访问级别（所有 CLEAN Tab）
`内部（Internal）` | `内部 WHS` | `工人任务访问` | `受限（Restricted）` | `工人和主管`

---

## 3.22 Kevin 语音通话 — 关键业务决策（2026-05-05 v12 新增）

- **来源：** Kevin Gowdie 与 Yiming 语音录音（2026 年 4–5 月）
- **性质：** 对 v11 中多处架构假设的修正 + 新增业务规则确认

---

### 修正一：工人任务流程顺序（v12 权威版）

v11 记录的顺序有误，以下为 Kevin 确认的正确顺序：

```
正确顺序：

  SWMS 阅读
      ↓
  数字签名（HASH-001 锚定）
      ↓
  启动前检查（可选 — 主管配置）
      ↓
  执行任务
      ↓
  任务后检查（可选 — 主管配置）
      ↓
  HASH-002 关闭锚定
      ↓
  培训评估（周期性 — 非每次执行）
```

**关键修正点：**
- 签名在启动前检查**之前**（不是之后）
- 启动前检查是可选的（主管可关闭）
- 培训是周期性触发，不是每次任务都做
- 入职培训（Induction）是首次到达工地时的一次性流程，与上面的循环分离

**⚠️ v11 的系统流程图（4.6节）需按此顺序修正**

---

### 修正二：启动前检查 & 任务后检查 — 可配置频率

这两个模块**不是强制的**。主管在设置每项任务时选择频率：

| 配置选项 | 含义 |
|---|---|
| `daily` | 每天仅完成一次；同一天第二次执行同一任务时跳过 |
| `as_needed` | 每次任务执行前均须完成 |
| `off` | 该任务完全禁用启动前/任务后检查 |

**Laravel 实现含义：**
- `tasks` 表需要 `prestart_frequency` 和 `posttask_frequency` 字段（枚举：`daily` / `as_needed` / `off`）
- `worker_task_sessions` 表需要记录当日是否已完成启动前，用于 `daily` 模式的去重判断
- 前端检查点：在渲染启动前/任务后屏幕前先查询当日完成状态

---

### 修正三：培训评估 — 周期性刷新，不是每次任务

培训评估由刷新周期驱动，**不是**每次任务执行都触发：

| 参数 | 说明 |
|---|---|
| 刷新周期 | 由任务配置（月度 / 季度 / 半年 / 年度） |
| 通过分 | 12/15（80%） |
| 临界失败题 | 答错 = 即时阻止，无论总分 |
| 过期行为 | 培训到期后下次任务开始前强制重测 |

**Laravel 实现含义：**
- `worker_training_completions` 表需要 `completed_at` + `expires_at` 字段
- 任务分配时计算 `expires_at = completed_at + refresh_interval`
- 每次启动任务时检查：`expires_at < now()` → 触发培训流程
- 未过期 → 跳过培训，直接进入任务

---

### 修正四：PDF 文档 — 双层模型（建造商→客户，非工人）

v11 中 PDF 生成的接收对象描述不清。Kevin 语音通话中明确了：

```
文档层 A — 完整 SWMS PDF（建造商 → 客户）
  来源：WHSAPP_SWMS_Data Tab（完整风险评估矩阵）
  内容：所有危险源、管控措施、责任人、证据要求
  用途：交给客户（业主/项目方）作为合规文件
  格式：正式 PDF，含 Logo、ABN、签名页
  生成时机：任务完成后，由主管或经理触发

文档层 B — 工人 App 视图（工人 → 屏幕）
  来源：WHSAPP_Worker_App_View_Map Tab（N-W 列精简视图）
  内容：精简版步骤说明 + 图标 + 危险提示
  用途：工人在手机上阅读的操作指南
  格式：移动端 UI，不生成 PDF
  生成时机：任务开始时实时渲染
```

**关键推论：**
- PDF 生成是**管理端功能**，不在工人移动端
- 工人只看 Worker App View（34列精简版），从不看完整 SWMS PDF
- `WHSAPP_PDF_Rules` Tab 控制 PDF 排版，MVP 阶段可先用 DomPDF 实现基础版
- `pdf_export_allowed` 字段在角色权限中控制谁能触发 PDF 生成

---

### 修正五：工人 — 主管 — 经理 三级权限作用域

Kevin 语音通话确认了三级层级的**数据可见性边界**：

```
经理（Manager）
  │  可见范围：其名下所有工作场所 + 所有工人
  │  操作权：配置任务频率、生成 PDF、查看全局仪表板
  │
  ├── 工作场所 A
  │     主管（Supervisor）
  │       可见范围：仅本工作场所的工人和任务
  │       操作权：启动前/任务后频率配置（本场所）
  │       │
  │       ├── 工人 1（Worker）
  │       │     可见范围：仅自己被分配的任务
  │       │     操作权：执行任务流程（只读+提交）
  │       └── 工人 2
  │
  └── 工作场所 B
        主管（另一位）
        ...
```

**Laravel 实现含义：**
- 经理与工作场所是 `many-to-many`（一个经理可管多个场所）
- 主管与工作场所是 `many-to-one`（一个主管属于一个场所）
- 工人与任务是通过工作场所隐式关联的（工人在场所 → 看到该场所的任务）
- API 所有查询必须携带 `workplace_id` 作用域过滤，防止跨场所数据泄漏

---

### 新增：内容生产进度 & 市场时间线

| 维度 | 确认数据 |
|---|---|
| 当前生产速率 | ~150 本工作簿/天（Kevin + Brodie + Lisa） |
| 总工作簿目标 | ~1,700 本（涵盖所有 SWMS + SOP） |
| 预计完成时间 | 通话时约 2–3 周（即 2026 年 5 月中旬完成） |
| 市场上线目标 | 2026 年 7 月（付费试验期） |
| 初始营销预算 | $600（Kevin 提及） |
| 第一批试验客户目标 | 5–10 家企业（来自 Kevin 现有人脉网络） |

**启示：** 第 3 周（Task Pack 导入引擎）必须在 6 月前完成，才能在 7 月上线前完成 1,700 本工作簿的批量导入。时间窗口极其紧张。

---

### 新增：MVP 5 个 Tab 确认（市场构建范围）

Kevin 在团队消息中明确限定 MVP 阶段只处理以下 5 个 Tab：

| Tab | 用途 | 工人端 | 管理端 |
|---|---|---|---|
| `WHSAPP_SWMS_Data` | SWMS 完整内容（PDF 来源） | ✗ | ✅（PDF 生成） |
| `WHSAPP_Worker_App_View_Map` | 精简步骤视图（工人阅读） | ✅ | ✗ |
| `WHSAPP_PreStart_SWMS_15` | 15 道启动前问题 | ✅（可选） | ✅（配置频率） |
| `WHSAPP_PostTask_SWMS_15` | 15 道任务后问题 | ✅（可选） | ✅（配置频率） |
| `WHSAPP_Training_SWMS` | 15 道培训题（周期性） | ✅（周期触发） | ✅（查看结果） |

其余 60 个 Tab 在后续阶段开放，MVP 阶段的导入引擎只需解析这 5 个 Tab。

---

## 3.23 第二段语音通话补充（2026-05-06 v13 新增）

- **来源：** Kevin Gowdie 与 Yiming 第二次语音录音

---

### 支付模型（首次确认）

Kevin 首次明确了计费和入职触发方式：

| 维度 | 确认内容 |
|---|---|
| 支付网关 | **Stripe**（内嵌在 App 内部） |
| 计费模型 | **按席位**（企业添加多少工人就出多少账单） |
| 入职触发 | 支付成功 → 自动开通企业账户 |
| 遗留方案参考 | AppSheet 版本用 Zapier 对接支付 → 入职 |

**Laravel 实现含义：**
- 需要 Stripe Webhook 接收 `payment_intent.succeeded` 事件
- Webhook 触发 `ProvisionTenantJob`：创建 tenant → 创建 admin 账户 → 发送欢迎邮件
- 按席位计费：`workers` 表的行数即为计费基准；Stripe 可用 metered billing 或每月手动同步席位数
- MVP 阶段可简化：手动开通 + Stripe 仅做收款，webhook 自动化放第 2 阶段

---

### 内容生产流水线 — 完全模板化（细节补充）

Kevin 的 ChatGPT prompt 模板已经"锁定"：

```
输入：一个任务关键词（如 "cement mixer", "sheep shearing"）
输出：完整工作簿所有 Tab 的内容
      ├── 所有问题文本（启动前/任务后/培训）
      ├── 评分规则和通过分
      ├── 职业/行业访问权限
      └── 所有选项和 critical_fail 标记
硬件：两台电脑并排运行，批量处理
产能：~150 本/天（已确认）
```

**架构含义：** 模板锁定意味着所有工作簿的列结构高度一致，导入引擎可以放心假设格式稳定，不需要大量容错逻辑。内容覆盖范围理论上无上限（"从剪羊毛到飞飞机"）。

---

### Worker App View — 10 列口头确认

Kevin 向其从事拆除行业的儿子解释产品时说：
> "我们从大文件里取出 10 列，就这些，就显示这个。"

这与工作簿中 WHSAPP_Worker_App_View_Map 的 N-W 列（刚好 10 列）完全吻合。这是第一次从业务对话中听到"10 列"被明确说出，与架构记录一致，无需修正。

---

### 问题语言风格 — 已简化为工人口语

Kevin 用 ChatGPT 对所有问题文本做了语言降级处理：

- **原风格：** 正式技术文档语气（"请确认所有电气设备已按规程进行绝缘检查"）
- **现风格：** 直接口语（"你检查过电线没有浸在水里吗？是 / 否"）
- **目的：** 工人不觉得被"审问"，不会因为语言太学术而抵触

**对前端 demo 的含义：** 当前 mock 数据里的问题文本如果措辞偏正式，是可以接受的临时状态——真实工作簿导入后会被口语化版本自然替换，不需要现在专门改 mock 数据。

---

### Tab 数量灵活性（再次确认）

Kevin 明确表态：MVP 阶段只取需要的 Tab，其余的留着后续再接：
> "如果我们只需要 10 个 Tab，就取 10 个；如果要 15 个，就取 15 个；剩下 60 个先放着，以后再连。"

这与 MVP 5 Tab 策略完全一致，给了导入引擎设计更大的灵活空间——引擎只需能解析指定 Tab，不需要强制处理全部 65 个。

---

## 3.24 第三段语音通话补充（2026-05-06 v14 新增）

- **来源：** Kevin + Yiming + Damon 三方语音会议录音

---

### PDF 生成底层机制（首次完整揭露）

Kevin 现场演示了 Google Sheets 里的 PDF 生成方式，核心逻辑如下：

```
WHSAPP_SWMS_Data Tab 的列结构：
  列 N = 活动 1（RMP 1）
  列 O = 活动 2（RMP 2）
  ...
  列 W = 活动 10（RMP 10）

RMP = Risk Management Plan（风险管理计划）
每一列对应一个活动的完整风险评估块。

PDF 模板（CodSops Template / SWMSTemplate）：
  → 按 RMP 1–10 的顺序从数据 Tab 拉取内容
  → 将 10 个风险块组装成一份完整的 SWMS PDF
  → 一份模板，数据来源只有一份，PDF 生成无限次
```

**两个 PDF 模板：**
- `CodSops Template`（SOP 版本）— 已在 Google Drive 找到，处理约 1,200 份 SOP 文件
- `SWMS Template`（SWMS 版本）— Kevin 需向 Luke 确认位置

**Kevin 的核心哲学：**
> "我们只写一次，使用一百万次。一份数据，无限次生成。"

**Laravel 实现含义：**
- PDF 引擎是 **query → render** 模式，不存储 PDF 内容本身
- 每次 PDF 生成时：从数据库取出该任务的 10 个活动块，注入模板
- `WHSAPP_PDF_Rules` Tab 定义了排版规则，MVP 阶段用 DomPDF 实现基础版
- PDF 生成触发点：主管/经理端（不是工人端）

---

### 告警升级链条（新业务规则）

Kevin 确认了一个时间驱动的告警升级机制：

```
工人触发告警（如：临界失败、培训未通过）
      ↓
通知发送给 → 主管（直属上级）
      ↓
[主管在 X 时间内未处理]
      ↓
自动升级 → 经理（场所级别）
      ↓
经理看到："Tim（主管）尚未处理该告警"
```

**Laravel 实现含义：**
- `alerts` 表需要 `escalated_at` 和 `escalation_level` 字段
- 队列任务（`EscalateUnacknowledgedAlerts`）定时扫描超时告警并升级
- 升级超时时长需和 Kevin 确认（建议默认：2 小时）

---

### SWMS 最小阅读时间 — UX 规则（Damon 提议，Kevin 批准）

工人在 Worker App View 的每一步页面，"下一步"按钮必须延迟解锁：

- **延迟时长：** 3–5 秒（具体值待 Kevin 最终确认）
- **目的：** 防止工人不阅读直接狂点 Next，提高合规有效性
- **法律意义：** 结合时间戳记录，可证明工人"有机会阅读"每一条

**前端实现：** 页面加载后启动倒计时，倒计时结束前按钮保持灰色不可点击。

---

### 不可篡改记录设计（正式确认）

Kevin 明确：提交后的记录**永远不能修改或回删**，只能新建记录。

```
正确做法：
  所有 submission 表：只有 INSERT，禁止 UPDATE / DELETE
  每条记录有稳定的 UUID 主键
  如需修正：新建一条 supersedes_id 指向旧记录的更正记录

错误做法：
  允许工人或主管编辑已提交的记录（篡改风险）
  允许回溯修改时间戳（法律风险）
```

**背景：** Kevin 在 AppSheet 系统中曾遇到"出事后改记录"的风险，这是不可妥协的设计原则。结合哈希链（HASH-001/002），形成完整的防篡改证据链。

---

### 系统峰值负载窗口

Kevin 根据其两个儿子（从事建筑和拆除行业）的实际经验确认：

| 时段 | 活动类型 | 负载级别 |
|---|---|---|
| 早上 6–8 点 | 所有工人集中完成启动前检查、SWMS 确认、签名 | **最高峰** |
| 工作日中午 | 偶发 SWMS 查阅 | 低 |
| 下午 3–4 点 | 任务后检查提交 | 次峰 |

**含义：** 基础设施和数据库连接池需要按早高峰设计容量上限；队列任务（PDF 生成、告警推送）应避免在高峰期执行重型操作。

---

### 竞品线索

Kevin 提及一家竞品：**Siculture.com**
- 月活约 100 万用户
- Kevin 认为其流程主要是纸质化，体验笨重
- 值得后续调研其功能和定价模型

---

### OpsFortress 多应用平台愿景（再次确认）

Kevin 明确表达了 OpsFortress 的长期定位：

> "WHS App 只是第一层皮。OpsFortress 是核心数据库，未来可以在上面套任何 App——资产管理 App、奶昔订购 App——都从同一个数据库拉数据，不同的皮肤。"

- WHS App = 第一个皮肤
- 资产登记 App = 下一个候选
- 所有 App 共享 OpsFortress 的：企业、工作场所、工人、职业、行业数据

---

## 3.17 AppSheet 表单创建流水线（两个 PowerPoint 文件）

### 来源 A：`Docs/Prepare Forms for Appsheet.pptx`
- **是什么：** Kevin 团队的内部操作指南（7步），说明如何将 Google 表单转换为可供 AppSheet 使用的数据源
- **关键发现：** AppSheet 插件会自动将分支逻辑写入 Google Sheets — 无需手动设置分支列

**7步操作流程：**
```
第1步：打开 Google Forms，安装 AppSheet 插件
       → Google Workspace Marketplace → 搜索"AppSheet" → 安装

第2步：打开需要添加分支逻辑的 Google 表单

第3步：将表单与 Google Sheets 关联
       → 点击"响应（Responses）"标签页 → 关联到 Sheets

第4步：新建电子表格并命名
       （此表格将成为 AppSheet 的数据源）

第5步：从 Forms 侧边栏打开 AppSheet 插件

第6步：点击"准备（Prepare）"并等待

第7步：完成 — 插件自动将表单问题的分支信息
       写入 Sheets 列中
       → 表格现在可以直接导入 AppSheet
```

**对重建的实际意义：** 所有约 110 个 AppSheet 应用都是通过这个流程创建的。Google Forms 的分支配置是"真正的数据源"，而非 Excel 列。在 Laravel 中，我们用 PHP 控制器/验证逻辑来复现分支逻辑，而不是依赖电子表格公式。

---

### 来源 B：`Marketing/App Creation Process.pptx`
- **是什么：** Kevin 创建新 WHS 应用的完整端到端流水线 — 从原始安全文件到 AppSheet 上线应用
- **关键发现：** 整个流水线分为 5 个阶段。PDF → Excel → Forms → AppSheet → Cloud 的顺序解释了为什么所有遗留数据都存在 Google Sheets/Drive 中

**完整 5 阶段流水线：**
```
第1阶段：PDF → Excel 数据库
  → 将源安全文件（法规、标准、实践守则）
    转换为结构化 Excel 数据库
  → 成为内容来源（危险源、管控措施、操作规程）

第2阶段：Excel → Microsoft Forms（设置分支逻辑）
  → 将内容加载到 Microsoft Forms
  → 配置分支逻辑（条件问题）
  → Forms 处理问卷式问题流程

第3阶段：Microsoft Forms → AppSheet
  → AppSheet 插件从 Forms 的问题逻辑
    自动生成带分支代码的 Excel 文件
  → 此 Excel 成为 AppSheet 的数据源表

第4阶段：AppSheet 展示（5种视图类型）
  → 主页（Home Page）  — 导航中心
  → 表单（Form）       — 数据采集（工人的主要操作界面）
  → 视频（Video）      — 嵌入式培训视频
  → 折线图（Graph）    — 汇总统计
  → 饼图（Pie Chart）  — 合规情况分解

第5阶段：云存储（Google Drive）
  → 每次表单提交均保存至 Google Drive，格式为：
    (a) Excel/Sheets 行 — 实时数据
    (b) 自动生成 PDF   — 永久记录
  → 实时同步 — 无需手动导出
```

**重建关键发现 — Laravel 替代对照表：**

| 遗留系统（AppSheet 流水线） | Laravel 等效实现 |
|---|---|
| PDF → Excel 数据库 | 数据库种子文件（tasks、hazards、controls 表） |
| Microsoft Forms 分支逻辑 | Laravel 表单验证 + 条件渲染（Inertia.js） |
| AppSheet 插件自动生成 Excel | 不需要 — Laravel 直接从数据库渲染 |
| AppSheet 表单视图 | React/Inertia 表单页面 |
| AppSheet 主页视图 | Laravel 仪表板控制器 |
| AppSheet 图表/饼图 | Recharts / Chart.js 组件 |
| Google Drive Excel 行 | Eloquent 模型 → PostgreSQL 数据行 |
| Drive 上自动生成的 PDF | Laravel PDF 生成（如 DomPDF / Browsershot） |
| 实时同步 | 数据库写入即时完成 — 无需同步层 |

**架构启示：** Kevin 构建的整套流水线之所以存在，是因为 AppSheet 必须以 Google Sheets 作为数据源。Laravel + PostgreSQL 从根本上消除了这条流水线的必要性。内容（危险源、问题、操作规程）直接写入数据库种子表；工人通过 React/Inertia 渲染的表单页面交互；提交数据直接写入数据库，并通过队列任务触发 PDF 生成。

---

## 4. 重建的核心架构洞见

### 4.0 重大更新 — 双系统架构，而非单一系统

**OpsFortress（核心平台）**
- 存储所有共享数据：企业、用户、工作场所、行业分类、职业分类
- 唯一数据来源 — 不包含 WHS 工作流
- 正在构建的可扩展、永久性系统

**WHS App（应用层）**
- 建立在 OpsFortress **之上**
- 提供：SWMS/SOP、入职培训、启动前检查、任务后评估、培训、作业许可证、WHS 报告
- 面向用户的工作流层 — 不拥有核心数据

**平台：Laravel（PHP）** — 已在 Excel README 中确认
> "Laravel 是目标平台。AppSheet 仅可作为遗留数据来源处理。"

**核心系统流程：**
```
工人 → 职业 → 任务/要求 → 活动 → 合规
```

**工人的每一个操作 = 一条活动记录：**
- 签到、入职培训、启动前检查、作业许可、培训、事故报告

**地理定位是核心功能：**
- 工作场所已打上地理标签
- 系统检测到工人到达时 → 提示签到（是/否）
- 根据位置 + 职业自动加载所需任务

**用户体验原则（不可妥协）：**
- 尽量减少文字输入
- 输入方式仅限：下一步 / 是 / 否 / 不适用 / 提交 / 拍照 / 数字签名

---

### 4.1 一个平台，多个模块 — 而非多个应用
现有系统每种设备类型对应一个独立应用。重建应打造**单一多租户平台**，其中：
- 设备/任务类型 = 内容模块（而非独立应用）
- 企业 = 拥有自有品牌工作区的租户
- 工人 = 企业租户下的用户

### 4.2 需支持的核心文档类型
| 类型 | 说明 |
|---|---|
| SWMS | 安全工作方法声明（每项任务的风险评估表） |
| SOP | 标准操作规程（分步骤操作说明） |
| 检查清单 | 操作前/中/后的是/否检查 |
| 知识评估 | 与 SOP/SWMS 内容挂钩的多选题测验 |
| 事故报告 | 多方参与的调查表单 |

### 4.3 用户层级（综合老板信件 + 团队目录）
- **集团管理员** — 付款后创建的第一个用户；建立企业身份及组织结构
- **部门/子部门/小组管理者** — 适用于大型企业的三级组织层级
- **员工** — 多种子类型：正式、临时、学徒、学生、志愿者
- **劳务派遣** — 独立序列，配备专属安全经理角色
- **承包商** — 复杂分类（石棉、清洁、建筑、拆除、维护、服务）
- **应急/医疗** — 消防队、救护、急救、全科医生、护士
- **外部人员** — 客户、访客、检查员、监管机构、保险公司、律师、工会代表

这是**企业级**的角色复杂度。权限设计必须同时考虑角色和层级。

### 4.4 企业身份建立流程（老板确认的起点）
```
收到付款 → 创建集团管理员 → 建立企业身份 →
应用品牌（Logo、ABN、地址）→ 员工入职 → 分配文档
```

### 4.5 以 Web 为先，面向现场
- 工人在现场使用手机操作
- 必须支持离线或弱网络环境（推荐 PWA）
- 界面简洁快速 — 而非复杂的管理后台

---

### 4.6 完整系统流程（v12 修正版 — Kevin 语音通话确认）

```
1. 在 OpsFortress 中创建工人
2. 分配行业 + 职业（可多个职业）
3. 工人到达已打地理标签的工作场所
4. 系统检测位置 → 提示签到
   [首次到达工地] → 完成入职培训（一次性）
5. 系统根据工人职业加载相关任务

6. 工人执行任务（每次循环）：
   a. 阅读 SWMS（Worker App View — N-W列精简版）
   b. 数字签名（HASH-001 锚定）
   c. [若 prestart_frequency ≠ off 且未触发去重] → 启动前检查
      → 评分；关键失败题 = 即时阻止
   d. 执行实际工作
   e. [若 posttask_frequency ≠ off] → 任务后检查
      → 评分；关键失败 = 触发整改
   f. HASH-002 关闭锚定
   g. [若培训已到期（expires_at < now()）] → 培训评估（15题，≥80%通过）

7. 所有步骤记录为审计活动（audit_events）
8. 自动计算合规状态（绿色/黄色/红色）
9. 主管/经理仪表板实时更新
10. [主管/经理] → 触发完整 SWMS PDF 生成 → 发送给客户（建造商合规文件）
```

**关键规则（v12 修正）：**
- 无职业 = 无法访问 SWMS/SOP
- 无活动 = 无合规追踪
- 无位置 = 无现场工作流
- 职业匹配错误 = 显示错误 SWMS（高风险错误）
- 签名在启动前检查**之前**（不是之后）⚠️ v11 错误已修正
- 启动前/任务后是**可配置可关闭**的（`daily` / `as_needed` / `off`）
- 培训是**周期性触发**，到期才重测（不是每次任务都做）
- PDF 文件是管理端向客户输出的，不是工人执行流程的一部分

---

## 5. 尚未明确的问题 — 待解答

| 问题 | 优先级 | 备注 |
|---|---|---|
| ~~"企业身份"文档（INTERNS - Business Identity Information 1.docx）内容？~~ | ✅ 已解答 | Kevin 的 BII 入职"最终提示词" 4 月 28 日晚完成；Yiming 已开始基于 BII 文档用 Cody 构建前端 |
| ~~OpsFortress ↔ WHS App 的 API/数据接口如何设计？~~ | ✅ 已解答 | 模块化单体架构（一个代码库）已在 TARGET_ARCHITECTURE.md 中确认——无需独立 API |
| ~~作业许可证（Permits to Work）的数据结构是什么？~~ | ✅ 已解答 | 热工作业许可证：3阶段流程、6级火灾等级门控、6张 Laravel 表（见 3.14） |
| 地理定位如何技术实现？ | 🔴 高 | GPS 地理围栏？二维码备用方案？现有文件中均未提及 |
| 目前有多少真实活跃客户/企业？ | 🟡 中 | 昆士兰卫生部提案为 2022 年，不确定是否签约 |
| ~~当前订阅/计费模式是什么？~~ | ✅ 已解答 | **Stripe + 按席位计费**；支付触发自动入职；MVP 可先手动开通（见 3.23） |
| ~~多职业合并（concatenation）具体如何工作？~~ | ✅ 已解答（原理） | 通过每本工作簿中的 `OPSF_Task_Occupation_Access` Tab 控制 |
| ~~入职培训（Induction）流程的数据结构是什么？~~ | ✅ 已解答 | `WHSAPP_Workplace_Induction` 和 `WHSAPP_Task_Induction_Register` Tab 已在生产版工作簿中确认（见 3.20） |
| SWMS 过期/需审查时如何处理？ | 🟡 中 | `WHSAPP_Dashboard_Rules` Tab 可能包含此逻辑——第 2 阶段读取 |
| ~~WHS 报告模块（危险源/事故/检查）结构？~~ | ✅ 已解答 | 62个表单模板全量扫描完成（见 3.15）— 通用头部模式 + 5类表单结构已梳理 |
| ~~"区块链"是真链还是哈希？~~ | ✅ 已解答 | MD5/SHA-256 哈希；每本工作簿中有 `WHSAPP_Blockchain_Logic` Tab。无需 Web3（见 3.16） |
| ~~离线同步是否为硬性需求？~~ | ✅ 已解答（策略） | 第 1 阶段=离线感知（缓存）；第 2 阶段=离线草稿；第 3 阶段=冲突同步 |
| ~~重建目标平台是什么？~~ | ✅ 已解答 | **Laravel + PostgreSQL + React/Inertia.js** 已确认 |
| ~~什么情况下触发 SWMS vs SOP？~~ | ✅ 已解答 | 通过 `OPSF_Task_Occupation_Access` 和 `OPSF_Task_Industry_Access` Tab 控制访问 |
| ~~`OPSF_Laravel_Table_Map` 的精确内容是什么？~~ | ✅ 已解答 | 生产版工作簿中已提取 44 行完整映射（LTM-001 至 LTM-013 逐行记录，LTM-014+ 全部 Active）— 见 3.20 |
| Python Google Sheets → PostgreSQL 迁移脚本如何设计？ | 🔴 高 | 已确认需要；尚未规划——第 3 周导入引擎开发前必须完成 |
| 应该先读哪本工作簿作为导入模板参考？ | 🔴 高 | Kevin 提到"砂浆混合/砌砖"为模板——已在 `others/` 文件夹中 |
| ~~多租户策略：单库 + tenant_id，还是每租户一库？~~ | ✅ 已解答（2026-05-12） | **单一共享 PostgreSQL + `tenant_id` 行级隔离**。需要更强隔离的政府客户走"独立部署一整套"路线，不在运行时维护双模式。落地强制措施详见 `opsfortress-demo/TARGET_ARCHITECTURE.md` §Tenancy Strategy |

---

## 6. 建议下一步行动

1. **阅读企业身份文档（INTERNS - Business Identity Information 1.docx）** — 老板确认这是数据字段、结构、关系和逻辑的主要参考
2. **回复 Kevin 关于"安装门"数据包的邮件** — 他希望收到反馈：结构是否可行？有哪些缺口？是否减少了工作量？
3. **梳理 OpsFortress 数据模型** — 企业 → 工作场所 → 工人 结构是首个构建重点
4. **从 Google Drive 行业/职业数据中梳理职业 → SWMS/SOP 对应关系**
5. **明确地理定位的技术实现方式** — GPS 地理围栏 vs 二维码签到备用方案
6. **为 OpsFortress 核心表 + WHS App 叠加层绘制数据模型图**

---

## 7. 文件索引

| 文件 | 类型 | 相关性 |
|---|---|---|
| `luke@...WHS Apps.pdf` | PDF | SWMS 输出样本 — 展示最终产品格式 |
| `2023.04.28 Home page.docx` | DOCX | 网站文案 — 业务定位及产品类别 |
| `4WD_Vehicle_Inspection_Checklist.xlsx` | XLSX | 检查清单模板 — 20 项操作前/中/后检查 |
| `4WD_Knowledge_Assessment_Quiz_With_Feedback.xlsx` | XLSX | 测验模板 — 20 道附反馈的多选题 |
| `4WD_Knowledge_Assessment_Quiz.xlsx` | XLSX | 无反馈版测验（简化版） |
| `4WD_Knowledge_Assessment_Quiz_GoogleSheets.xlsx` | XLSX | 上述测验的 Google Sheets 版本 |
| `Incident Investigation Report.pdf` | PDF | 事故报告表单 — 多章节、多方参与 |
| `321220_Chakradhar Reddy Garlapati.pdf` | PDF | 实习生简历 — 与架构无关 |
| `321383_Zhongda Qu.pdf` | PDF | 实习生简历 — 与架构无关 |
| `Resume_Kaushik MALIGELI.docx` | DOCX | 实习生简历 — 与架构无关 |
| `Qld Health Final Draft.docx` | DOCX | ✅ 昆士兰卫生部销售提案 — 定价模型、功能列表、应用创建流程 |
| `Team Directory Input.xlsx` | XLSX | ✅ 完整用户角色分类 + 资料字段模式 — 数据模型关键文件 |
| `appsheet/data/` | 文件夹 | 600+ 应用文件夹 — 大部分为空，结构已完成梳理 |

---

---

## 8. 系统架构图（总览）

```
┌─────────────────────────────────────────────────────┐
│                   OpsFortress                        │
│             （核心平台 — Laravel）                   │
│                                                      │
│  企业 → 工作场所 → 工人                              │
│  行业 → 职业                                         │
│  （唯一数据来源）                                    │
└─────────────────────┬───────────────────────────────┘
                      │ 数据供给
┌─────────────────────▼───────────────────────────────┐
│                   WHS App                            │
│           （应用层 — Laravel）                       │
│                                                      │
│  工人到达已打地理标签的工作场所                       │
│       ↓                                              │
│  系统检测位置 → 提示签到                              │
│       ↓                                              │
│  职业匹配 → 加载相关任务                              │
│       ↓                                              │
│  任务流程（按内容包）：                               │
│  SWMS 确认 → 启动前检查 → 入职培训 → 执行任务        │
│  → 任务后报告 → 培训评估                             │
│       ↓                                              │
│  活动记录 → 计算合规状态                              │
│       ↓                                              │
│  仪表板更新（绿色/黄色/红色）                         │
└─────────────────────────────────────────────────────┘

每项任务的内容包（10 张 Excel → Laravel 导入）：
tasks | swms_records | sop_records | prestart_questions
posttask_questions | training_questions | occupation_access
dashboard_rules | submissions | corrective_actions
```

*本文档将随分析进展持续更新。*
> Status: business and product background. Last full update 2026-05-06.
> Schema details predate the v0.3 reset (2026-05-17). For schema truth
> see the migration files plus the regenerated DBML.
>
> 状态：业务与产品背景资料。最后完整更新：2026-05-06。
> 本文中的数据库细节早于 v0.3 schema reset（2026-05-17）。
> 当前 schema 以 Laravel migrations 和重新生成的 DBML 为准。
