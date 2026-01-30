# API 概览

本系统提供完整的 RESTful API 接口，分为后台管理接口和前端接口两部分。

## 接口分类

### 后台管理接口

基础路径：`/admin`

- [产品管理](/api/admin#产品管理)
- [订单管理](/api/admin#订单管理)
- [会员管理](/api/admin#会员管理)
- [秒杀管理](/api/admin#秒杀管理)
- [团购管理](/api/admin#团购管理)
- [权限管理](/api/admin#权限管理)

### 前端接口

基础路径：`/api`

- [用户接口](/api/frontend#用户接口)
- [产品接口](/api/frontend#产品接口)
- [订单接口](/api/frontend#订单接口)
- [支付接口](/api/frontend#支付接口)
- [地址接口](/api/frontend#地址接口)

## 通用说明

### 请求格式

所有接口均使用 JSON 格式进行数据交互。

**请求头**：

```http
Content-Type: application/json
Authorization: Bearer {access_token}
```

### 响应格式

统一的响应格式：

```json
{
  "code": 200,
  "message": "success",
  "data": {}
}
```

**字段说明**：

- `code`: 响应码，200 表示成功
- `message`: 响应消息
- `data`: 响应数据

### 响应码

| 响应码 | 说明 |
|-------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 500 | 服务器错误 |

### 分页参数

列表接口支持分页查询：

**请求参数**：

```json
{
  "page": 1,
  "page_size": 20
}
```

**响应格式**：

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "items": [],
    "total": 100,
    "page": 1,
    "page_size": 20,
    "total_pages": 5
  }
}
```

### 排序参数

支持排序的接口：

```json
{
  "order_by": "created_at",
  "order_direction": "desc"
}
```

- `order_by`: 排序字段
- `order_direction`: 排序方向（asc/desc）

### 筛选参数

支持筛选的接口：

```json
{
  "filters": {
    "status": "active",
    "category_id": 1,
    "keyword": "商品名称"
  }
}
```

## 认证授权

### JWT Token

系统使用 JWT Token 进行身份认证。

#### 获取 Token

**接口**: `POST /api/auth/login`

**请求**：

```json
{
  "username": "admin",
  "password": "password"
}
```

**响应**：

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200
  }
}
```

#### 刷新 Token

**接口**: `POST /api/auth/refresh`

**请求头**：

```http
Authorization: Bearer {refresh_token}
```

**响应**：

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200
  }
}
```

#### 使用 Token

在请求头中携带 Token：

```http
Authorization: Bearer {access_token}
```

### 权限验证

后台接口需要验证用户权限，权限不足时返回 403 错误。

## 错误处理

### 错误响应格式

```json
{
  "code": 400,
  "message": "参数错误",
  "errors": {
    "name": ["商品名称不能为空"],
    "price": ["价格必须大于0"]
  }
}
```

### 常见错误

| 错误码 | 错误信息 | 说明 |
|-------|---------|------|
| 1001 | Token 已过期 | 需要刷新 Token |
| 1002 | Token 无效 | 需要重新登录 |
| 1003 | 无权限访问 | 权限不足 |
| 2001 | 参数验证失败 | 请求参数错误 |
| 3001 | 资源不存在 | 请求的资源不存在 |
| 4001 | 库存不足 | 商品库存不足 |
| 5001 | 服务器错误 | 系统内部错误 |

## 接口限流

为保护系统稳定性，部分接口有访问频率限制：

- 普通接口：60 次/分钟
- 登录接口：5 次/分钟
- 支付接口：10 次/分钟

超过限制时返回 429 错误：

```json
{
  "code": 429,
  "message": "请求过于频繁，请稍后再试"
}
```

## 接口文档

详细的接口文档请查看：

- [后台接口文档](/api/admin)
- [前端接口文档](/api/frontend)
- [认证接口文档](/api/auth)

## Swagger 文档

系统提供 Swagger 在线文档，访问地址：

```
http://your-domain.com/swagger
```

## Postman 集合

可以导入 Postman 集合进行接口测试：

[下载 Postman 集合](./postman_collection.json)

## 下一步

- [后台接口](/api/admin) - 查看后台管理接口
- [前端接口](/api/frontend) - 查看前端接口
- [认证授权](/api/auth) - 了解认证授权机制
