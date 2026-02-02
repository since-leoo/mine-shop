# 支付系统

Mine Shop 基于 [yansongda/pay](https://github.com/yansongda/pay) 实现支付宝、微信、余额等多种支付方式，并结合订单、退款、钱包等模块形成闭环。本页介绍配置方法、调用流程与回调处理。

## 支持的支付方式

| 支付方式 | 场景 | 状态 |
| -------- | ---- | ---- |
| 支付宝 (App/Web/H5) | B2C 官方站、小程序外链 | ✅ |
| 微信支付 (小程序/公众号/App) | 微信生态内闭环 | ✅ |
| 余额支付 | 会员钱包余额 | ✅ |
| 其他扩展 | 银联、线下转账等 | 可通过扩展实现 |

## 配置

配置文件位于 `config/autoload/pay.php`。建议通过 `.env` 管理密钥与证书路径。

```php
'alipay' => [
    'app_id' => env('ALIPAY_APP_ID'),
    'app_secret_cert' => env('ALIPAY_APP_SECRET_CERT'),
    'alipay_public_cert_path' => env('ALIPAY_PUBLIC_CERT_PATH'),
    'alipay_root_cert_path' => env('ALIPAY_ROOT_CERT_PATH'),
    'notify_url' => env('APP_URL') . '/api/payment/alipay/notify',
    'return_url' => env('APP_URL') . '/api/payment/alipay/return',
    'log' => ['file' => BASE_PATH . '/runtime/logs/alipay.log'],
    'mode' => env('ALIPAY_MODE', 'normal'), // normal/dev/sandbox
],

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

余额支付无需第三方配置，只需保证会员钱包余额足够并记录流水。

## 创建支付

应用层统一调用 `PaymentService`：

```php
$payload = [
    'order_no' => $order->getOrderNo(),
    'amount' => $order->getPayAmount(),
    'subject' => sprintf('商城订单-%s', $order->getOrderNo()),
    'client' => 'mini_program', // 可选：web/app/h5/mini_program
];

$paymentParams = $paymentService->create($payload, 'alipay'); // 或 wechat/balance
```

返回值由具体支付方式决定：

- **支付宝 App**：`order_string`
- **支付宝 Web/H5**：`redirect_url`
- **微信 JSAPI/小程序**：`prepay_id` 等参数
- **余额支付**：直接扣减会员余额，生成钱包流水并将订单置为已支付

## 回调处理

### 支付宝/微信回调

1. `yansongda/pay` 自动验签。
2. 验签通过后，根据 `out_trade_no` 查订单。
3. 若订单尚未标记为已支付：
   - 更新 `pay_status = paid`、`status = paid`。
   - 写入 `pay_time`、`pay_method`、`pay_no`。
   - 发布 `OrderPaidEvent`，触发发货、积分、营销等逻辑。
4. 返回 `success` 响应，避免重复通知。

### 余额支付

后台直接调用 `MemberAccountService` 扣减余额并写入流水，随后更新订单状态，无需第三方回调。

## 退款

- 由 `RefundService` 编排：校验订单状态、金额、支付渠道；调用 yansongda/pay 的 `refund()` 方法。
- 成功后同步更新订单 `status = refunded`、`pay_status = refunded`，并写入退款日志。
- 余额退款直接回充钱包。

## 安全与合规

1. **证书与密钥**：建议将私钥文件放在安全目录，并通过 `.env` 配置路径。
2. **回调 IP 白名单**：可开启网关防护，仅允许支付宝/微信官方 IP 访问通知地址。
3. **日志**：对接支付时建议保留 `payment.log`，方便排查。
4. **重放攻击防护**：支付回调以 `pay_no` + `order_no` 幂等校验，不重复处理。
5. **风控**：可结合会员等级、IP、设备指纹实现支付限额/二次校验。

## 调试技巧

- 沙盒模式：`ALIPAY_MODE=sandbox` / 微信提供 `sandbox_signkey`。
- 本地回调：可使用 `ngrok`/`cloudflared` 等工具映射回调地址。
- Mock 支付：在测试环境可提供 `payment.mock=true` 配置，绕过真实三方并直接回调。

通过统一的 PaymentService + yansongda/pay 适配层，Mine Shop 可以快速增加新的支付场景，同时保持订单与财务数据的一致性。*** End Patch
