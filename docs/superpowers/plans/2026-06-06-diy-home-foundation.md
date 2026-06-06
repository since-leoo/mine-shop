# 首页 DIY 装修基础闭环实施计划

> **给执行代理的要求：** 必须使用 `superpowers:subagent-driven-development`（如果当前环境支持子代理）或 `superpowers:executing-plans` 来执行本计划。所有步骤使用复选框语法，便于逐项跟踪。

**目标：** 建立第一期可落地的 DIY 装修闭环，包括页面管理列表、页面类型、启用/禁用、后台草稿/发布、公开接口、小程序渲染器和可视化后台编辑器。

**架构：** 新增 `Content/DiyPage` 领域，用版本化 JSON 页面结构保存装修页面。后台接口负责页面列表、创建、复制、启用/禁用、草稿与发布流程；公开接口只返回当前页面键和页面类型下已启用、已发布的页面结构，并在服务端补齐商品组数据。小程序通过类型化组件注册表渲染已发布页面结构，同时保留现有首页作为兜底。

**技术栈：** Hyperf 3.1、PHP 8.2、DDD 服务分层、PHPUnit/co-phpunit、Vue 3、Element Plus、MineAdmin UI、Taro 4、React、TypeScript、SCSS、Vitest。

---

## 文件结构

### 后端

- 新建 `databases/migrations/2026_06_06_000001_create_diy_page_tables.php`：创建 `diy_pages` 和 `diy_page_versions`。
- 新建 `app/Infrastructure/Model/Content/DiyPage.php`：装修页面模型。
- 新建 `app/Infrastructure/Model/Content/DiyPageVersion.php`：装修页面版本模型。
- 新建 `app/Domain/Content/DiyPage/Enum/DiyPageStatus.php`：页面和版本状态常量。
- 新建 `app/Domain/Content/DiyPage/Contract/DiyPageInput.php`：页面创建/更新 DTO 契约。
- 新建 `app/Domain/Content/DiyPage/Contract/DiyPageDraftInput.php`：草稿 DTO 契约。
- 新建 `app/Domain/Content/DiyPage/Entity/DiyPageEntity.php`：页面聚合状态和发布行为。
- 新建 `app/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVo.php`：页面结构校验和规范化。
- 新建 `app/Domain/Content/DiyPage/Repository/DiyPageRepository.php`：持久化边界。
- 新建 `app/Domain/Content/DiyPage/Service/DomainDiyPageService.php`：列表、创建、复制、启用/禁用、草稿、发布、重置、查询规则。
- 新建 `app/Application/Admin/Content/AppDiyPageCommandService.php`：后台写操作编排。
- 新建 `app/Application/Admin/Content/AppDiyPageQueryService.php`：后台读操作编排。
- 新建 `app/Application/Api/Content/AppApiDiyPageQueryService.php`：公开侧读操作和商品组补齐。
- 新建 `app/Interface/Admin/Dto/DiyPage/DiyPageDto.php`：页面创建/更新 DTO。
- 新建 `app/Interface/Admin/Dto/DiyPage/DiyPageDraftDto.php`：后台草稿 DTO。
- 新建 `app/Interface/Admin/Request/DiyPage/DiyPageRequest.php`：后台请求校验。
- 新建 `app/Interface/Admin/Controller/DiyPage/DiyPageController.php`：后台接口。
- 新建 `app/Interface/Api/Controller/V1/Common/DiyPageController.php`：公开接口。
- 新建 `app/Interface/Api/Transformer/DiyPageTransformer.php`：公开响应结构转换。
- 新建测试 `tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`。
- 新建测试 `tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`。
- 新建测试 `tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`。

### 小程序

- 新建 `miniprogram/src/services/diy/page.ts`：获取已发布装修页面。
- 新建 `miniprogram/src/components/diy-renderer/types.ts`：页面结构类型定义。
- 新建 `miniprogram/src/components/diy-renderer/link.ts`：跨端跳转解析。
- 新建 `miniprogram/src/components/diy-renderer/index.tsx`：装修组件注册渲染器。
- 新建 `miniprogram/src/components/diy-renderer/index.scss`：渲染器样式。
- 新建 `miniprogram/src/components/diy/Banner/index.tsx` 和 `index.scss`。
- 新建 `miniprogram/src/components/diy/QuickNav/index.tsx` 和 `index.scss`。
- 新建 `miniprogram/src/components/diy/ImageAd/index.tsx` 和 `index.scss`。
- 新建 `miniprogram/src/components/diy/ProductGroup/index.tsx` 和 `index.scss`。
- 新建 `miniprogram/src/components/diy/TitleBar/index.tsx` 和 `index.scss`。
- 新建 `miniprogram/src/components/diy/Gap/index.tsx` 和 `index.scss`。
- 新建 `miniprogram/src/components/diy/Divider/index.tsx` 和 `index.scss`。
- 修改 `miniprogram/src/pages/home/index.tsx`：优先渲染 DIY 页面，无有效装修时回退当前首页。
- 新建测试 `miniprogram/src/components/diy-renderer/link.test.ts`。
- 新建测试 `miniprogram/src/components/diy-renderer/renderer.test.ts`。

### 后台网页端

- 新建 `web/src/modules/mall/api/diyPage.ts`：后台接口客户端。
- 新建 `web/src/modules/mall/views/diy/page/index.vue`：DIY 页面管理列表。
- 新建 `web/src/modules/mall/views/diy/editor/index.vue`：可视化编辑器外壳。
- 新建 `web/src/modules/mall/views/diy/schema/types.ts`：页面结构类型。
- 新建 `web/src/modules/mall/views/diy/schema/componentRegistry.ts`：组件默认值和表单元数据。
- 新建 `web/src/modules/mall/views/diy/components/ComponentLibrary.vue`：组件库。
- 新建 `web/src/modules/mall/views/diy/components/PhonePreview.vue`：手机预览和选中交互。
- 新建 `web/src/modules/mall/views/diy/components/PropertyPanel.vue`：属性配置面板。
- 新建 `web/src/modules/mall/views/diy/components/renderers/BannerPreview.vue`。
- 新建 `web/src/modules/mall/views/diy/components/renderers/QuickNavPreview.vue`。
- 新建 `web/src/modules/mall/views/diy/components/renderers/ImageAdPreview.vue`。
- 新建 `web/src/modules/mall/views/diy/components/renderers/ProductGroupPreview.vue`。
- 新建 `web/src/modules/mall/views/diy/components/renderers/TitleBarPreview.vue`。
- 新建 `web/src/modules/mall/views/diy/components/renderers/GapPreview.vue`。
- 新建 `web/src/modules/mall/views/diy/components/renderers/DividerPreview.vue`。

---

## 阶段 1：后端页面结构基础

### 任务 1：迁移和模型

**文件：**
- 新建：`databases/migrations/2026_06_06_000001_create_diy_page_tables.php`
- 新建：`app/Infrastructure/Model/Content/DiyPage.php`
- 新建：`app/Infrastructure/Model/Content/DiyPageVersion.php`

- [ ] **步骤 1：编写迁移**

创建 `diy_pages`，字段包括 `page_key`、`title`、`page_type`、`description`、`is_enabled`、`status`、`published_version_id`、作者字段、时间戳和软删除字段。

创建 `diy_page_versions`，字段包括 `page_id`、`version_no`、`status`、`schema`、`published_at`、作者字段和时间戳。

- [ ] **步骤 2：添加模型类**

`DiyPage` 使用 `SoftDeletes`，表名为 `diy_pages`，声明可填充字段、类型转换，以及 `versions()`、`publishedVersion()` 关联。`page_type` 只允许 `miniprogram`、`h5`、`all`，`is_enabled` 转为布尔值。

`DiyPageVersion` 使用表名 `diy_page_versions`，声明可填充字段，把 `schema` 转为数组，把 `published_at` 转为日期时间，并提供 `page()` 关联。

- [ ] **步骤 3：运行迁移测试组**

运行：`composer test -- --group=migrations`

预期：迁移测试通过；如果仓库原有迁移组存在环境依赖，需要记录具体环境问题。

- [ ] **步骤 4：提交**

```bash
git add databases/migrations/2026_06_06_000001_create_diy_page_tables.php app/Infrastructure/Model/Content/DiyPage.php app/Infrastructure/Model/Content/DiyPageVersion.php
git commit -m "feat(diy): 添加装修页面版本表"
```

### 任务 2：页面结构值对象

**文件：**
- 新建：`app/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVo.php`
- 新建：`app/Domain/Content/DiyPage/Enum/DiyPageStatus.php`
- 测试：`tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`

- [ ] **步骤 1：编写失败测试**

覆盖以下场景：

- 合法的最小首页页面结构可以通过。
- 合法页面类型 `miniprogram`、`h5`、`all` 可以通过。
- 非法页面类型抛出 `DomainException`。
- 未知组件类型抛出 `DomainException`。
- 组件 ID 重复抛出 `DomainException`。
- 组件数量超限抛出 `DomainException`。
- `publishedPayload()` 会移除禁用组件。
- `product-group` 的 `limit` 超过 50 时会被拒绝或限制。

- [ ] **步骤 2：运行测试确认失败**

运行：`vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`

预期：失败，原因是相关类尚不存在。

- [ ] **步骤 3：实现值对象**

实现以下能力：

- `fromArray(array $schema, string $pageKey): self`
- `toArray(): array`
- `publishedPayload(): array`
- 组件类型白名单
- 链接类型白名单
- 各组件的字段和数量限制

页面结构业务规则错误统一使用 `\DomainException`。

- [ ] **步骤 4：运行测试**

运行：`vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php`

预期：通过。

- [ ] **步骤 5：提交**

```bash
git add app/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVo.php app/Domain/Content/DiyPage/Enum/DiyPageStatus.php tests/Unit/Domain/Content/DiyPage/ValueObject/DiyPageSchemaVoTest.php
git commit -m "feat(diy): 添加页面结构校验"
```

### 任务 3：领域服务页面管理、草稿与发布

**文件：**
- 新建：`app/Domain/Content/DiyPage/Contract/DiyPageInput.php`
- 新建：`app/Domain/Content/DiyPage/Contract/DiyPageDraftInput.php`
- 新建：`app/Domain/Content/DiyPage/Entity/DiyPageEntity.php`
- 新建：`app/Domain/Content/DiyPage/Repository/DiyPageRepository.php`
- 新建：`app/Domain/Content/DiyPage/Service/DomainDiyPageService.php`
- 测试：`tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`

- [ ] **步骤 1：用 mock 仓储编写失败测试**

覆盖以下场景：

- `page()` 能按页面名称、页面键、页面类型、启用状态查询。
- `create()` 能创建页面，默认未启用。
- `update()` 能更新页面名称、页面类型、说明。
- `copy()` 能复制页面和最新草稿/发布结构，新页面默认未启用。
- `saveDraft()` 能创建或更新草稿版本。
- `publish()` 归档旧发布版本，并把草稿标记为已发布。
- `enable()` 启用当前页面，并禁用同一 `page_key` + `page_type` 下其他页面。
- `enable()` 在页面没有已发布版本时抛出业务异常。
- `disable()` 只关闭当前页面公开读取，不删除草稿和发布版本。
- `getPublished()` 返回规范化后的已发布 payload。
- `resetDraft()` 创建默认首页页面结构草稿。

- [ ] **步骤 2：运行测试确认失败**

运行：`vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`

预期：失败，原因是服务类尚不存在。

- [ ] **步骤 3：实现仓储方法**

需要的方法：

- `findByPageKey(string $pageKey, string $pageType = 'all'): ?DiyPage`
- `page(array $params, ?int $page = null, ?int $pageSize = null): array`
- `createPage(array $data): DiyPage`
- `updatePage(int $id, array $data): bool`
- `copyPage(DiyPage $source, array $overrides): DiyPage`
- `disableSiblings(string $pageKey, string $pageType, int $exceptId): void`
- `findDraftVersion(int $pageId): ?DiyPageVersion`
- `storeDraft(int $pageId, array $schema, ?int $operatorId): DiyPageVersion`
- `nextVersionNo(int $pageId): int`
- `publishVersion(DiyPage $page, DiyPageVersion $version, ?int $operatorId): DiyPageVersion`
- `findPublishedByPageKey(string $pageKey, string $pageType = 'all'): ?DiyPageVersion`

- [ ] **步骤 4：实现领域服务**

领域服务负责调用 `DiyPageSchemaVo` 校验页面结构，提供默认首页页面结构，并处理列表、创建、复制、启用/禁用、草稿、发布、重置和查询规则。

- [ ] **步骤 5：运行测试**

运行：`vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php`

预期：通过。

- [ ] **步骤 6：提交**

```bash
git add app/Domain/Content/DiyPage tests/Unit/Domain/Content/DiyPage/Service/DomainDiyPageServiceTest.php
git commit -m "feat(diy): 添加草稿发布领域服务"
```

---

## 阶段 2：后端后台接口和公开接口

### 任务 4：后台 DTO、Request、Service、Controller

**文件：**
- 新建：`app/Application/Admin/Content/AppDiyPageCommandService.php`
- 新建：`app/Application/Admin/Content/AppDiyPageQueryService.php`
- 新建：`app/Interface/Admin/Dto/DiyPage/DiyPageDto.php`
- 新建：`app/Interface/Admin/Dto/DiyPage/DiyPageDraftDto.php`
- 新建：`app/Interface/Admin/Request/DiyPage/DiyPageRequest.php`
- 新建：`app/Interface/Admin/Controller/DiyPage/DiyPageController.php`

- [ ] **步骤 1：编写 Request 和 DTO**

`DiyPageRequest` 校验：

- `page_key`：必填字符串，最大 64。
- `page_type`：必填枚举，`miniprogram`、`h5`、`all`。
- `title`：可空字符串，最大 100。
- `description`：可空字符串，最大 255。
- `schema`：必填数组。
- `schema.version`：必填整数，最小 1。
- `schema.page`：必填数组。
- `schema.components`：必填数组，最多 50 个。

`DiyPageDto` 实现 `DiyPageInput`。
`DiyPageDraftDto` 实现 `DiyPageDraftInput`。

- [ ] **步骤 2：实现应用服务**

`AppDiyPageCommandService` 对 `create()`、`update()`、`copy()`、`enable()`、`disable()`、`saveDraft()`、`publish()`、`resetDraft()` 使用 `Db::transaction()`。

- [ ] **步骤 3：实现后台控制器**

路由：

- `GET /admin/diy/pages/list`
- `POST /admin/diy/pages`
- `GET /admin/diy/pages/{id:\d+}`
- `PUT /admin/diy/pages/{id:\d+}`
- `PUT /admin/diy/pages/{id:\d+}/draft`
- `POST /admin/diy/pages/{id:\d+}/publish`
- `POST /admin/diy/pages/{id:\d+}/enable`
- `POST /admin/diy/pages/{id:\d+}/disable`
- `POST /admin/diy/pages/{id:\d+}/copy`
- `POST /admin/diy/pages/{id:\d+}/reset`

中间件沿用现有后台控制器：访问令牌、权限、操作日志。

权限码：

- `mall:diy:read`
- `mall:diy:create`
- `mall:diy:update`
- `mall:diy:publish`
- `mall:diy:enable`

- [ ] **步骤 4：运行 PHP 语法检查**

运行：`php -l app/Interface/Admin/Controller/DiyPage/DiyPageController.php`

预期：输出 `No syntax errors detected`。

- [ ] **步骤 5：提交**

```bash
git add app/Application/Admin/Content app/Interface/Admin/Dto/DiyPage app/Interface/Admin/Request/DiyPage app/Interface/Admin/Controller/DiyPage
git commit -m "feat(diy): 添加后台页面装修接口"
```

### 任务 5：公开接口和 Transformer

**文件：**
- 新建：`app/Application/Api/Content/AppApiDiyPageQueryService.php`
- 新建：`app/Interface/Api/Controller/V1/Common/DiyPageController.php`
- 新建：`app/Interface/Api/Transformer/DiyPageTransformer.php`
- 测试：`tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`

- [ ] **步骤 1：编写失败的 Transformer 测试**

覆盖以下场景：

- 空 payload 返回 `page => null`、`components => []`、`publishedAt => null`。
- 禁用组件不会出现在公开响应里。
- 未启用页面不会出现在公开响应里。
- 已发布时间使用 `publishedAt` 字段。

- [ ] **步骤 2：运行测试确认失败**

运行：`vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`

预期：失败，原因是 Transformer 尚不存在。

- [ ] **步骤 3：实现公开查询服务**

使用 `DomainDiyPageService::getPublished($pageKey, $pageType)`。公开查询优先取请求页面类型，例如 `miniprogram`，没有启用发布页时回退 `all`。对 `product-group` 组件调用 `AppApiProductQueryService` 补齐商品：

- `manual`：按组件中的商品 ID 查询，并保持配置顺序。
- `recommend`：调用 `page(['is_recommend' => true, 'status' => 'active'], 1, limit)`。
- `hot`：调用 `page(['is_hot' => true, 'status' => 'active'], 1, limit)`。
- `new`：调用 `page(['is_new' => true, 'status' => 'active'], 1, limit)`。

- [ ] **步骤 4：实现公开控制器**

路由：`GET /api/v1/diy/pages/{pageKey}?page_type=miniprogram`，并使用 `ApiSignatureMiddleware`。

- [ ] **步骤 5：运行测试**

运行：`vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php`

预期：通过。

- [ ] **步骤 6：提交**

```bash
git add app/Application/Api/Content app/Interface/Api/Controller/V1/Common/DiyPageController.php app/Interface/Api/Transformer/DiyPageTransformer.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php
git commit -m "feat(diy): 暴露已发布页面装修接口"
```

---

## 阶段 3：小程序装修渲染器

### 任务 6：DIY 请求服务和类型定义

**文件：**
- 新建：`miniprogram/src/services/diy/page.ts`
- 新建：`miniprogram/src/components/diy-renderer/types.ts`

- [ ] **步骤 1：添加页面结构类型**

定义 `DiyPagePayload`、`DiyComponent`、`DiyLink`，以及第一期七种组件的数据结构。

- [ ] **步骤 2：添加请求服务**

`fetchDiyPage(pageKey: string, pageType = 'miniprogram')` 通过现有 `request()` 请求 `/api/v1/diy/pages/${pageKey}`，并传递 `page_type` 参数。

- [ ] **步骤 3：运行小程序测试**

运行：`cd miniprogram && npm run test -- --runInBand`

预期：已有测试通过；如果命令参数和 Vitest 当前版本不兼容，记录问题并改用项目可用命令。

- [ ] **步骤 4：提交**

```bash
git add miniprogram/src/services/diy/page.ts miniprogram/src/components/diy-renderer/types.ts
git commit -m "feat(miniprogram): 添加装修页面请求和类型"
```

### 任务 7：跳转解析和渲染器

**文件：**
- 新建：`miniprogram/src/components/diy-renderer/link.ts`
- 新建：`miniprogram/src/components/diy-renderer/index.tsx`
- 新建：`miniprogram/src/components/diy-renderer/index.scss`
- 测试：`miniprogram/src/components/diy-renderer/link.test.ts`
- 测试：`miniprogram/src/components/diy-renderer/renderer.test.ts`

- [ ] **步骤 1：编写失败测试**

测试以下行为：

- `page` 链接跳转到指定页面路径。
- `product` 链接解析为商品详情页路径。
- 未知链接类型会被忽略。
- 未知组件类型会被跳过。

- [ ] **步骤 2：运行测试确认失败**

运行：`cd miniprogram && npm run test -- src/components/diy-renderer`

预期：失败，原因是渲染器文件尚不存在。

- [ ] **步骤 3：实现跳转解析**

使用 Taro 路由能力，不使用 DOM API。必要的平台差异放到现有平台工具函数后面。

- [ ] **步骤 4：实现组件注册渲染器**

注册并渲染：

- `banner`
- `quick-nav`
- `image-ad`
- `product-group`
- `title-bar`
- `gap`
- `divider`

跳过未知组件和禁用组件。

- [ ] **步骤 5：运行测试**

运行：`cd miniprogram && npm run test -- src/components/diy-renderer`

预期：通过。

- [ ] **步骤 6：提交**

```bash
git add miniprogram/src/components/diy-renderer
git commit -m "feat(miniprogram): 添加装修渲染器"
```

### 任务 8：基础 Taro 组件

**文件：**
- 新建：`miniprogram/src/components/diy/Banner/index.tsx`
- 新建：`miniprogram/src/components/diy/Banner/index.scss`
- 新建：`miniprogram/src/components/diy/QuickNav/index.tsx`
- 新建：`miniprogram/src/components/diy/QuickNav/index.scss`
- 新建：`miniprogram/src/components/diy/ImageAd/index.tsx`
- 新建：`miniprogram/src/components/diy/ImageAd/index.scss`
- 新建：`miniprogram/src/components/diy/ProductGroup/index.tsx`
- 新建：`miniprogram/src/components/diy/ProductGroup/index.scss`
- 新建：`miniprogram/src/components/diy/TitleBar/index.tsx`
- 新建：`miniprogram/src/components/diy/TitleBar/index.scss`
- 新建：`miniprogram/src/components/diy/Gap/index.tsx`
- 新建：`miniprogram/src/components/diy/Gap/index.scss`
- 新建：`miniprogram/src/components/diy/Divider/index.tsx`
- 新建：`miniprogram/src/components/diy/Divider/index.scss`

- [ ] **步骤 1：实现视觉组件**

只使用 Taro 跨端组件：`View`、`Text`、`Image`、`Swiper`、`SwiperItem`。`product-group` 复用现有 `GoodsList` 和 `GoodsCard`。

- [ ] **步骤 2：添加空数据保护**

图片列表、导航列表、商品组为空时返回 `null`，不渲染空壳。

- [ ] **步骤 3：运行小程序测试**

运行：`cd miniprogram && npm run test`

预期：通过。

- [ ] **步骤 4：提交**

```bash
git add miniprogram/src/components/diy
git commit -m "feat(miniprogram): 添加装修基础组件"
```

### 任务 9：首页兜底接入

**文件：**
- 修改：`miniprogram/src/pages/home/index.tsx`

- [ ] **步骤 1：添加 DIY 状态**

添加 `diyPage`、`diyLoading`、`useDiyPage` 相关状态。

- [ ] **步骤 2：先加载 DIY 页面，再进入兜底**

在现有加载流程中调用 `fetchDiyPage('home', 'miniprogram')`。如果返回 `components.length > 0`，渲染 `DiyRenderer`；否则走当前 `fetchHome()` 逻辑。

- [ ] **步骤 3：保留当前首页**

本任务不要删除现有 `HomeH5View` 或小程序原首页分支，它们是第一期兜底。

- [ ] **步骤 4：运行构建**

运行：

```bash
cd miniprogram
npm run build:weapp
npm run build:h5
```

预期：两个构建都通过。

- [ ] **步骤 5：提交**

```bash
git add miniprogram/src/pages/home/index.tsx
git commit -m "feat(miniprogram): 接入装修首页并保留兜底"
```

---

## 阶段 4：后台网页端页面管理和可视化编辑器

### 任务 10：后台接口客户端和页面结构注册表

**文件：**
- 新建：`web/src/modules/mall/api/diyPage.ts`
- 新建：`web/src/modules/mall/views/diy/schema/types.ts`
- 新建：`web/src/modules/mall/views/diy/schema/componentRegistry.ts`

- [ ] **步骤 1：添加接口客户端**

函数：

- `pageDiyPages(params)`
- `createDiyPage(data)`
- `getDiyPage(id: number)`
- `updateDiyPage(id: number, data)`
- `saveDiyDraft(id: number, data)`
- `publishDiyPage(id: number)`
- `enableDiyPage(id: number)`
- `disableDiyPage(id: number)`
- `copyDiyPage(id: number)`
- `resetDiyDraft(id: number)`

- [ ] **步骤 2：添加页面结构类型**

和小程序页面结构保持一致，但使用适合后台维护的 TypeScript 命名。

- [ ] **步骤 3：添加组件注册表**

定义每个组件的默认数据，以及 `PropertyPanel` 所需的表单元数据。

- [ ] **步骤 4：运行类型检查**

运行：`cd web && npm run lint:tsc`

预期：通过；如果存在无关历史类型错误，需要记录具体错误。

- [ ] **步骤 5：提交**

```bash
git add web/src/modules/mall/api/diyPage.ts web/src/modules/mall/views/diy/schema
git commit -m "feat(web): 添加装修编辑器页面结构和接口"
```

### 任务 11：DIY 页面管理列表

**文件：**
- 新建：`web/src/modules/mall/views/diy/page/index.vue`

- [ ] **步骤 1：实现列表页**

列表字段：

- 页面名称
- 页面键
- 页面类型：小程序、H5、通用
- 启用状态
- 发布状态
- 更新时间

- [ ] **步骤 2：实现筛选**

支持按页面名称、页面键、页面类型、启用状态筛选。

- [ ] **步骤 3：实现常规操作**

支持新建、编辑基础信息、复制、启用、禁用、进入可视化装修。

启用时，如果当前页面没有已发布版本，提示“请先发布页面后再启用”。

- [ ] **步骤 4：运行类型检查**

运行：`cd web && npm run lint:tsc`

预期：通过；如果存在无关历史类型错误，需要记录具体错误。

- [ ] **步骤 5：提交**

```bash
git add web/src/modules/mall/views/diy/page/index.vue
git commit -m "feat(web): 添加 DIY 页面管理列表"
```

### 任务 12：可视化编辑器外壳和配置面板

**文件：**
- 新建：`web/src/modules/mall/views/diy/editor/index.vue`
- 新建：`web/src/modules/mall/views/diy/components/ComponentLibrary.vue`
- 新建：`web/src/modules/mall/views/diy/components/PhonePreview.vue`
- 新建：`web/src/modules/mall/views/diy/components/PropertyPanel.vue`

- [ ] **步骤 1：实现编辑器外壳**

使用紧凑的运营工具布局：

- 顶部工具栏，展示页面名称、页面类型、保存草稿、发布、返回列表
- 左侧组件库
- 中间手机实时预览
- 右侧属性配置

不要做营销落地页式大卡片布局，控制区保持实用、密集、可扫描。

- [ ] **步骤 2：实现组件操作**

支持新增、选中、上移、下移、复制、删除、启用/禁用组件。预览区点击组件即可选中，右侧实时编辑属性。

- [ ] **步骤 3：实现草稿和发布操作**

使用 `useMessage()` 做成功和错误反馈。保存时提交完整页面结构。

- [ ] **步骤 4：运行类型检查**

运行：`cd web && npm run lint:tsc`

预期：通过；如果存在无关历史类型错误，需要记录具体错误。

- [ ] **步骤 5：提交**

```bash
git add web/src/modules/mall/views/diy/editor/index.vue web/src/modules/mall/views/diy/components/ComponentLibrary.vue web/src/modules/mall/views/diy/components/PhonePreview.vue web/src/modules/mall/views/diy/components/PropertyPanel.vue
git commit -m "feat(web): 添加 DIY 可视化装修编辑器"
```

### 任务 13：预览渲染组件

**文件：**
- 新建：`web/src/modules/mall/views/diy/components/renderers/BannerPreview.vue`
- 新建：`web/src/modules/mall/views/diy/components/renderers/QuickNavPreview.vue`
- 新建：`web/src/modules/mall/views/diy/components/renderers/ImageAdPreview.vue`
- 新建：`web/src/modules/mall/views/diy/components/renderers/ProductGroupPreview.vue`
- 新建：`web/src/modules/mall/views/diy/components/renderers/TitleBarPreview.vue`
- 新建：`web/src/modules/mall/views/diy/components/renderers/GapPreview.vue`
- 新建：`web/src/modules/mall/views/diy/components/renderers/DividerPreview.vue`

- [ ] **步骤 1：添加预览组件**

在 375px 手机预览宽度内，渲染和小程序组件接近的稳定预览效果。

- [ ] **步骤 2：保证文字不溢出**

使用固定预览宽度、行数截断和明确字号，不用随视口缩放的字体。

- [ ] **步骤 3：运行类型检查**

运行：`cd web && npm run lint:tsc`

预期：通过；如果存在无关历史类型错误，需要记录具体错误。

- [ ] **步骤 4：提交**

```bash
git add web/src/modules/mall/views/diy/components/renderers
git commit -m "feat(web): 添加装修预览组件"
```

### 任务 14：菜单入口和权限

**文件：**
- 修改：现有菜单 seeder，或按当前菜单种子模式新增 seeder。
- 可能修改：如果菜单文案需要国际化，补充 locale 文件。

- [ ] **步骤 1：检查当前菜单种子**

查看 `databases/seeders/menu_*.php`，按项目现有模式添加菜单。

- [ ] **步骤 2：添加菜单入口**

新增菜单：`商城管理 / 页面装修 / DIY 页面`，组件路径为 `mall/views/diy/page/index`。可视化编辑器通过列表操作进入，不作为主菜单入口。

- [ ] **步骤 3：添加权限**

权限码：

- `mall:diy:read`
- `mall:diy:create`
- `mall:diy:update`
- `mall:diy:publish`
- `mall:diy:enable`

- [ ] **步骤 4：提交**

```bash
git add databases/seeders
git commit -m "feat(diy): 添加后台装修菜单"
```

---

## 阶段 5：验证

### 任务 15：后端验证

**文件：**
- 除非发现问题需要修复，否则不新增文件。

- [ ] **步骤 1：运行定向 PHP 测试**

运行：

```bash
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Domain/Content/DiyPage
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/Interface/Api/Transformer/DiyPageTransformerTest.php
```

预期：通过。

- [ ] **步骤 2：运行静态分析**

运行：`composer analyse`

预期：通过；如果存在无关历史基线问题，需要记录具体错误。

- [ ] **步骤 3：必要时提交修复**

```bash
git add <fixed files>
git commit -m "fix(diy): 修复后端验证问题"
```

### 任务 16：前端验证

**文件：**
- 除非发现问题需要修复，否则不新增文件。

- [ ] **步骤 1：运行小程序检查**

运行：

```bash
cd miniprogram
npm run test
npm run build:weapp
npm run build:h5
```

预期：通过。

- [ ] **步骤 2：运行后台检查**

运行：

```bash
cd web
npm run lint:tsc
npm run build
```

预期：通过。

- [ ] **步骤 3：必要时提交修复**

```bash
git add <fixed files>
git commit -m "fix(diy): 修复前端验证问题"
```

### 任务 17：手工冒烟验证

**文件：**
- 除非发现问题需要修复，否则不新增文件。

- [ ] **步骤 1：启动后端**

运行：`composer dev`

预期：Hyperf 正常启动，没有容器错误。

- [ ] **步骤 2：启动后台**

运行：`cd web && npm run dev`

预期：Vite 输出本地访问地址。

- [ ] **步骤 3：验证后台流程**

在浏览器中：

- 打开 DIY 页面管理列表。
- 新建页面，页面键为 `home`，页面类型为小程序。
- 进入可视化装修编辑器。
- 添加轮播图。
- 添加金刚区。
- 添加商品组。
- 保存草稿。
- 发布草稿。
- 返回列表，启用该页面。
- 确认同一页面键和页面类型下只有该页面处于启用状态。

- [ ] **步骤 4：验证公开接口**

调用 `GET /api/v1/diy/pages/home?page_type=miniprogram`，并带上必要接口签名请求头。确认返回已启用、已发布页面结构。

- [ ] **步骤 5：验证小程序 H5**

运行：`cd miniprogram && npm run dev:h5`

打开生成的 H5 应用，确认首页渲染装修页面。

- [ ] **步骤 6：必要时提交冒烟修复**

```bash
git add <fixed files>
git commit -m "fix(diy): 修复冒烟验证问题"
```

---

## 执行注意事项

- 不要修改 `DomainApiOrderCommandService`，它和装修功能无关。
- 新装修入口验证完成前，不要删除或破坏现有 `/api/v1/home` 行为。
- 第一版不要删除当前小程序首页布局，它必须作为兜底。
- 手工编辑文件使用 `apply_patch`。
- 每次提交保持范围收敛，不要包含无关工作区变更。
