# 认证授权

## Token 机制

Mine Shop 使用 JWT 双 Token 机制:

| 类型 | 用途 | 说明 |
| ---- | ---- | ---- |
| Access Token | 调用受保护接口 | `Authorization: Bearer <token>` |
| Refresh Token | 刷新 Access Token | 仅用于 refresh 接口 |

## Admin 登录

```http
POST /admin/passport/login
Content-Type: application/json

{ "username": "admin", "password": "Pass@123" }
```

```json
{
  "code": 200,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expire_at": 7200
  }
}
```

相关接口:

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| POST | `/admin/passport/login` | 账号密码登录 |
| POST | `/admin/passport/logout` | 登出（Token 加入黑名单） |
| GET | `/admin/passport/getInfo` | 获取当前用户信息 |
| POST | `/admin/passport/refresh` | 刷新 Token（需 Refresh Token） |

## 小程序登录

```http
POST /api/v1/login/miniApp
Content-Type: application/json

{
  "code": "wx.login() 返回的 code",
  "encrypted_data": "加密数据 (可选)",
  "iv": "加密向量 (可选)",
  "openid": "openid (可选，静默登录)"
}
```

## Token 刷新流程

1. Access Token 过期时，后端返回 `401`
2. 客户端携带 Refresh Token 调用 `POST /admin/passport/refresh`
3. 服务端将旧 Token 加入黑名单，返回新的 Token 对
4. 登出时 `POST /admin/passport/logout` 立即失效当前 Token

## 认证实现

认证流程由 `DomainAuthService` 处理:

```
LoginInput (Contract)
  → DomainAuthService::login()
    → UserRepository::findByUnameType()  # 查找用户
    → UserEntity::verifyPassword()       # 密码校验
    → DomainTokenService::buildAccessToken()  # 生成 Token
    → DomainTokenService::buildRefreshToken()
    → 返回 TokenPair
```

Token 黑名单机制确保登出和刷新后旧 Token 立即失效。

## RBAC 权限模型

```
用户 → 角色 → 权限（菜单 + 按钮）
```

- 角色绑定菜单权限，Controller 使用 `#[Permission(code: 'xxx')]` 注解声明权限码
- `PermissionMiddleware` 校验当前用户是否拥有对应权限码
- 超级管理员跳过权限检查

## 数据权限

支持四档数据范围:
- 全部数据
- 本部门及下级
- 本部门
- 仅本人

通过 `DataScope` Attribute + AOP 在查询时自动注入过滤条件。

## 错误响应

```json
{ "code": 401, "message": "Token 已过期" }
```

```json
{ "code": 403, "message": "无权限访问" }
```

```json
{ "code": 423, "message": "账户已禁用" }
```
