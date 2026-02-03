# 前端接口（Frontend API）

前端接口以 `/api` 为前缀，供 B2C 网站、小程序、App 等终端调用。部分接口需登录（Bearer Token），其余为开放读取。

## 认证与会员

| 功能 | 方法 & 路径 | 说明 |
| ---- | ----------- | ---- |
| 手机号注册 | `POST /api/user/register` | `mobile` + `password` + 验证码 |
| 登录 | `POST /api/user/login` | 支持手机号/密码或第三方登录（可扩展） |
| 获取个人信息 | `GET /api/user/profile` | 返回昵称、头像、等级、余额、积分等 |
| 更新信息 | `PUT /api/user/profile` | 支持头像、昵称、生日等 |
| 刷新 Token / 登出 | 同 Admin，参见 [认证授权](/api/auth) |

## 商品 & 内容

| 功能 | 方法 & 路径 | 关键参数 |
| ---- | ----------- | -------- |
| 商品列表 | `GET /api/product/list` | `page`, `category_id`, `keyword`, `sort` |
| 商品详情 | `GET /api/product/{id}` | 含图片、详情、SKU、规格、营销标签 |
| 团购/秒杀列表 | `GET /api/group-buy/list`, `GET /api/seckill/session/{id}/products` | 返回实时库存与倒计时 |
| 优惠券可领取列表 | `GET /api/v1/coupons/available` | `spu_id`、`limit` 查询可领取优惠券 |
| 优惠券详情 | `GET /api/v1/coupons/{id}` | 返回单券基础信息、规则、可领状态 |
| 领取优惠券 | `POST /api/coupon/claim` | 登录后调用，传 `coupon_id` |

## 购物车与下单

| 功能 | 方法 & 路径 |
| ---- | ----------- |
| 添加购物车 | `POST /api/cart/add` `{ "sku_id": 1, "quantity": 2 }` |
| 购物车列表 | `GET /api/cart/list` |
| 更新数量 | `PUT /api/cart/{id}` |
| 删除/批量删除 | `DELETE /api/cart/{id}` 或 `POST /api/cart/batch-delete` |

### 下单流程

1. **预览订单**：`POST /api/order/preview`
   ```json
   {
     "items": [{ "sku_id": 1, "quantity": 1 }],
     "address_id": 3,
     "coupon_ids": [8],
     "remark": "请周末送达"
   }
   ```
   返回：商品金额、运费、优惠、合计、可用优惠券列表、预计发货时间等。

2. **提交订单**：`POST /api/order/submit`
   - 需登录。
   - 支持普通订单/团购/秒杀，在 `order_type` 或 SKU 上标记。
   - 返回 `order_id`、`order_no`、`pay_amount`。

3. **订单列表/详情**：`GET /api/order/list`、`GET /api/order/{id}`。
   - 列表支持按状态过滤（`pending`, `paid`, `shipped`, `completed`, `cancelled`）。
   - 详情包含订单项、地址、物流信息、支付状态、倒计时等。

4. **订单操作**：
   - 取消：`POST /api/order/{id}/cancel`
   - 确认收货：`POST /api/order/{id}/confirm`
   - 查看物流：`GET /api/order/{id}/express` *(如已对接)*

## 支付

| 功能 | 方法 & 路径 | 说明 |
| ---- | ----------- | ---- |
| 创建支付 | `POST /api/payment/create` | `{ "order_id": 1, "payment_method": "alipay" }` |
| 查询支付状态 | `GET /api/payment/query/{order_id}` | 轮询或前端触发 |
| 支持方式 | `alipay` / `wechat` / `balance`（根据 `config/autoload/pay.php` 决定） |

回调地址示例：

- `POST /api/payment/alipay/notify`
- `POST /api/payment/wechat/notify`

## 会员资产

| 功能 | 方法 & 路径 | 说明 |
| ---- | ----------- | ---- |
| 钱包流水 | `GET /api/wallet/logs?type=balance` | 余额/积分通用 |
| 钱包概览 | `GET /api/wallet/summary` | 余额、冻结、累计充值/消费 |
| 可用优惠券 | `GET /api/coupon/my` | 支持筛选状态（可用/已用/已过期） |
| 地址管理 | `GET/POST/PUT/DELETE /api/address` | 字段含 `province`、`city`、`district`、`street`、`region_path` |
| 设置默认地址 | `POST /api/address/{id}/default` | |

地址表单数据可通过 `/geo/pcas`、`/geo/search`（无需登录）获取，保证四级地区一致性。

## Geo 与公共接口

| 接口 | 是否需登录 | 描述 |
| ---- | ---------- | ---- |
| `GET /geo/pcas` | 否 | 返回最新版本的四级联动树 `items[]` 与 `version` |
| `GET /geo/search?keyword=杭州&limit=10` | 否 | 模糊匹配省、市、区、街，带 `level` 和 `path_codes` |
| `GET /common/setting` | 否 | 获取商城基础设置（LOGO、客服、公告等） |

## 错误示例

```json
{
  "code": 400,
  "message": "库存不足",
  "data": { "sku_id": 10086 }
}
```

```json
{
  "code": 401,
  "message": "请先登录",
  "data": null
}
```

## 最佳实践

1. **Token 续期**：在 `401` 且 message 为 Token 失效时，调用 `/api/auth/refresh` 获取新 Token，再重试一次。
2. **地区字段**：提交地址/会员资料时使用 `region_path = |省code|市code|区code|街code|`，便于后台地区统计。
3. **幂等下单**：前端可传 `client_request_id`（定制）放入扩展字段，后端按需做幂等校验。
4. **网络错误**：支付状态必须以查询接口为准，不要依赖前端跳转。
5. **灰度发布**：可在 Header 中携带 `X-Client-Version`，后端根据版本控制返回字段或行为（如启用新的运营位）。

更多后台能力参考 [Admin API](/api/admin)，认证细节见 [认证授权](/api/auth)。*** End Patch
