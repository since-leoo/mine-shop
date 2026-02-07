# 支付系统

Mine Shop 基于 [yansongda/pay](https://github.com/yansongda/pay) 实现微信支付与余额支付，结合订单、钱包模块形成闭环。

## 支持的支付方式

| 支付方式 | 场景 | 状态 |
| -------- | ---- | ---- |
| 微信支付（小程序/公众号/App） | 微信生态内闭环 | ✅ |
| 余额支付 | 会员钱包余额直接扣减 | ✅ |
| 其他扩展 | 支付宝、银联等 | 可通过扩展实现 |

## 配置

配置文件位于 `config/autoload/pay.php`，通过 `.env` 管理密钥与证书路径：

```php
'wechat' => [
    'app_id' => env('WECHAT_APP_ID'),
    'mini_app_id' => env('WECHAT_MINI_APP_ID'),
    'mch_id' => env('WECHAT_MCH_ID'),
    'mch_secret_key' => env('WECHAT_MCH_SECRET_KEY'),
    'mch_secret_cert' => env('WECHAT_MCH_SECRET_CERT'),
    'mch_public_cert_path' => env('WECHAT_MCH_PUBLIC_CERT_PATH'),
    'notify_url' => env('APP_URL') . '/api/payment/wechat/notify',
    'log' => ['file' => BASE_PATH . '/runtime/logs/wechat.log'],
],
```

余额支付无需第三方配置，只需会员钱包余额足够。

## 支付入口

前端通过 `POST /api/v1/order/payment` 发起支付，传入 `order_no` 和 `pay_method`。

`AppApiOrderPaymentService` 编排支付流程，核心委托给 `DomainPayService`。

## DomainPayService 流程

### 初始化

```php
$payService->init($orderEntity, $memberEntity);
```

将订单实体和会员实体注入服务实例，后续操作基于这两个上下文。

### 微信支付 — `payByWechat()`

1. 根据 `orderEntity.payMethod` 从 `config('pay.wechat.default')` 提取对应支付方式配置
2. 调用 `DomainOrderPaymentService::create()` 创建支付记录
3. 通过 `YsdPayService::pay()` 调用 yansongda/pay 生成支付参数
4. 返回 `prepay_id` 等参数给前端唤起支付

### 余额支付 — `payByBalance()`

在事务中完成（`#[Transactional]`）：

1. 创建支付记录（`DomainOrderPaymentService::create()`）
2. 加载会员钱包实体（`DomainMemberWalletService::getEntity()`）
3. 设置变动金额（负数）、来源（`Consume`）、备注
4. 执行钱包余额变更（`walletEntity->changeBalance()`）
5. 更新订单：设置 `pay_method = balance`、`pay_no`、标记已支付
6. 标记支付记录为已支付
7. 持久化钱包变更
8. 发布 `MemberBalanceAdjusted` 事件（触发钱包流水记录）

### 支付回调 — `notify()`

微信异步通知处理：

1. 检查订单是否已支付（幂等）
2. 验证回调 `trade_state === 'SUCCESS'`
3. 更新订单：`pay_method = wechat`、`pay_no = transaction_id`、标记已支付
4. 更新支付记录（`markPaidByOrderNo`）

## 核心类关系

```
OrderController
  └─ POST /api/v1/order/payment
       └─ AppApiOrderPaymentService
            └─ DomainPayService
                 ├─ init(orderEntity, memberEntity)
                 ├─ payByWechat()  → YsdPayService → yansongda/pay
                 └─ payByBalance() → DomainMemberWalletService
                      └─ DomainOrderPaymentService (支付记录)
```

## 支付记录

`DomainOrderPaymentService` 管理支付记录的生命周期：

- `create()` — 创建待支付记录（order_id、order_no、member_id、pay_method、amount）
- `markPaid()` — 按 payment_no 标记已支付
- `markPaidByOrderNo()` — 按 order_no 标记已支付（回调场景）

## 安全与合规

1. **证书与密钥**：私钥文件放在安全目录，通过 `.env` 配置路径
2. **回调验签**：yansongda/pay 自动验签，防止伪造通知
3. **幂等校验**：`notify()` 检查 `pay_status`，已支付的订单不重复处理
4. **事务保障**：余额支付在 `#[Transactional]` 中执行，钱包扣减与订单更新原子一致
5. **日志**：支付日志写入 `runtime/logs/wechat.log`

## 调试技巧

- 本地回调：使用 `ngrok` / `cloudflared` 映射回调地址
- Mock 支付：测试环境可配置 `payment.mock=true` 绕过真实三方
- 支付状态以 `GET /api/v1/order/detail/{orderNo}` 查询为准，不依赖前端跳转

通过 `DomainPayService` + yansongda/pay 适配层，可快速增加新的支付场景，同时保持订单与财务数据一致性。
