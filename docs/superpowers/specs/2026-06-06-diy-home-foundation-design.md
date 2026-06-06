# 小程序首页 DIY 装修基础闭环设计

日期：2026-06-06

## 目标

为 MineShop 建立第一期小程序 DIY 装修能力，先打通“后台配置 -> 草稿保存 -> 发布 -> 接口输出 -> 小程序多端渲染”的基础闭环。

第一期聚焦首页，但后台需要先建立 DIY 页面管理列表。设计目标是把页面装修的数据结构、页面类型、启用机制、发布机制、组件边界和可视化编辑能力先稳定下来，后续再扩展模板市场、营销专题和更复杂的自由布局。

## 背景

当前项目已有：

- 后端 Hyperf + DDD 分层规范，具体约束见 `docs/DDD-ARCHITECTURE.md`。
- 后台网页端 `web` 使用 Vue 3、Vite、Element Plus、MineAdmin 组件体系。
- 小程序 `miniprogram` 使用 Taro 4、React、TypeScript，并支持 `weapp` 和 `h5` 构建。
- 首页接口为 `/api/v1/home`，由 `AppApiHomeQueryService` 和 `HomeTransformer` 输出轮播、分类、商品分区等数据。
- 小程序首页 `miniprogram/src/pages/home/index.tsx` 目前是静态结构 + 首页接口数据混合渲染。

当前没有完整装修模块。已有 `mall.home.banners`、`mall.home.activity_banner` 等配置只能解决单点配置，无法表达行业装修页面所需的组件列表、排序、草稿、发布、预览和多端渲染。

## 范围

第一期包含：

- 首页装修页面模型。
- DIY 页面管理列表。
- 页面类型：小程序、H5、通用。
- 常规启用/禁用。
- 在同一页面键和页面类型下选择一个已 DIY 好的页面启用。
- 页面草稿保存。
- 页面发布。
- 已发布页面接口。
- 后台可视化装修编辑器。
- 小程序动态装修渲染器。
- 首页兼容回退：未发布装修或接口异常时继续使用现有首页。
- 基础组件：
  - `banner` 轮播图
  - `quick-nav` 金刚区
  - `image-ad` 图片广告
  - `product-group` 商品组
  - `title-bar` 标题栏
  - `gap` 空白间距
  - `divider` 分割线

第一期不包含：

- 模板市场。
- 多业务页面落地，如分类页、会员中心、活动专题的前端接入。
- 绝对定位式自由画布。
- 页面 A/B 测试。
- 复杂人群定向、定时发布、渠道差异化投放。
- 任意 CSS 或低代码脚本能力。

## 总体方案

采用“服务端保存标准页面结构，小程序端按组件注册表渲染”的行业通用方案。

后台负责管理 DIY 页面列表和可视化编辑结构化组件数据，不允许保存任意前端代码。后端负责页面结构校验、草稿、发布和启用状态管理。公开接口只返回当前页面键和页面类型下已启用且已发布的版本。小程序端维护组件注册表，根据 `type` 找到对应 Taro 组件渲染。

首页改造保持可回退：

1. 小程序请求装修接口，并声明页面键 `home` 和页面类型 `miniprogram`。
2. 如果存在已启用、已发布且组件列表有效的装修页面，渲染装修页面。
3. 如果无启用发布版本、接口失败、页面结构不可用，则沿用当前 `fetchHome()` 首页渲染。

这样第一期不会一次性替换现有首页，也不会影响当前交易、商品、营销链路。

## 后端设计

### 目录边界

新增独立内容装修域，避免混入订单、商品、系统设置：

```text
app/Domain/Content/DiyPage/
app/Application/Admin/Content/
app/Application/Api/Content/
app/Interface/Admin/Controller/DiyPage/
app/Interface/Admin/Dto/DiyPage/
app/Interface/Admin/Request/DiyPage/
app/Interface/Api/Controller/V1/Common/
app/Interface/Api/Transformer/
app/Infrastructure/Model/Content/
```

命名遵循现有 DDD 规范：

- 后台写服务：`AppDiyPageCommandService`
- 后台读服务：`AppDiyPageQueryService`
- 小程序读服务：`AppApiDiyPageQueryService`
- 领域服务：`DomainDiyPageService`
- 小程序领域查询：如需要复杂公开侧逻辑，再补 `DomainApiDiyPageQueryService`

### 数据表

第一期使用两张表。

`mall_diy_pages`：

- `id`
- `page_key`：页面键，如 `home`
- `title`：页面名称
- `page_type`：页面类型，`miniprogram`、`h5`、`all`
- `description`：页面说明
- `is_enabled`：是否启用
- `status`：`draft`、`published`、`disabled`
- `published_version_id`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at`

`mall_diy_page_versions`：

- `id`
- `page_id`
- `version_no`
- `status`：`draft`、`published`、`archived`
- `schema`：JSON，完整页面配置
- `published_at`
- `created_by`
- `created_at`
- `updated_at`

不单独建立组件表。组件是页面版本 JSON 的一部分，第一期可减少跨表排序和发布一致性问题。后续若需要组件复用、组件埋点、组件级权限，再拆表。

启用规则：

- 同一个 `page_key` + `page_type` 只能启用一个 DIY 页面。
- `page_type=all` 是通用页面，可作为小程序和 H5 的兜底。
- 小程序请求时优先查 `page_type=miniprogram`，没有启用发布页时再查 `page_type=all`。
- H5 请求时优先查 `page_type=h5`，没有启用发布页时再查 `page_type=all`。
- 启用前必须存在已发布版本，不能启用只有草稿的页面。
- 禁用只影响公开端读取，不删除草稿和历史版本。

### 页面结构标准

页面结构固定为：

```json
{
  "version": 1,
  "page": {
    "key": "home",
    "title": "首页",
    "backgroundColor": "#f7f8fa"
  },
  "components": [
    {
      "id": "cmp_20260606120000_001",
      "type": "banner",
      "name": "轮播图",
      "enabled": true,
      "props": {},
      "style": {},
      "data": {}
    }
  ]
}
```

通用规则：

- `version` 必填，用于后续页面结构升级。
- `components` 按数组顺序渲染。
- `id` 必须在页面内唯一。
- `type` 必须在组件注册表中存在。
- `enabled=false` 的组件后台保留，接口默认不输出。
- `props` 放业务配置。
- `style` 只允许白名单样式，如背景色、边距、圆角。
- `data` 放组件数据，如图片列表、导航项、商品选择规则。

### 基础组件页面结构

`banner`：

```json
{
  "type": "banner",
  "props": {
    "height": 160,
    "radius": 8,
    "autoplay": true,
    "interval": 3000
  },
  "data": {
    "items": [
      {
        "image": "https://example.com/banner.png",
        "title": "春季上新",
        "link": {
          "type": "url",
          "value": "/pages/goods/list/index"
        }
      }
    ]
  }
}
```

`quick-nav`：

```json
{
  "type": "quick-nav",
  "props": {
    "columns": 5,
    "rows": 1
  },
  "data": {
    "items": [
      {
        "icon": "https://example.com/icon.png",
        "text": "拼团活动",
        "link": {
          "type": "page",
          "value": "/pages/promotion/group-buy/index"
        }
      }
    ]
  }
}
```

`image-ad`：

```json
{
  "type": "image-ad",
  "props": {
    "layout": "single"
  },
  "data": {
    "items": [
      {
        "image": "https://example.com/ad.png",
        "link": {
          "type": "url",
          "value": "/pages/coupon/coupon-center/index"
        }
      }
    ]
  }
}
```

`product-group`：

```json
{
  "type": "product-group",
  "props": {
    "title": "精选推荐",
    "layout": "two-column",
    "limit": 10
  },
  "data": {
    "source": "manual",
    "productIds": [1, 2, 3]
  }
}
```

商品来源第一期支持：

- `manual`：手动商品 ID。
- `recommend`：推荐商品。
- `hot`：热卖商品。
- `new`：新品。

接口输出时由后端补齐商品列表，避免小程序端再根据组件发多次商品请求。

### 领域规则

`DomainDiyPageService` 负责：

- 创建或获取页面。
- 页面列表查询。
- 复制页面。
- 保存草稿。
- 发布草稿。
- 启用页面。
- 禁用页面。
- 校验页面结构。
- 过滤禁用组件。
- 限制组件类型白名单。
- 限制图片、导航、商品数量。
- 校验 `page.key` 与页面主表 `page_key` 一致。

第一期校验重点：

- 首页只能有一个 `page_key=home`。
- `page_type` 只能是 `miniprogram`、`h5`、`all`。
- 同一 `page_key` + `page_type` 下只能启用一个页面。
- 启用页面前必须已有已发布版本。
- `components` 最大 50 个。
- 单个 `banner` 最多 10 张图。
- `quick-nav` 最多 20 个入口。
- `image-ad` 最多 10 张图。
- `product-group.limit` 最大 50。
- 链接只允许白名单类型：`page`、`url`、`product`、`category`、`coupon`、`group_buy`、`seckill`。

### 接口

后台接口：

- `GET /admin/diy/pages/list`：页面管理列表。
- `POST /admin/diy/pages`：创建 DIY 页面。
- `GET /admin/diy/pages/{id}`：获取页面、草稿、发布状态。
- `PUT /admin/diy/pages/{id}`：更新页面标题、类型、说明。
- `PUT /admin/diy/pages/{id}/draft`：保存草稿。
- `POST /admin/diy/pages/{id}/publish`：发布草稿。
- `POST /admin/diy/pages/{id}/enable`：启用页面。
- `POST /admin/diy/pages/{id}/disable`：禁用页面。
- `POST /admin/diy/pages/{id}/copy`：复制页面。
- `POST /admin/diy/pages/{id}/reset`：恢复默认草稿。

小程序接口：

- `GET /api/v1/diy/pages/{pageKey}?page_type=miniprogram`：获取当前类型下已启用、已发布的装修页面。

接口返回示例：

```json
{
  "page": {
    "key": "home",
    "title": "首页",
    "backgroundColor": "#f7f8fa"
  },
  "components": [
    {
      "id": "cmp_001",
      "type": "banner",
      "props": {},
      "style": {},
      "data": {}
    }
  ],
  "publishedAt": "2026-06-06 15:00:00"
}
```

无发布版本时返回空页面对象或 404 均可。为降低小程序复杂度，建议返回成功响应：

```json
{
  "page": null,
  "components": [],
  "publishedAt": null
}
```

### 与现有首页接口的关系

第一期不删除 `/api/v1/home`。

装修接口成为新入口，当前首页接口继续作为兜底和老页面数据源。`product-group` 所需商品数据在后端可复用现有 `AppApiProductQueryService`。

后续稳定后，可以让 `/api/v1/home` 内部优先读取装修配置，或逐步迁移小程序首页只依赖装修接口。

## 后台网页端设计

新增页面管理列表：`web/src/modules/mall/views/diy/page/index.vue`。

列表能力：

- 按页面名称、页面键、页面类型、启用状态查询。
- 显示页面名称、页面键、页面类型、启用状态、发布状态、更新时间。
- 操作：新建、编辑、可视化装修、复制、启用、禁用、删除或软删除。
- 启用操作用于“小程序装修时选择已经 DIY 好的页面点启用即可”的场景。
- 启用前如果页面没有已发布版本，后台提示先进入可视化编辑器发布。

新增可视化装修编辑器：`web/src/modules/mall/views/diy/editor/index.vue`。

界面结构：

- 顶部操作栏：页面名称、页面类型、保存草稿、发布、恢复默认、返回列表。
- 左侧组件库：展示基础组件，点击添加。
- 中间手机预览：按页面结构顺序实时渲染组件预览，点击预览组件可选中。
- 右侧配置面板：编辑当前组件的 props、style、data。

第一期交互：

- 点击组件添加到页面底部。
- 支持拖拽或按钮调整组件顺序；如果拖拽实现成本高，先提供上移/下移并预留拖拽接入点。
- 选中预览组件后在右侧编辑。
- 支持上移、下移、复制、删除、启用/禁用。
- 支持保存草稿。
- 支持发布草稿。
- 支持恢复默认首页装修。
- 可视化预览必须与小程序渲染结构保持同一份页面结构，后台不维护另一套私有布局数据。

第一期不做绝对定位式自由画布。可视化编辑以“组件库 + 手机实时预览 + 属性面板”为准，符合商城装修后台的常见工作流。

后台文件建议：

```text
web/src/modules/mall/api/diyPage.ts
web/src/modules/mall/views/diy/page/index.vue
web/src/modules/mall/views/diy/editor/index.vue
web/src/modules/mall/views/diy/components/ComponentLibrary.vue
web/src/modules/mall/views/diy/components/PhonePreview.vue
web/src/modules/mall/views/diy/components/PropertyPanel.vue
web/src/modules/mall/views/diy/components/renderers/
web/src/modules/mall/views/diy/schema/
```

## 小程序设计

新增服务：

```text
miniprogram/src/services/diy/page.ts
```

新增装修渲染器：

```text
miniprogram/src/components/diy-renderer/index.tsx
miniprogram/src/components/diy-renderer/index.scss
miniprogram/src/components/diy/Banner/index.tsx
miniprogram/src/components/diy/QuickNav/index.tsx
miniprogram/src/components/diy/ImageAd/index.tsx
miniprogram/src/components/diy/ProductGroup/index.tsx
miniprogram/src/components/diy/TitleBar/index.tsx
miniprogram/src/components/diy/Gap/index.tsx
miniprogram/src/components/diy/Divider/index.tsx
miniprogram/src/components/diy/link.ts
```

渲染器职责：

- 接收页面结构。
- 跳过未知组件。
- 跳过禁用组件。
- 根据 `type` 找到组件。
- 给组件传入 `props`、`style`、`data`。
- 统一处理跳转。

首页接入：

- `pages/home/index.tsx` 页面加载时先请求 `fetchDiyPage('home', 'miniprogram')`。
- 如果返回组件列表非空，渲染 `DiyRenderer`。
- 如果失败或为空，保留当前首页渲染。

### 多平台策略

小程序端只使用 Taro 跨端组件：

- `View`
- `Text`
- `Image`
- `Swiper`
- `SwiperItem`
- `ScrollView`

不直接使用 DOM API，不依赖 `window`、`document`。跳转统一使用封装方法：

- H5 使用 Taro 路由。
- 微信小程序使用 Taro 路由。
- 外部 URL 第一阶段在小程序内不直接打开，只做白名单页面跳转或忽略。

页面结构可预留：

```json
{
  "platform": {
    "weapp": {},
    "h5": {}
  }
}
```

第一期不主动使用平台差异配置，保证一套装修配置多端可跑。

## 默认首页模板

首次启用时提供默认页面结构：

1. `banner`
2. `quick-nav`
3. `image-ad`
4. `title-bar`
5. `product-group` 推荐商品
6. `title-bar`
7. `product-group` 热卖商品

默认模板不写死具体商品 ID，商品组使用动态来源。这样新环境没有人工配置时也能展示基础首页。

## 错误处理

后台：

- 页面结构校验失败时返回具体组件位置和字段。
- 发布前必须有有效组件。
- 发布失败不覆盖当前已发布版本。

小程序：

- 装修接口失败时回退现有首页。
- 单个未知组件跳过，不阻断整页。
- 单个图片为空时不渲染该图片项。
- 商品组为空时隐藏该商品组。

后端：

- 无已发布版本返回空装修结构。
- 商品补齐失败时返回空商品组，不抛出到整页。

## 测试策略

后端单测：

- 页面结构校验通过。
- 未知组件拒绝保存。
- 草稿保存不影响已发布版本。
- 发布后接口返回发布版本。
- 禁用组件不进入公开接口。
- `product-group` 不同来源能补齐商品结构。

后台网页端验证：

- `npm run lint:tsc`
- 手工验证添加、编辑、排序、删除、保存草稿、发布。

小程序测试：

- `npm run test`
- `npm run build:weapp`
- `npm run build:h5`
- 手工验证 H5 和微信小程序首页装修渲染。
- 手工验证接口失败时回退现有首页。

## 实施顺序

1. 后端表结构和模型。
2. 后端 Domain 页面结构校验与草稿/发布服务。
3. 后端页面列表、启用/禁用、复制、后台接口。
4. 小程序公开接口。
5. 小程序装修渲染器和基础组件。
6. 首页接入装修兜底逻辑。
7. 后台 DIY 页面管理列表。
8. 后台可视化装修编辑器。
9. 默认首页模板和菜单入口。
10. 全量验证。

## 风险与取舍

- 第一阶段不用完整拖拽，降低编辑器复杂度。
- 第一阶段不拆组件表，保证发布版本原子性。
- 第一阶段保留现有首页兜底，降低上线风险。
- 第一阶段限制样式白名单，避免小程序多端表现失控。
- 第一阶段商品数据由后端补齐，避免小程序发起组件级多请求。

## 验收标准

- 后台可以打开 DIY 页面管理列表。
- 后台可以创建、编辑、复制、启用、禁用 DIY 页面。
- 页面支持 `miniprogram`、`h5`、`all` 类型。
- 同一页面键和页面类型下只能启用一个页面。
- 后台可以从列表进入可视化装修编辑器。
- 后台可以新增、编辑、排序、删除基础组件。
- 后台可以保存草稿。
- 后台可以发布草稿。
- 小程序首页可以渲染已启用、已发布的小程序装修页面。
- 未发布装修时小程序首页保持当前表现。
- `weapp` 和 `h5` 构建通过。
- 后端新增服务遵循 `docs/DDD-ARCHITECTURE.md` 的分层规范。
