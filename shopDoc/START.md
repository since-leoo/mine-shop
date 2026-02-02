# 文档协作速览

> 本页帮助新同学在 5 分钟内熟悉 Mine Shop 官方文档工作的「起步动作」。

## 已可用内容

- 📘 **指南**：项目概览、安装、配置（`guide/`）
- 🏛️ **架构**：DDD、分层设计、常用模式（`architecture/`）
- ⚙️ **核心能力**：订单、库存、支付（`core/`）
- 🔌 **API**：Admin、Frontend、Auth、Geo（`api/`）
- 🗺️ **首页**：站点 Hero、Feature、CTA（`index.md`）

## 编辑-预览流程

```bash
cd shopDoc
npm install          # 第一次运行需要
npm run docs:dev     # http://localhost:5173
```

> 所有 Markdown 保存后会实时热更新，便于核对排版与代码块。

## 常见更新事项

| 场景 | 操作建议 |
| ---- | -------- |
| 发布功能、模块 | 在 `index.md`、`guide/index.md`、对应 `core/` 或 `api/` 中同步描述 |
| 新增命令/接口 | 在 `guide/installation.md`、`guide/configuration.md`、`api/` 下补充命令参数或请求示例 |
| UI/前端体验更新 | 在指南与前端章节注明所需 Node 版本、`web/` 启动方式、Hook/组件截图说明 |
| 运营指标升级 | 更新「会员概览」「数据洞察」文字说明与示例数据 |
| 地区库/Geo 调整 | 在 `core`、`api`、`guide` 中更新 `mall:sync-regions`、`/geo/pcas` 等信息 |

## 发版检查清单

1. ✅ `npm run docs:build` 通过且无警告。
2. ✅ 所有外链（API、指南跳转）在开发预览中可打开。
3. ✅ 代码块示例与当前 `main` 分支保持一致。
4. ✅ 重要更新已出现在首页 Feature 或「关键更新」段落。
5. ✅ 中文/英文术语统一（如「Hyperf 3」「Element Plus」）。

## 联系方式

- 研发负责人：`@backend-team`
- 前端/文档负责人：`@fe-team`
- 紧急发布通道：`devops@mineadmin.com`

欢迎在 PR 中附上截图或录屏，帮助更快审核上线。*** End Patch
