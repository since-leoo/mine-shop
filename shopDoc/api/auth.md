# 认证授权

Mine Shop 的 Admin / Frontend API 均使用 **JWT（JSON Web Token）** 进行认证与授权。本页说明 Token 结构、发放流程、刷新策略以及 RBAC/数据权限实现。

## Token 类型

| 类型 | 用途 | 默认有效期 | 备注 |
| ---- | ---- | ---------- | ---- |
| Access Token | 调用受保护接口 | 2 小时 | 放在 `Authorization: Bearer <token>` |
| Refresh Token | 刷新 Access Token | 7 天 | 仅用于 `POST /auth/refresh` |

Access/Refresh Token 均采用 HS256/JWT，负载含 `sub`（用户 ID）、`client`（admin/api）、`exp` 等字段。

## 登录流程

```
客户端 → 提交账号凭证
        → 服务端验证用户名/密码/验证码
        → 生成 Access + Refresh Token
        → 返回 Token + 用户信息
客户端 → 存储 Token（HttpOnly Cookie 或 Local Storage）
```

### Admin 登录示例

```http
POST /admin/auth/login
Content-Type: application/json

{ "username": "admin", "password": "Pass@123" }
```

```json
{
  "code": 200,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200,
    "user": {
      "id": 1,
      "username": "admin",
      "nickname": "超级管理员",
      "roles": ["super_admin"]
    }
  }
}
```

前端登录接口路径相同，前缀为 `/api/auth/login`。

## 刷新与登出

1. Access Token 过期时，后端返回 `401` + `Token 已过期`。
2. 客户端需携带 Refresh Token 调用 `POST /{prefix}/auth/refresh`（prefix 为 `api` 或 `admin`）。

```http
POST /api/auth/refresh
Authorization: Bearer <refresh_token>
```

```json
{
  "code": 200,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200
  }
}
```

3. 登出：`POST /{prefix}/auth/logout`，服务端会将 Token 加入黑名单或调整版本号，立即失效。

## 权限模型

### RBAC

```
用户 ──► 角色 ──► 权限（菜单 + 按钮）
```

- 角色绑定权限与菜单。
- Controller 使用 `PermissionMiddleware` 读取当前路由对应的权限标识，若用户缺少即返回 `403`。
- 菜单/权限配置通过 Admin 控制台维护。

### 数据权限

- 支持「全部 / 本部门及下级 / 本部门 / 仅本人」四档。
- 在 Repository 查询时注入 `WHERE dept_id IN (...)` 或 `user_id = ...` 条件。
- 实现方式：`DataScope` Attribute + AOP，透明注入。

## 常见错误响应

```json
{
  "code": 401,
  "message": "Token 已过期"
}
```

```json
{
  "code": 403,
  "message": "无权限访问",
  "data": { "permission": "member:list" }
}
```

## 安全提示

1. **HTTPS**：所有环境应通过 HTTPS 传输 Token。
2. **存储**：推荐使用 HttpOnly Cookie 或安全存储封装，避免 XSS 窃取 Token。
3. **刷新**：前端维护刷新状态，避免同一 Refresh Token 在多个终端重复使用。
4. **强制下线**：后台支持禁用用户后使其 Token 失效（通过黑名单或版本戳）。
5. **多端管理**：如需多端登录限制，可在 Token Claim 中记录 `device_id` 并做校验。

了解认证后，可继续查看 [Admin API](/api/admin) 与 [Frontend API](/api/frontend) 的具体业务接口。*** End Patch
