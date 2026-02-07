# 后台管理 API

Base URL: `/admin`

所有接口（除登录外）需携带 `Authorization: Bearer {access_token}` 请求头。

## 认证

| 方法 | 路径 | 说明 | 权限 |
|------|------|------|------|
| POST | `/admin/passport/login` | 管理员登录 | 公开 |
| POST | `/admin/passport/logout` | 退出登录 | 登录 |
| GET | `/admin/passport/getInfo` | 获取当前用户信息 | 登录 |
| POST | `/admin/passport/refresh` | 刷新 Token | RefreshToken |

## 权限管理

| 方法 | 路径 | 说明 | 权限 |
|------|------|------|------|
| GET | `/admin/permission/menus` | 当前用户菜单 | 登录 |
| GET | `/admin/permission/roles` | 当前用户角色 | 登录 |
| POST | `/admin/permission/update` | 更新个人信息/密码 | 登录 |

## 用户管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/user/list` | 用户列表 | `permission:user:index` |
| POST | `/admin/user` | 创建用户 | `permission:user:save` |
| PUT | `/admin/user/{userId}` | 更新用户 | `permission:user:update` |
| PUT | `/admin/user` | 更新当前用户 | `permission:user:update` |
| PUT | `/admin/user/password` | 重置密码 | `permission:user:password` |
| DELETE | `/admin/user` | 批量删除用户 | `permission:user:delete` |
| GET | `/admin/user/{userId}/roles` | 用户角色列表 | `permission:user:getRole` |
| PUT | `/admin/user/{userId}/roles` | 分配用户角色 | `permission:user:setRole` |

## 角色管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/role/list` | 角色列表 | `permission:role:index` |
| POST | `/admin/role` | 创建角色 | `permission:role:save` |
| PUT | `/admin/role/{id}` | 更新角色 | `permission:role:update` |
| DELETE | `/admin/role` | 批量删除角色 | `permission:role:delete` |
| GET | `/admin/role/{id}/permissions` | 角色权限列表 | `permission:role:getMenu` |
| PUT | `/admin/role/{id}/permissions` | 分配角色权限 | `permission:role:setMenu` |

## 菜单管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/menu/list` | 菜单树 | `permission:menu:index` |
| POST | `/admin/menu` | 创建菜单 | `permission:menu:create` |
| PUT | `/admin/menu/{id}` | 更新菜单 | `permission:menu:save` |
| DELETE | `/admin/menu` | 批量删除菜单 | `permission:menu:delete` |

## 商品管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/product/product/list` | 商品列表 | `product:product:list` |
| GET | `/admin/product/product/stats` | 商品统计 | `product:product:list` |
| GET | `/admin/product/product/{id}` | 商品详情 | `product:product:read` |
| POST | `/admin/product/product` | 创建商品 | `product:product:create` |
| PUT | `/admin/product/product/{id}` | 更新商品 | `product:product:update` |
| DELETE | `/admin/product/product/{id}` | 删除商品 | `product:product:delete` |
| PUT | `/admin/product/product/{id}/status` | 更新商品状态 | `product:product:update` |
| PUT | `/admin/product/product/sort` | 批量排序 | `product:product:update` |

## 分类管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/product/category/list` | 分类列表 | `product:category:list` |
| GET | `/admin/product/category/tree` | 分类树 | `product:category:list` |
| GET | `/admin/product/category/{id}` | 分类详情 | `product:category:read` |
| POST | `/admin/product/category` | 创建分类 | `product:category:create` |
| PUT | `/admin/product/category/{id}` | 更新分类 | `product:category:update` |
| DELETE | `/admin/product/category/{id}` | 删除分类 | `product:category:delete` |
| GET | `/admin/product/category/options` | 分类选项 | `product:category:list` |
| GET | `/admin/product/category/statistics` | 分类统计 | `product:category:list` |
| PUT | `/admin/product/category/sort` | 批量排序 | `product:category:update` |
| PUT | `/admin/product/category/move` | 移动分类 | `product:category:update` |
| GET | `/admin/product/category/{id}/breadcrumb` | 面包屑 | `product:category:list` |

## 品牌管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/product/brand/list` | 品牌列表 | `product:brand:list` |
| GET | `/admin/product/brand/{id}` | 品牌详情 | `product:brand:read` |
| POST | `/admin/product/brand` | 创建品牌 | `product:brand:create` |
| PUT | `/admin/product/brand/{id}` | 更新品牌 | `product:brand:update` |
| DELETE | `/admin/product/brand/{id}` | 删除品牌 | `product:brand:delete` |
| GET | `/admin/product/brand/options` | 品牌选项 | `product:brand:list` |
| GET | `/admin/product/brand/statistics` | 品牌统计 | `product:brand:list` |
| PUT | `/admin/product/brand/sort` | 批量排序 | `product:brand:update` |

## 订单管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/order/order/list` | 订单列表 | `order:order:list` |
| GET | `/admin/order/order/stats` | 订单统计 | `order:order:list` |
| GET | `/admin/order/order/{id}` | 订单详情 | `order:order:read` |
| PUT | `/admin/order/order/{id}/ship` | 发货 | `order:order:update` |
| PUT | `/admin/order/order/{id}/cancel` | 取消订单 | `order:order:update` |
| POST | `/admin/order/order/export` | 导出订单 | `order:order:list` |

## 运费模板

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/shipping/templates/list` | 模板列表 | `shipping:template:list` |
| GET | `/admin/shipping/templates/{id}` | 模板详情 | `shipping:template:read` |
| POST | `/admin/shipping/templates` | 创建模板 | `shipping:template:create` |
| PUT | `/admin/shipping/templates/{id}` | 更新模板 | `shipping:template:update` |
| DELETE | `/admin/shipping/templates/{id}` | 删除模板 | `shipping:template:delete` |

## 优惠券管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/coupon/list` | 优惠券列表 | `coupon:list` |
| GET | `/admin/coupon/stats` | 优惠券统计 | `coupon:list` |
| GET | `/admin/coupon/{id}` | 优惠券详情 | `coupon:read` |
| POST | `/admin/coupon` | 创建优惠券 | `coupon:create` |
| PUT | `/admin/coupon/{id}` | 更新优惠券 | `coupon:update` |
| DELETE | `/admin/coupon/{id}` | 删除优惠券 | `coupon:delete` |
| PUT | `/admin/coupon/{id}/toggle-status` | 切换状态 | `coupon:update` |
| POST | `/admin/coupon/{id}/issue` | 发放优惠券 | `coupon:issue` |

## 优惠券领取记录

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/coupon/user/list` | 领取记录列表 | `coupon:user:list` |
| PUT | `/admin/coupon/user/{id}/mark-used` | 标记已使用 | `coupon:user:update` |
| PUT | `/admin/coupon/user/{id}/mark-expired` | 标记过期 | `coupon:user:update` |

## 会员管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/member/member/list` | 会员列表 | `member:member:list` |
| GET | `/admin/member/member/stats` | 会员统计 | `member:member:list` |
| GET | `/admin/member/member/overview` | 会员概览 | `member:member:list` |
| GET | `/admin/member/member/{id}` | 会员详情 | `member:member:read` |
| POST | `/admin/member/member` | 创建会员 | `member:member:create` |
| PUT | `/admin/member/member/{id}` | 更新会员 | `member:member:update` |
| PUT | `/admin/member/member/{id}/status` | 更新状态 | `member:member:update` |
| PUT | `/admin/member/member/{id}/tags` | 同步标签 | `member:member:tag` |

## 会员等级

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/member/level/list` | 等级列表 | `member:level:list` |
| GET | `/admin/member/level/{id}` | 等级详情 | `member:level:read` |
| POST | `/admin/member/level` | 创建等级 | `member:level:create` |
| PUT | `/admin/member/level/{id}` | 更新等级 | `member:level:update` |
| DELETE | `/admin/member/level/{id}` | 删除等级 | `member:level:delete` |

## 会员标签

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/member/tag/list` | 标签列表 | `member:tag:list` |
| GET | `/admin/member/tag/options` | 标签选项 | `member:member:list` |
| POST | `/admin/member/tag` | 创建标签 | `member:tag:create` |
| PUT | `/admin/member/tag/{id}` | 更新标签 | `member:tag:update` |
| DELETE | `/admin/member/tag/{id}` | 删除标签 | `member:tag:delete` |

## 会员钱包

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/member/account/wallet/logs` | 钱包流水 | `member:wallet:list` |
| POST | `/admin/member/account/wallet/adjust` | 余额调整 | `member:wallet:adjust` |

## 秒杀活动

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/seckill/activity/list` | 活动列表 | `seckill:activity:list` |
| GET | `/admin/seckill/activity/stats` | 活动统计 | `seckill:activity:list` |
| GET | `/admin/seckill/activity/{id}` | 活动详情 | `seckill:activity:read` |
| POST | `/admin/seckill/activity` | 创建活动 | `seckill:activity:create` |
| PUT | `/admin/seckill/activity/{id}` | 更新活动 | `seckill:activity:update` |
| DELETE | `/admin/seckill/activity/{id}` | 删除活动 | `seckill:activity:delete` |
| PUT | `/admin/seckill/activity/{id}/toggle-status` | 切换状态 | `seckill:activity:update` |

## 拼团活动

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/group-buy/list` | 活动列表 | `promotion:group_buy:list` |
| GET | `/admin/group-buy/stats` | 活动统计 | `promotion:group_buy:list` |
| GET | `/admin/group-buy/{id}` | 活动详情 | `promotion:group_buy:read` |
| POST | `/admin/group-buy` | 创建活动 | `promotion:group_buy:create` |
| PUT | `/admin/group-buy/{id}` | 更新活动 | `promotion:group_buy:update` |
| DELETE | `/admin/group-buy/{id}` | 删除活动 | `promotion:group_buy:delete` |
| PUT | `/admin/group-buy/{id}/toggle-status` | 切换状态 | `promotion:group_buy:update` |

## 系统配置

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/system/setting/groups` | 配置分组列表 | `system:setting:list` |
| GET | `/admin/system/setting/group/{group}` | 分组配置详情 | `system:setting:list` |
| PUT | `/admin/system/setting/{key}` | 更新配置项 | `system:setting:update` |

## 附件管理

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| POST | `/admin/attachment/upload` | 上传附件 | 登录 |

## 审计日志

| 方法 | 路径 | 说明 | 权限码 |
|------|------|------|--------|
| GET | `/admin/logstash/login-log/list` | 登录日志 | `logstash:login:list` |
| GET | `/admin/logstash/operation-log/list` | 操作日志 | `logstash:operation:list` |
