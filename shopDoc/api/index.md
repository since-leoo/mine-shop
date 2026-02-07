# API 概览

Mine Shop 提供 RESTful API，覆盖后台管理端（Admin）和小程序端（API V1）。

## 基础信息

| 分类 | Base Path | 说明 |
| ---- | --------- | ---- |
| Admin API | `/admin` | 后台管理、商品、订单、营销、会员、权限、系统配置 |
| 小程序 API | `/api/v1` | 微信小程序端业务接口 |

所有接口默认返回 JSON，使用 UTF-8 编码。

## 认证

- Admin 端使用 JWT，携带 `Authorization: Bearer {access_token}`
- 小程序端同样使用 JWT，通过微信 `code` 换取 Token
- Admin 登录入口: `POST /admin/passport/login`
- 小程序登录入口: `POST /api/v1/login/miniApp`
- Token 刷新: `POST /admin/passport/refresh`（需 Refresh Token）

详细流程参考 [认证授权](/api/auth)。

## 请求/响应格式

```json
// 请求
POST /admin/product/product
Content-Type: application/json
Authorization: Bearer <token>

// 成功响应
{
  "code": 200,
  "message": "success",
  "data": { ... }
}

// 分页响应
{
  "code": 200,
  "data": {
    "list": [...],
    "total": 128
  }
}
```

## 常见状态码

| code | 描述 |
| ---- | ---- |
| 200 | 成功 |
| 201 | 创建成功 |
| 401 | 未认证 / Token 失效 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 表单验证失败 |
| 423 | 账户已禁用 |
| 500 | 服务端异常 |

## 分页参数

通用参数: `page`（默认 1）、`page_size`（默认 20）。

## 限流

订单预览和提交接口配置了速率限制:
- 预览: 60 次/20 容量
- 提交: 30 次/10 容量

## 字段命名规范

- Request 入参统一使用 `snake_case`
- Transformer 输出使用 `camelCase`（前端展示用）
- 后端字段名为唯一权威标准

## 下一步

- [后台接口](/api/admin) — 商品、订单、营销、会员等 Admin 端接口
- [小程序接口](/api/frontend) — 面向微信小程序的业务接口
- [认证授权](/api/auth) — Token、刷新、RBAC 权限说明
- [Admin API 速查](/admin-api) — 完整路由表（含权限码）
- [小程序 API 速查](/mini-api) — 完整路由表（含请求示例）
