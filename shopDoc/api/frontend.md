# 小程序 / 前端接口（API V1）

所有前端接口以 `/api/v1` 为前缀，供微信小程序、H5、App 等终端调用。写操作需登录（Bearer Token），部分读接口开放。

> 金额单位统一为**分（int）**，避免浮点精度问题。

## 登录

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| `POST` | `/api/v1/login/miniApp` | 微信小程序登录，传 `code`、可选 `encrypted_data`/`iv`/`openid` |

登录成功返回 JWT Token，后续请求通过 `Authorization: Bearer {token}` 鉴权。

## 首页

| 方法 | 路径 | 登录 | 说明 |
| ---- | ---- | ---- | ---- |
| `GET` | `/api/v1/home` | 否 | 首页聚合数据（轮播、推荐、公告等） |

## 商品 & 分类

| 方法 | 路径 | 登录 | 说明 |
| ---- | ---- | ---- | ---- |
| `GET` | `/api/v1/products` | 否 | 商品列表，支持 `page`/`page_size`/`category_id`/`keyword` 等筛选 |
| `GET` | `/api/v1/products/{id}` | 否 | 商品详情（含 SKU、规格、图片、快照缓存） |
| `GET` | `/api/v1/categories` | 否 | 分类树 |

## 优惠券

| 方法 | 路径 | 登录 | 说明 |
| ---- | ---- | ---- | ---- |
| `GET` | `/api/v1/coupons/available` | 否 | 可领取优惠券列表，支持 `spu_id`/`limit` |
| `GET` | `/api/v1/coupons/{id}` | 否 | 优惠券详情 |
| `GET` | `/api/v1/member/coupons` | 是 | 我的优惠券，`status` 筛选（可用/已用/已过期） |
| `POST` | `/api/v1/member/coupons/receive` | 是 | 领取优惠券，传 `coupon_id` |

## 购物车

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| `GET` | `/api/v1/cart` | 购物车列表（含商品快照、价格、库存状态） |
| `POST` | `/api/v1/cart/items` | 加入购物车 `{ "sku_id": 1, "quantity": 2 }` |
| `PUT` | `/api/v1/cart/items/{skuId}` | 更新数量 |
| `DELETE` | `/api/v1/cart/items/{skuId}` | 删除单项 |
| `POST` | `/api/v1/cart/clear-invalid` | 清理失效商品 |

所有购物车接口需登录。

## 订单

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| `POST` | `/api/v1/order/preview` | 预览订单（计算金额、运费、可用优惠券） |
| `POST` | `/api/v1/order/submit` | 提交订单 |
| `POST` | `/api/v1/order/payment` | 发起支付 |
| `GET` | `/api/v1/order/list` | 订单列表，`status` 筛选，分页 |
| `GET` | `/api/v1/order/detail/{orderNo}` | 订单详情 |
| `GET` | `/api/v1/order/statistics` | 各状态订单数量统计 |
| `POST` | `/api/v1/order/cancel` | 取消订单（待付款），传 `order_no` |
| `POST` | `/api/v1/order/confirm-receipt` | 确认收货（已发货），传 `order_no` |

所有订单接口需登录，且有限流保护（preview 60次/分，submit 30次/分）。

### 下单流程

```
预览 POST /api/v1/order/preview
  → 返回：商品金额、运费、优惠、合计、可用优惠券
  
提交 POST /api/v1/order/submit
  → 返回：order_no、pay_methods
  
支付 POST /api/v1/order/payment
  → 传入：order_no、pay_method (wechat/balance)
  → 微信支付返回 prepay_id 等参数；余额支付直接完成
```

预览/提交入参示例：

```json
{
  "items": [{ "sku_id": 1, "quantity": 1 }],
  "address_id": 3,
  "coupon_list": [{ "coupon_id": 8 }],
  "buyer_remark": "请周末送达"
}
```

## 会员

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| `GET` | `/api/v1/member/profile` | 个人信息（昵称、头像、等级等） |
| `GET` | `/api/v1/member/center` | 个人中心聚合（订单统计、优惠券数、客服电话） |
| `POST` | `/api/v1/member/phone/bind` | 微信手机号授权绑定，传 `code` |
| `POST` | `/api/v1/member/profile/authorize` | 头像昵称授权 |

## 收货地址

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| `GET` | `/api/v1/member/addresses` | 地址列表，支持 `limit` |
| `GET` | `/api/v1/member/addresses/{id}` | 地址详情 |
| `POST` | `/api/v1/member/addresses` | 新增地址 |
| `PUT` | `/api/v1/member/addresses/{id}` | 更新地址 |
| `DELETE` | `/api/v1/member/addresses/{id}` | 删除地址 |
| `POST` | `/api/v1/member/addresses/{id}/default` | 设为默认地址 |

## 文件上传

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| `POST` | `/api/v1/upload/image` | 上传图片（jpg/png/gif/webp，≤5MB），返回 `url` |

需登录，`multipart/form-data` 格式，字段名 `file`。

## 统一响应格式

```json
{
  "code": 200,
  "message": "操作成功",
  "data": { ... }
}
```

常见错误码：

| code | 含义 |
| ---- | ---- |
| `200` | 成功 |
| `400` | 参数错误 |
| `401` | 未登录 / Token 失效 |
| `403` | 无权限 |
| `404` | 资源不存在 |
| `500` | 服务器错误 |

## 接入建议

1. Token 失效时（401），调用登录接口重新获取，再重试请求
2. 金额字段均为**分（int）**，前端展示时除以 100
3. 支付结果以 `GET /api/v1/order/detail/{orderNo}` 查询为准，不依赖前端跳转回调
4. 地址提交使用 `region_path`（如 `|110000|110100|110101|`）保证地区一致性

更多后台接口参考 [Admin API](/api/admin)，认证细节见 [认证授权](/api/auth)。
