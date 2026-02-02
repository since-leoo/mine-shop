# 后台管理接口（Admin API）

后台接口以 `/admin` 为前缀，供运营、客服、商品/订单/营销/会员等后台页面调用。所有接口采用 JSON，需携带 Admin 侧 JWT。示例省略域名，默认 `https://api.example.com`。

## 认证

| 功能 | 方法 & 路径 | 说明 |
| ---- | ----------- | ---- |
| 登录 | `POST /admin/auth/login` | 账号密码登录，返回 `access_token`/`refresh_token` |
| 刷新 | `POST /admin/auth/refresh` | 使用 Refresh Token 获取新的 Access Token |
| 登出 | `POST /admin/auth/logout` | 立即失效当前 Access Token |

```json
POST /admin/auth/login
{ "username": "admin", "password": "Pass@123" }

// 响应
{
  "code": 200,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200,
    "user": { "id": 1, "nickname": "超级管理员" }
  }
}
```

## 商品管理

| 功能 | 方法 & 路径 | 关键参数 |
| ---- | ----------- | -------- |
| 商品列表 | `GET /admin/product/product/list` | `page`, `keyword`, `category_id`, `status` |
| 商品详情 | `GET /admin/product/product/{id}` |  |
| 创建商品 | `POST /admin/product/product` | 基础信息 + `skus[]`（含价格库存） |
| 更新商品 | `PUT /admin/product/product/{id}` | 同创建 |
| 删除商品 | `DELETE /admin/product/product/{id}` | 软删 |

**SKU 示例**：

```json
{
  "name": "双拼礼盒",
  "category_id": 3,
  "brand_id": 12,
  "description": "节日必备",
  "skus": [
    { "sku_name": "红/XL", "sale_price": 199.00, "cost_price": 88.00, "stock": 500 },
    { "sku_name": "蓝/L", "sale_price": 189.00, "cost_price": 80.00, "stock": 480 }
  ]
}
```

## 订单与履约

| 功能 | 方法 & 路径 | 说明 |
| ---- | ----------- | ---- |
| 订单列表 | `GET /admin/order/order/list` | 支持按订单号、状态、支付状态、时间区间筛选 |
| 订单详情 | `GET /admin/order/order/{id}` | 包含订单项、收货地址、日志 |
| 发货 | `POST /admin/order/order/{id}/ship` | 传入 `express_company`、`express_no` |
| 取消 | `POST /admin/order/order/{id}/cancel` | `reason` |
| 订单日志 | `GET /admin/order/order/{id}/logs` | （如开启）查看操作轨迹 |

## 营销中心

### 优惠券

| 功能 | 方法 & 路径 |
| ---- | ----------- |
| 列表/筛选 | `GET /admin/mall/coupon/list` |
| 详情 | `GET /admin/mall/coupon/{id}` |
| 创建/更新 | `POST /admin/mall/coupon` / `PUT /admin/mall/coupon/{id}` |
| 发放 | `POST /admin/mall/coupon/issue`（支持批量或按会员） |
| 切换状态 | `PUT /admin/mall/coupon/{id}/status` |

表单默认值：面额、阈值、日期区间、状态开关在前端有 UI 限制，后端需校验开始<结束、库存>=发放量等。

### 团购

| 功能 | 方法 & 路径 | 备注 |
| ---- | ----------- | ---- |
| 活动列表 | `GET /admin/mall/group-buy/list` |  |
| 详情 | `GET /admin/mall/group-buy/{id}` |  |
| 创建/更新 | `POST /admin/mall/group-buy` / `PUT /admin/mall/group-buy/{id}` | `original_price`、`group_price`、`stock` 默认 0 可编辑；`start_time`/`end_time` 默认当天 |
| 状态切换 | `PUT /admin/mall/group-buy/{id}/status` | 上/下架 |

后台验证包括价格区间、人数阈值、成团时限等。

### 秒杀

- 活动：`/admin/seckill/activity/*`
- 场次：`/admin/seckill/session/*`
- 商品：`/admin/seckill/product/*`

提供多场次、限购、库存脚本。所有库存操作最终落在 `OrderStockService` + Redis/Lua。

## 会员中心

| 功能 | 方法 & 路径 | 说明 |
| ---- | ----------- | ---- |
| 会员列表 | `GET /admin/member/member/list` | 支持关键字、等级、状态、来源、标签、注册/登录时间等筛选 |
| 会员详情 | `GET /admin/member/member/{id}` | 含钱包、积分、地址、标签、地区路径 |
| 新建/编辑 | `POST /admin/member/member` / `PUT /admin/member/member/{id}` | 支持 `region_path`、省市区街字段 |
| 概览驾驶舱 | `GET /admin/member/member/overview` | 返回趋势、地区分布、等级结构 |
| 统计卡片 | `GET /admin/member/member/stats` | 今日新增、30 天活跃/沉睡、禁用等 |
| 标签管理 | `GET/POST/PUT/DELETE /admin/member/tag` | 标签选项、颜色、排序 |
| 钱包日志 | `GET /admin/member/account/wallet/logs` | balance / points 两种钱包 |
| 调整钱包/积分 | `POST /admin/member/account/adjust` | 需传 `type`、`value`、`source`、`remark` |

### Geo 级联

- 表单使用 `/common/geo/pcas` 及 `/geo/search` 数据。
- 后台可在系统任务中调用 `php bin/hyperf.php mall:sync-regions` 或配置 Crontab 自动同步。

## Geo 管理接口

虽然 Geo API 对外暴露 `/geo/*`（见公共 API），后台仍可在系统工具中触发同步：

| 功能 | 方法 & 路径 | 说明 |
| ---- | ----------- | ---- |
| 地址库版本列表 | `GET /admin/system/geo/version` *(若开放)* | 查看版本与同步时间 |
| 同步命令 | `php bin/hyperf.php mall:sync-regions --source=modood` | CLI 方式，支持 `--url`、`--force`、`--dry-run` |
| 定时任务 | `config/autoload/crontab.php` | 示例：每日凌晨 03:30 执行同步 |

## 系统设置 & 权限

- 菜单/角色/用户：`/admin/permission/*`
- 数据权限：`/admin/permission/data-scope`
- 系统设置：`/admin/system/setting`（商城配置、订单、支付、会员、内容等）
- 附件：`/admin/attachment/*`
- 日志：`/admin/logstash/user-login`、`/admin/logstash/user-operation`

## 仪表盘与统计

- `GET /admin/dashboard/stats`：概览 KPI（订单数、GMV、会员数等）。
- `GET /admin/dashboard/trend`：可扩展为多维可视化接口。

## 通用响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "items": [],
    "total": 0
  },
  "trace_id": "a5b8c7d9..." // 若启用链路追踪
}
```

错误示例（422 表单验证失败）：

```json
{
  "code": 422,
  "message": "表单验证失败",
  "errors": {
    "start_time": ["开始时间必须早于结束时间"]
  }
}
```

## 小贴士

1. **权限**：Admin API 所有路由默认启用 `PermissionMiddleware`，请在菜单/角色中配置按钮权限码。
2. **数据权限**：查询接口自动拼接 DataScope 条件（全部/部门/本人）。
3. **幂等**：钱包调整、券发放、库存操作等敏感接口建议提供业务幂等键。
4. **调试**：使用 `X-Request-Id` / `trace_id` 关联链路；同时可在操作日志中查看关键管理员行为。

下一步：前往 [前端接口](/api/frontend) / [认证授权](/api/auth) / [Geo API](/api/index#geo-api-说明) 了解更多调用细节。*** End Patch
