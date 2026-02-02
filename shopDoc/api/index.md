# API 概览

Mine Shop 提供一套 RESTful API，覆盖后台管理端（Admin）、前台业务端（Frontend）以及 Geo、认证等通用能力。本页汇总协议规范、通用约束与典型示例。

## 基础信息

| 分类 | Base Path | 说明 |
| ---- | --------- | ---- |
| Admin API | `/admin` | 后台管理、配置、运营、Geo 控制台等接口 |
| Frontend API | `/api` | 对 C 端/小程序/APP 开放的业务接口 |
| Geo API | `/geo` | PCAS 级联、关键字搜索等只读接口 |
| Auth API | `/api/auth` & `/admin/auth` | JWT 登录、刷新与登出 |

所有接口默认返回 JSON，使用 UTF-8 编码。

## 认证与权限

- **JWT**：后台与前台均使用 JWT，携带在 `Authorization: Bearer <token>`。
- **刷新机制**：`/api/auth/refresh` / `/admin/auth/refresh` 提供刷新令牌能力。
- **权限控制**：Admin API 采用 RBAC + 数据权限（部门/本人）。权限不足返回 `403`。

详细流程参考 [认证授权](/api/auth)。

## 请求/响应格式

```http
POST /admin/member/member/list HTTP/1.1
Content-Type: application/json
Authorization: Bearer <token>

{
  "page": 1,
  "page_size": 20,
  "keyword": "手机"
}
```

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [...],
    "total": 128
  }
}
```

| 字段 | 含义 |
| ---- | ---- |
| `code` | 业务状态码（200 成功；其余见下文） |
| `message` | 人类可读提示 |
| `data` | 真实业务数据；失败时可为空 |

## 常见状态码

| code | 描述 |
| ---- | ---- |
| 200 | 成功 |
| 201 | 创建成功/异步接受 |
| 400 | 参数错误或业务校验失败 |
| 401 | 未认证 / Token 失效 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 表单验证失败（附带字段错误） |
| 429 | 请求过于频繁 |
| 500 | 服务端异常 |

## 分页 & 筛选

- 通用参数：`page`（默认 1）、`page_size`（默认 20，最大 200）。
- 响应字段：`list`/`items`、`total`、`page`、`page_size`。
- 复杂筛选字段随资源不同而异（例如会员列表支持等级/标签/地区；订单支持状态/时间区间等）。

## 限流与幂等

- 登录、支付等敏感接口配置了速率限制，如收到 `429` 请退避重试。
- 幂等场景需自带业务幂等字段（例如订单号、第三方流水号）。

## Geo API 说明

| 接口 | 描述 |
| ---- | ---- |
| `GET /geo/pcas` | 获取最新版本的省-市-区-街四级联动树，含版本号与更新时间 |
| `GET /geo/search?keyword=杭州&limit=20` | 基于关键字的模糊匹配，返回 `code`、`level`、`full_name`、`path_codes` |

Geo 数据来自自建 `geo_regions` 表，支持 `mall:sync-regions` 命令或 Crontab 定时同步。

## 调用示例

```bash
curl -H "Authorization: Bearer $TOKEN" \
     -X GET "https://api.example.com/admin/member/member/overview?trend_days=14"
```

```json
{
  "code": 200,
  "data": {
    "trend": { "labels": ["01-20", ...], "new_members": [32, ...], "active_members": [58, ...] },
    "region_breakdown": [
      { "key": "广东省", "label": "广东省", "value": 1024 },
      { "key": "未填写地区", "label": "未填写地区", "value": 210 }
    ],
    "level_breakdown": [...]
  }
}
```

## 下一步

- [后台接口](/api/admin) – 商品、订单、营销、会员、Geo 管理等所有 Admin 端接口。
- [前端接口](/api/frontend) – 面向 C 端/小程序的下单、支付、地址、钱包接口。
- [认证授权](/api/auth) – Token、刷新、权限、数据权限等说明。*** End Patch
