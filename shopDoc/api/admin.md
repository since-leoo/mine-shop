# 后台管理接口

后台管理接口用于管理商城的各项功能。

## 基础信息

- **基础路径**: `/admin`
- **认证方式**: Bearer Token
- **请求格式**: JSON
- **响应格式**: JSON

## 认证接口

### 登录

**接口**: `POST /admin/auth/login`

**请求**:

```json
{
  "username": "admin",
  "password": "password"
}
```

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200,
    "user": {
      "id": 1,
      "username": "admin",
      "nickname": "管理员",
      "avatar": "https://..."
    }
  }
}
```

### 退出登录

**接口**: `POST /admin/auth/logout`

**响应**:

```json
{
  "code": 200,
  "message": "退出成功"
}
```

## 产品管理

### 产品列表

**接口**: `GET /admin/product/product/list`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | integer | 否 | 页码，默认 1 |
| page_size | integer | 否 | 每页数量，默认 20 |
| keyword | string | 否 | 搜索关键词 |
| category_id | integer | 否 | 分类 ID |
| brand_id | integer | 否 | 品牌 ID |
| status | string | 否 | 状态：active/inactive |

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "商品名称",
        "category_id": 1,
        "brand_id": 1,
        "min_price": 99.00,
        "max_price": 199.00,
        "status": "active",
        "created_at": "2024-01-01 12:00:00"
      }
    ],
    "total": 100,
    "page": 1,
    "page_size": 20,
    "total_pages": 5
  }
}
```

### 产品详情

**接口**: `GET /admin/product/product/{id}`

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "name": "商品名称",
    "category_id": 1,
    "brand_id": 1,
    "description": "商品描述",
    "min_price": 99.00,
    "max_price": 199.00,
    "status": "active",
    "skus": [
      {
        "id": 1,
        "sku_name": "红色/L",
        "sale_price": 99.00,
        "stock": 100
      }
    ],
    "created_at": "2024-01-01 12:00:00"
  }
}
```

### 创建产品

**接口**: `POST /admin/product/product`

**请求**:

```json
{
  "name": "商品名称",
  "category_id": 1,
  "brand_id": 1,
  "description": "商品描述",
  "skus": [
    {
      "sku_name": "红色/L",
      "sale_price": 99.00,
      "cost_price": 50.00,
      "market_price": 199.00,
      "stock": 100,
      "weight": 0.5
    }
  ]
}
```

**响应**:

```json
{
  "code": 200,
  "message": "创建成功",
  "data": {
    "id": 1,
    "name": "商品名称"
  }
}
```

### 更新产品

**接口**: `PUT /admin/product/product/{id}`

**请求**: 同创建产品

**响应**:

```json
{
  "code": 200,
  "message": "更新成功"
}
```

### 删除产品

**接口**: `DELETE /admin/product/product/{id}`

**响应**:

```json
{
  "code": 200,
  "message": "删除成功"
}
```

## 订单管理

### 订单列表

**接口**: `GET /admin/order/order/list`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | integer | 否 | 页码 |
| page_size | integer | 否 | 每页数量 |
| order_no | string | 否 | 订单号 |
| status | string | 否 | 订单状态 |
| pay_status | string | 否 | 支付状态 |
| start_time | string | 否 | 开始时间 |
| end_time | string | 否 | 结束时间 |

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "items": [
      {
        "id": 1,
        "order_no": "ORD20240101120000",
        "member_id": 1,
        "status": "paid",
        "pay_status": "paid",
        "total_amount": 99.00,
        "pay_amount": 99.00,
        "created_at": "2024-01-01 12:00:00"
      }
    ],
    "total": 100
  }
}
```

### 订单详情

**接口**: `GET /admin/order/order/{id}`

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "order_no": "ORD20240101120000",
    "member_id": 1,
    "status": "paid",
    "pay_status": "paid",
    "total_amount": 99.00,
    "pay_amount": 99.00,
    "items": [
      {
        "product_name": "商品名称",
        "sku_name": "红色/L",
        "quantity": 1,
        "unit_price": 99.00,
        "total_price": 99.00
      }
    ],
    "address": {
      "consignee": "张三",
      "mobile": "13800138000",
      "address": "北京市朝阳区xxx"
    }
  }
}
```

### 订单发货

**接口**: `POST /admin/order/order/{id}/ship`

**请求**:

```json
{
  "express_company": "顺丰速运",
  "express_no": "SF1234567890",
  "remark": "备注"
}
```

**响应**:

```json
{
  "code": 200,
  "message": "发货成功"
}
```

### 取消订单

**接口**: `POST /admin/order/order/{id}/cancel`

**请求**:

```json
{
  "reason": "取消原因"
}
```

**响应**:

```json
{
  "code": 200,
  "message": "取消成功"
}
```

## 秒杀管理

### 秒杀活动列表

**接口**: `GET /admin/seckill/activity/list`

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "items": [
      {
        "id": 1,
        "title": "双11秒杀",
        "status": "active",
        "is_enabled": true,
        "created_at": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

### 创建秒杀活动

**接口**: `POST /admin/seckill/activity`

**请求**:

```json
{
  "title": "双11秒杀",
  "description": "活动描述",
  "is_enabled": true
}
```

### 秒杀场次列表

**接口**: `GET /admin/seckill/session/list`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| activity_id | integer | 是 | 活动 ID |

### 创建秒杀场次

**接口**: `POST /admin/seckill/session`

**请求**:

```json
{
  "activity_id": 1,
  "start_time": "2024-01-01 10:00:00",
  "end_time": "2024-01-01 12:00:00",
  "max_quantity_per_user": 2,
  "total_quantity": 100
}
```

## 团购管理

### 团购活动列表

**接口**: `GET /admin/groupbuy/activity/list`

### 创建团购活动

**接口**: `POST /admin/groupbuy/activity`

**请求**:

```json
{
  "title": "团购活动",
  "product_id": 1,
  "sku_id": 1,
  "original_price": 199.00,
  "group_price": 99.00,
  "min_people": 2,
  "max_people": 10,
  "start_time": "2024-01-01 00:00:00",
  "end_time": "2024-01-31 23:59:59",
  "group_time_limit": 24,
  "total_quantity": 1000
}
```

## 会员管理

### 会员列表

**接口**: `GET /admin/member/member/list`

**参数**

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `keyword` | string | 昵称/手机号/OpenID 模糊搜索 |
| `status` | string | `active` / `inactive` / `banned` |
| `level` | string | `bronze` / `silver` / `gold` / `diamond` |
| `source` | string | `wechat` / `mini_program` / `h5` / `admin` |
| `tag_id` | int | 标签 ID |
| `created_start` / `created_end` | date | 注册时间范围 |
| `page` / `page_size` | int | 分页参数 |

**响应**

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "nickname": "小明",
        "avatar": "https://cdn.example.com/avatar.png",
        "phone": "13800000000",
        "level": "gold",
        "status": "active",
        "source": "mini_program",
        "total_orders": 8,
        "total_amount": 3299.00,
        "tags": [
          { "id": 2, "name": "高价值用户", "color": "#f56c6c", "status": "active" }
        ],
        "created_at": "2025-12-01 10:20:00",
        "last_login_at": "2026-01-25 19:30:00"
      }
    ],
    "total": 1
  }
}
```

### 会员统计

**接口**: `GET /admin/member/member/stats`

返回 `total`（累计会员）、`new_today`、`active_30d`、`sleeping_30d`、`banned` 等指标。

### 会员详情

**接口**: `GET /admin/member/member/{id}`

包含基础信息、钱包汇总、标签、收货地址等。

### 更新会员资料

**接口**: `PUT /admin/member/member/{id}`

**请求示例**

```json
{
  "nickname": "新的昵称",
  "phone": "18800000000",
  "gender": "female",
  "level": "diamond",
  "growth_value": 5200,
  "status": "active",
  "source": "admin",
  "remark": "私域高优先级用户"
}
```

### 更新会员状态

**接口**: `PUT /admin/member/member/{id}/status`

```json
{
  "status": "banned"
}
```

### 同步会员标签

**接口**: `PUT /admin/member/member/{id}/tags`

```json
{
  "tags": [1, 2, 5]
}
```

### 标签管理

| 操作 | 接口 |
| --- | --- |
| 标签列表 | `GET /admin/member/tag/list` |
| 新增标签 | `POST /admin/member/tag` |
| 更新标签 | `PUT /admin/member/tag/{id}` |
| 删除标签 | `DELETE /admin/member/tag/{id}` |
| 可用标签选项 | `GET /admin/member/tag/options` |

## 统计数据

### 首页统计

**接口**: `GET /admin/dashboard/stats`

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "order_count": 1000,
    "order_amount": 100000.00,
    "member_count": 5000,
    "product_count": 500
  }
}
```

## 下一步

- [前端接口](/api/frontend) - 查看前端接口文档
- [认证授权](/api/auth) - 了解认证机制
