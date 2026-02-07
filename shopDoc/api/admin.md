# 后台管理接口（Admin API）

Base URL: `/admin`，所有接口（除登录外）需携带 `Authorization: Bearer {access_token}`。

Admin 端使用三层中间件: `AccessTokenMiddleware` → `PermissionMiddleware` → `OperationMiddleware`。

## 认证

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| POST | `/admin/passport/login` | 管理员登录 |
| POST | `/admin/passport/logout` | 退出登录 |
| GET | `/admin/passport/getInfo` | 获取当前用户信息 |
| POST | `/admin/passport/refresh` | 刷新 Token |

## 权限与个人设置

| 方法 | 路径 | 说明 |
| ---- | ---- | ---- |
| GET | `/admin/permission/menus` | 当前用户菜单树 |
| GET | `/admin/permission/roles` | 当前用户角色 |
| POST | `/admin/permission/update` | 更新个人信息/密码 |

## 用户管理 `/admin/user`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/user/list` | `permission:user:index` |
| POST | `/admin/user` | `permission:user:save` |
| PUT | `/admin/user/{userId}` | `permission:user:update` |
| PUT | `/admin/user/password` | `permission:user:password` |
| DELETE | `/admin/user` | `permission:user:delete` |
| GET | `/admin/user/{userId}/roles` | `permission:user:getRole` |
| PUT | `/admin/user/{userId}/roles` | `permission:user:setRole` |

## 角色管理 `/admin/role`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/role/list` | `permission:role:index` |
| POST | `/admin/role` | `permission:role:save` |
| PUT | `/admin/role/{id}` | `permission:role:update` |
| DELETE | `/admin/role` | `permission:role:delete` |
| GET | `/admin/role/{id}/permissions` | `permission:role:getMenu` |
| PUT | `/admin/role/{id}/permissions` | `permission:role:setMenu` |

## 菜单管理 `/admin/menu`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/menu/list` | `permission:menu:index` |
| POST | `/admin/menu` | `permission:menu:create` |
| PUT | `/admin/menu/{id}` | `permission:menu:save` |
| DELETE | `/admin/menu` | `permission:menu:delete` |

## 商品管理 `/admin/product/product`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/product/product/list` | `product:product:list` |
| GET | `/admin/product/product/stats` | `product:product:list` |
| GET | `/admin/product/product/{id}` | `product:product:read` |
| POST | `/admin/product/product` | `product:product:create` |
| PUT | `/admin/product/product/{id}` | `product:product:update` |
| DELETE | `/admin/product/product/{id}` | `product:product:delete` |
| PUT | `/admin/product/product/{id}/status` | `product:product:update` |
| PUT | `/admin/product/product/sort` | `product:product:update` |

## 分类管理 `/admin/product/category`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/product/category/list` | `product:category:list` |
| GET | `/admin/product/category/tree` | `product:category:list` |
| GET | `/admin/product/category/{id}` | `product:category:read` |
| POST | `/admin/product/category` | `product:category:create` |
| PUT | `/admin/product/category/{id}` | `product:category:update` |
| DELETE | `/admin/product/category/{id}` | `product:category:delete` |
| GET | `/admin/product/category/options` | `product:category:list` |
| GET | `/admin/product/category/statistics` | `product:category:list` |
| PUT | `/admin/product/category/sort` | `product:category:update` |
| PUT | `/admin/product/category/move` | `product:category:update` |
| GET | `/admin/product/category/{id}/breadcrumb` | `product:category:list` |

## 品牌管理 `/admin/product/brand`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/product/brand/list` | `product:brand:list` |
| GET | `/admin/product/brand/{id}` | `product:brand:read` |
| POST | `/admin/product/brand` | `product:brand:create` |
| PUT | `/admin/product/brand/{id}` | `product:brand:update` |
| DELETE | `/admin/product/brand/{id}` | `product:brand:delete` |
| GET | `/admin/product/brand/options` | `product:brand:list` |
| GET | `/admin/product/brand/statistics` | `product:brand:list` |
| PUT | `/admin/product/brand/sort` | `product:brand:update` |

## 订单管理 `/admin/order/order`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/order/order/list` | `order:order:list` |
| GET | `/admin/order/order/stats` | `order:order:list` |
| GET | `/admin/order/order/{id}` | `order:order:read` |
| PUT | `/admin/order/order/{id}/ship` | `order:order:update` |
| PUT | `/admin/order/order/{id}/cancel` | `order:order:update` |
| POST | `/admin/order/order/export` | `order:order:list` |

## 运费模板 `/admin/shipping/templates`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/shipping/templates/list` | `shipping:template:list` |
| GET | `/admin/shipping/templates/{id}` | `shipping:template:read` |
| POST | `/admin/shipping/templates` | `shipping:template:create` |
| PUT | `/admin/shipping/templates/{id}` | `shipping:template:update` |
| DELETE | `/admin/shipping/templates/{id}` | `shipping:template:delete` |

## 优惠券管理 `/admin/coupon`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/coupon/list` | `coupon:list` |
| GET | `/admin/coupon/stats` | `coupon:list` |
| GET | `/admin/coupon/{id}` | `coupon:read` |
| POST | `/admin/coupon` | `coupon:create` |
| PUT | `/admin/coupon/{id}` | `coupon:update` |
| DELETE | `/admin/coupon/{id}` | `coupon:delete` |
| PUT | `/admin/coupon/{id}/toggle-status` | `coupon:update` |
| POST | `/admin/coupon/{id}/issue` | `coupon:issue` |

## 优惠券领取记录 `/admin/coupon/user`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/coupon/user/list` | `coupon:user:list` |
| PUT | `/admin/coupon/user/{id}/mark-used` | `coupon:user:update` |
| PUT | `/admin/coupon/user/{id}/mark-expired` | `coupon:user:update` |

## 会员管理 `/admin/member/member`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/member/member/list` | `member:member:list` |
| GET | `/admin/member/member/stats` | `member:member:list` |
| GET | `/admin/member/member/overview` | `member:member:list` |
| GET | `/admin/member/member/{id}` | `member:member:read` |
| POST | `/admin/member/member` | `member:member:create` |
| PUT | `/admin/member/member/{id}` | `member:member:update` |
| PUT | `/admin/member/member/{id}/status` | `member:member:update` |
| PUT | `/admin/member/member/{id}/tags` | `member:member:tag` |

## 会员等级 `/admin/member/level`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/member/level/list` | `member:level:list` |
| GET | `/admin/member/level/{id}` | `member:level:read` |
| POST | `/admin/member/level` | `member:level:create` |
| PUT | `/admin/member/level/{id}` | `member:level:update` |
| DELETE | `/admin/member/level/{id}` | `member:level:delete` |

## 会员标签 `/admin/member/tag`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/member/tag/list` | `member:tag:list` |
| GET | `/admin/member/tag/options` | `member:member:list` |
| POST | `/admin/member/tag` | `member:tag:create` |
| PUT | `/admin/member/tag/{id}` | `member:tag:update` |
| DELETE | `/admin/member/tag/{id}` | `member:tag:delete` |

## 会员钱包 `/admin/member/account`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/member/account/wallet/logs` | `member:wallet:list` |
| POST | `/admin/member/account/wallet/adjust` | `member:wallet:adjust` |

## 秒杀活动 `/admin/seckill/activity`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/seckill/activity/list` | `seckill:activity:list` |
| GET | `/admin/seckill/activity/stats` | `seckill:activity:list` |
| GET | `/admin/seckill/activity/{id}` | `seckill:activity:read` |
| POST | `/admin/seckill/activity` | `seckill:activity:create` |
| PUT | `/admin/seckill/activity/{id}` | `seckill:activity:update` |
| DELETE | `/admin/seckill/activity/{id}` | `seckill:activity:delete` |
| PUT | `/admin/seckill/activity/{id}/toggle-status` | `seckill:activity:update` |

## 拼团活动 `/admin/group-buy`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/group-buy/list` | `promotion:group_buy:list` |
| GET | `/admin/group-buy/stats` | `promotion:group_buy:list` |
| GET | `/admin/group-buy/{id}` | `promotion:group_buy:read` |
| POST | `/admin/group-buy` | `promotion:group_buy:create` |
| PUT | `/admin/group-buy/{id}` | `promotion:group_buy:update` |
| DELETE | `/admin/group-buy/{id}` | `promotion:group_buy:delete` |
| PUT | `/admin/group-buy/{id}/toggle-status` | `promotion:group_buy:update` |

## 系统配置 `/admin/system/setting`

| 方法 | 路径 | 权限码 |
| ---- | ---- | ------ |
| GET | `/admin/system/setting/groups` | `system:setting:list` |
| GET | `/admin/system/setting/group/{group}` | `system:setting:list` |
| PUT | `/admin/system/setting/{key}` | `system:setting:update` |
