# 前端接口

前端接口用于移动端和 Web 端的用户功能。

## 基础信息

- **基础路径**: `/api`
- **认证方式**: Bearer Token (部分接口需要)
- **请求格式**: JSON
- **响应格式**: JSON

## 用户接口

### 用户注册

**接口**: `POST /api/user/register`

**请求**:

```json
{
  "mobile": "13800138000",
  "password": "password123",
  "code": "123456"
}
```

**响应**:

```json
{
  "code": 200,
  "message": "注册成功"
}
```

### 用户登录

**接口**: `POST /api/user/login`

**请求**:

```json
{
  "mobile": "13800138000",
  "password": "password123"
}
```

**响应**:

```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200
  }
}
```

### 获取用户信息

**接口**: `GET /api/user/profile`

**需要认证**: 是

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "mobile": "13800138000",
    "nickname": "用户昵称",
    "avatar": "https://...",
    "level": 1,
    "balance": 100.00,
    "points": 1000
  }
}
```

### 更新用户信息

**接口**: `PUT /api/user/profile`

**需要认证**: 是

**请求**:

```json
{
  "nickname": "新昵称",
  "avatar": "https://..."
}
```

## 产品接口

### 产品列表

**接口**: `GET /api/product/list`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | integer | 否 | 页码 |
| page_size | integer | 否 | 每页数量 |
| category_id | integer | 否 | 分类 ID |
| keyword | string | 否 | 搜索关键词 |
| sort | string | 否 | 排序：price_asc/price_desc/sales |

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
        "main_image": "https://...",
        "min_price": 99.00,
        "max_price": 199.00,
        "sales": 100
      }
    ],
    "total": 100
  }
}
```

### 产品详情

**接口**: `GET /api/product/{id}`

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "name": "商品名称",
    "description": "商品描述",
    "main_image": "https://...",
    "gallery_images": ["https://..."],
    "min_price": 99.00,
    "max_price": 199.00,
    "skus": [
      {
        "id": 1,
        "sku_name": "红色/L",
        "sale_price": 99.00,
        "stock": 100,
        "spec_values": {
          "颜色": "红色",
          "尺码": "L"
        }
      }
    ]
  }
}
```

## 购物车接口

### 添加到购物车

**接口**: `POST /api/cart/add`

**需要认证**: 是

**请求**:

```json
{
  "sku_id": 1,
  "quantity": 1
}
```

**响应**:

```json
{
  "code": 200,
  "message": "添加成功"
}
```

### 购物车列表

**接口**: `GET /api/cart/list`

**需要认证**: 是

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "sku_id": 1,
        "product_name": "商品名称",
        "sku_name": "红色/L",
        "image": "https://...",
        "price": 99.00,
        "quantity": 1,
        "total": 99.00
      }
    ],
    "total_amount": 99.00
  }
}
```

### 更新购物车

**接口**: `PUT /api/cart/{id}`

**需要认证**: 是

**请求**:

```json
{
  "quantity": 2
}
```

### 删除购物车项

**接口**: `DELETE /api/cart/{id}`

**需要认证**: 是

## 订单接口

### 订单预览

**接口**: `POST /api/order/preview`

**需要认证**: 是

**请求**:

```json
{
  "items": [
    {
      "sku_id": 1,
      "quantity": 1
    }
  ],
  "address_id": 1
}
```

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "goods_amount": 99.00,
    "shipping_fee": 10.00,
    "discount_amount": 0.00,
    "total_amount": 109.00,
    "items": [
      {
        "product_name": "商品名称",
        "sku_name": "红色/L",
        "quantity": 1,
        "price": 99.00
      }
    ]
  }
}
```

### 提交订单

**接口**: `POST /api/order/submit`

**需要认证**: 是

**请求**:

```json
{
  "items": [
    {
      "sku_id": 1,
      "quantity": 1
    }
  ],
  "address_id": 1,
  "remark": "备注"
}
```

**响应**:

```json
{
  "code": 200,
  "message": "订单创建成功",
  "data": {
    "order_id": 1,
    "order_no": "ORD20240101120000",
    "pay_amount": 109.00
  }
}
```

### 订单列表

**接口**: `GET /api/order/list`

**需要认证**: 是

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 订单状态 |
| page | integer | 否 | 页码 |

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
        "status": "pending",
        "pay_status": "pending",
        "total_amount": 109.00,
        "items": [
          {
            "product_name": "商品名称",
            "sku_name": "红色/L",
            "quantity": 1,
            "image": "https://..."
          }
        ],
        "created_at": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

### 订单详情

**接口**: `GET /api/order/{id}`

**需要认证**: 是

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "order_no": "ORD20240101120000",
    "status": "pending",
    "pay_status": "pending",
    "total_amount": 109.00,
    "pay_amount": 109.00,
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
    },
    "created_at": "2024-01-01 12:00:00"
  }
}
```

### 取消订单

**接口**: `POST /api/order/{id}/cancel`

**需要认证**: 是

**响应**:

```json
{
  "code": 200,
  "message": "取消成功"
}
```

### 确认收货

**接口**: `POST /api/order/{id}/confirm`

**需要认证**: 是

**响应**:

```json
{
  "code": 200,
  "message": "确认收货成功"
}
```

## 支付接口

### 创建支付

**接口**: `POST /api/payment/create`

**需要认证**: 是

**请求**:

```json
{
  "order_id": 1,
  "payment_method": "alipay"
}
```

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "payment_params": {
      "order_string": "..."
    }
  }
}
```

### 查询支付状态

**接口**: `GET /api/payment/query/{order_id}`

**需要认证**: 是

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "status": "paid",
    "paid_at": "2024-01-01 12:00:00"
  }
}
```

## 地址接口

### 地址列表

**接口**: `GET /api/address/list`

**需要认证**: 是

**响应**:

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 1,
      "consignee": "张三",
      "mobile": "13800138000",
      "province": "北京市",
      "city": "北京市",
      "district": "朝阳区",
      "address": "xxx街道xxx号",
      "is_default": true
    }
  ]
}
```

### 添加地址

**接口**: `POST /api/address`

**需要认证**: 是

**请求**:

```json
{
  "consignee": "张三",
  "mobile": "13800138000",
  "province": "北京市",
  "city": "北京市",
  "district": "朝阳区",
  "address": "xxx街道xxx号",
  "is_default": false
}
```

### 更新地址

**接口**: `PUT /api/address/{id}`

**需要认证**: 是

### 删除地址

**接口**: `DELETE /api/address/{id}`

**需要认证**: 是

### 设置默认地址

**接口**: `POST /api/address/{id}/default`

**需要认证**: 是

## 下一步

- [后台接口](/api/admin) - 查看后台管理接口
- [认证授权](/api/auth) - 了解认证机制
