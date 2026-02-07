# 小程序 API (V1)

Base URL: `/api/v1`

除登录和公开接口外，需携带 `Authorization: Bearer {token}` 请求头。

## 认证

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| POST | `/api/v1/login/miniApp` | 微信小程序登录 | 否 |

请求参数:
```json
{
  "code": "wx.login() 返回的 code",
  "encrypted_data": "加密数据 (可选)",
  "iv": "加密向量 (可选)",
  "openid": "openid (可选，静默登录)"
}
```

## 首页

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/v1/home` | 首页数据 | 否 |

## 商品

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/v1/products` | 商品列表 | 否 |
| GET | `/api/v1/products/{id}` | 商品详情 | 否 |

查询参数: `page`, `page_size`, `category_id`, `keyword`, `sort_field`, `sort_order`

## 分类

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/v1/categories` | 分类树 | 否 |

## 购物车

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/v1/cart` | 购物车列表 | 是 |
| POST | `/api/v1/cart/items` | 加入购物车 | 是 |
| PUT | `/api/v1/cart/items/{skuId}` | 更新数量 | 是 |
| DELETE | `/api/v1/cart/items/{skuId}` | 删除商品 | 是 |
| POST | `/api/v1/cart/clear-invalid` | 清理失效商品 | 是 |

## 订单

| 方法 | 路径 | 说明 | 认证 | 限流 |
|------|------|------|------|------|
| POST | `/api/v1/order/preview` | 订单预览 | 是 | 60次/20容量 |
| POST | `/api/v1/order/submit` | 提交订单 | 是 | 30次/10容量 |
| POST | `/api/v1/order/payment` | 订单支付 | 是 | - |
| GET | `/api/v1/order/list` | 订单列表 | 是 | - |
| GET | `/api/v1/order/detail/{orderNo}` | 订单详情 | 是 | - |
| GET | `/api/v1/order/statistics` | 订单统计 | 是 | - |
| POST | `/api/v1/order/cancel` | 取消订单 | 是 | - |
| POST | `/api/v1/order/confirm-receipt` | 确认收货 | 是 | - |

### 订单预览请求

```json
{
  "order_type": "normal",
  "goods_list": [
    { "sku_id": 1, "quantity": 2 }
  ],
  "address_id": 10,
  "coupon_list": [
    { "coupon_id": 5 }
  ]
}
```

### 提交订单请求

```json
{
  "order_type": "normal",
  "goods_list": [
    { "sku_id": 1, "quantity": 2 }
  ],
  "address_id": 10,
  "buyer_remark": "请尽快发货",
  "coupon_list": [
    { "coupon_id": 5 }
  ],
  "total_amount": 9900
}
```

### 订单支付请求

```json
{
  "order_no": "202501010001",
  "pay_method": "wechat"
}
```

## 会员

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/v1/member/profile` | 个人资料 | 是 |
| GET | `/api/v1/member/center` | 个人中心 | 是 |
| POST | `/api/v1/member/phone/bind` | 绑定手机号 | 是 |
| POST | `/api/v1/member/profile/authorize` | 授权头像昵称 | 是 |

## 收货地址

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/v1/member/addresses` | 地址列表 | 是 |
| GET | `/api/v1/member/addresses/{id}` | 地址详情 | 是 |
| POST | `/api/v1/member/addresses` | 新增地址 | 是 |
| PUT | `/api/v1/member/addresses/{id}` | 更新地址 | 是 |
| DELETE | `/api/v1/member/addresses/{id}` | 删除地址 | 是 |
| POST | `/api/v1/member/addresses/{id}/default` | 设为默认 | 是 |

## 优惠券

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/v1/coupons/available` | 可领取优惠券 | 否 |
| GET | `/api/v1/coupons/{id}` | 优惠券详情 | 否 |
| GET | `/api/v1/member/coupons` | 我的优惠券 | 是 |
| POST | `/api/v1/member/coupons/receive` | 领取优惠券 | 是 |

## 文件上传

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| POST | `/api/v1/upload/image` | 上传图片 | 是 |

限制: jpg/png/gif/webp，最大 5MB，`multipart/form-data` 格式，字段名 `file`。
