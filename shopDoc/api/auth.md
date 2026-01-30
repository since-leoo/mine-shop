# 认证授权

本系统使用 JWT (JSON Web Token) 进行身份认证和授权。

## JWT Token

### Token 结构

JWT Token 由三部分组成：

```
Header.Payload.Signature
```

示例：

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIiwiaWF0IjoxNjQwOTk1MjAwLCJleHAiOjE2NDEwMDE2MDB9.xxx
```

### Token 类型

系统使用两种 Token：

1. **Access Token**: 用于访问 API，有效期 2 小时
2. **Refresh Token**: 用于刷新 Access Token，有效期 7 天

## 认证流程

### 1. 用户登录

```
用户提交用户名和密码
    ↓
服务器验证用户名和密码
    ↓
生成 Access Token 和 Refresh Token
    ↓
返回 Token 给客户端
```

**接口**: `POST /api/auth/login`

**请求**:

```json
{
  "username": "user@example.com",
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
    "expires_in": 7200,
    "user": {
      "id": 1,
      "username": "user@example.com",
      "nickname": "用户昵称"
    }
  }
}
```

### 2. 使用 Token 访问 API

在请求头中携带 Access Token：

```http
GET /api/user/profile HTTP/1.1
Host: api.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

### 3. Token 过期处理

当 Access Token 过期时，服务器返回 401 错误：

```json
{
  "code": 401,
  "message": "Token 已过期"
}
```

客户端需要使用 Refresh Token 刷新 Access Token。

### 4. 刷新 Token

**接口**: `POST /api/auth/refresh`

**请求头**:

```http
Authorization: Bearer {refresh_token}
```

**响应**:

```json
{
  "code": 200,
  "message": "刷新成功",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 7200
  }
}
```

### 5. 退出登录

**接口**: `POST /api/auth/logout`

**请求头**:

```http
Authorization: Bearer {access_token}
```

**响应**:

```json
{
  "code": 200,
  "message": "退出成功"
}
```

## 权限验证

### RBAC 权限模型

系统采用 RBAC (Role-Based Access Control) 权限模型：

```
用户 (User) → 角色 (Role) → 权限 (Permission)
```

### 权限检查流程

```
1. 从 Token 中获取用户 ID
2. 查询用户的角色
3. 查询角色的权限
4. 检查是否有访问当前接口的权限
5. 有权限则继续，无权限则返回 403
```

### 权限不足响应

```json
{
  "code": 403,
  "message": "无权限访问"
}
```

## 数据权限

### 部门数据权限

系统支持基于部门的数据权限：

- **全部数据**: 可以查看所有数据
- **本部门及下级部门**: 可以查看本部门和下级部门的数据
- **本部门**: 只能查看本部门的数据
- **仅本人**: 只能查看自己的数据

### 实现方式

在查询时自动添加数据权限过滤条件：

```php
// 自动添加部门过滤
$query->where(function ($query) use ($user) {
    if ($user->dataScope === 'all') {
        // 全部数据，不添加过滤
    } elseif ($user->dataScope === 'dept_and_child') {
        // 本部门及下级部门
        $deptIds = $this->getDeptAndChildIds($user->deptId);
        $query->whereIn('dept_id', $deptIds);
    } elseif ($user->dataScope === 'dept') {
        // 本部门
        $query->where('dept_id', $user->deptId);
    } elseif ($user->dataScope === 'self') {
        // 仅本人
        $query->where('user_id', $user->id);
    }
});
```

## 安全建议

### 1. Token 存储

- **前端**: 存储在 localStorage 或 sessionStorage
- **移动端**: 存储在安全的本地存储中
- **不要**: 存储在 Cookie 中（容易受到 CSRF 攻击）

### 2. Token 传输

- 始终使用 HTTPS
- 在请求头中传输，不要在 URL 中传输

### 3. Token 刷新

- Access Token 过期后使用 Refresh Token 刷新
- Refresh Token 也过期后需要重新登录

### 4. Token 撤销

- 退出登录时将 Token 加入黑名单
- 定期清理过期的黑名单记录

### 5. 密码安全

- 使用强密码策略
- 密码使用 bcrypt 或 argon2 加密
- 限制登录失败次数

## 错误码

| 错误码 | 说明 |
|-------|------|
| 1001 | Token 已过期 |
| 1002 | Token 无效 |
| 1003 | Token 已被撤销 |
| 1004 | 用户名或密码错误 |
| 1005 | 账号已被禁用 |
| 1006 | 无权限访问 |

## 示例代码

### JavaScript

```javascript
// 登录
async function login(username, password) {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ username, password })
  });
  
  const data = await response.json();
  
  if (data.code === 200) {
    // 保存 Token
    localStorage.setItem('access_token', data.data.access_token);
    localStorage.setItem('refresh_token', data.data.refresh_token);
  }
  
  return data;
}

// 请求 API
async function fetchAPI(url, options = {}) {
  const token = localStorage.getItem('access_token');
  
  const response = await fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  
  // Token 过期，刷新 Token
  if (data.code === 1001) {
    await refreshToken();
    return fetchAPI(url, options);
  }
  
  return data;
}

// 刷新 Token
async function refreshToken() {
  const refreshToken = localStorage.getItem('refresh_token');
  
  const response = await fetch('/api/auth/refresh', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${refreshToken}`
    }
  });
  
  const data = await response.json();
  
  if (data.code === 200) {
    localStorage.setItem('access_token', data.data.access_token);
  } else {
    // Refresh Token 也过期，跳转到登录页
    window.location.href = '/login';
  }
}

// 退出登录
async function logout() {
  const token = localStorage.getItem('access_token');
  
  await fetch('/api/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  localStorage.removeItem('access_token');
  localStorage.removeItem('refresh_token');
  
  window.location.href = '/login';
}
```

## 下一步

- [API 概览](/api/) - 查看 API 接口文档
- [后台接口](/api/admin) - 查看后台管理接口
