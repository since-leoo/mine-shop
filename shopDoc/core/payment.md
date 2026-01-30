# 支付系统（yansongda/pay）

本系统支持多种支付方式，包括支付宝、微信支付和余额支付。

## 支付方式

### 支持的支付方式

| 支付方式 | 说明 | 状态 |
|---------|------|------|
| 支付宝 | App、Web、H5 支付 | ✅ 支持 |
| 微信支付 | 小程序、公众号、App 支付 | ✅ 支持 |
| 余额支付 | 会员余额支付 | ✅ 支持 |

## 配置说明

### 支付宝配置

文件：`config/autoload/pay.php`

```php
'alipay' => [
    // 应用 ID
    'app_id' => env('ALIPAY_APP_ID'),
    
    // 应用私钥证书路径
    'app_secret_cert' => env('ALIPAY_APP_SECRET_CERT'),
    
    // 支付宝公钥证书路径
    'alipay_public_cert_path' => env('ALIPAY_PUBLIC_CERT_PATH'),
    
    // 支付宝根证书路径
    'alipay_root_cert_path' => env('ALIPAY_ROOT_CERT_PATH'),
    
    // 回调 URL
    'notify_url' => env('APP_URL') . '/api/payment/alipay/notify',
    'return_url' => env('APP_URL') . '/api/payment/alipay/return',
    
    // 日志配置
    'log' => [
        'file' => BASE_PATH . '/runtime/logs/alipay.log',
        'level' => 'debug',
    ],
    
    // 模式: normal, dev, sandbox
    'mode' => env('ALIPAY_MODE', 'normal'),
],
```

### 微信支付配置

```php
'wechat' => [
    // 应用 ID
    'app_id' => env('WECHAT_APP_ID'),
    
    // 小程序 ID
    'mini_app_id' => env('WECHAT_MINI_APP_ID'),
    
    // 商户号
    'mch_id' => env('WECHAT_MCH_ID'),
    
    // 商户密钥
    'mch_secret_key' => env('WECHAT_MCH_SECRET_KEY'),
    
    // 商户证书路径
    'mch_secret_cert' => env('WECHAT_MCH_SECRET_CERT'),
    'mch_public_cert_path' => env('WECHAT_MCH_PUBLIC_CERT_PATH'),
    
    // 回调 URL
    'notify_url' => env('APP_URL') . '/api/payment/wechat/notify',
    
    // 日志配置
    'log' => [
        'file' => BASE_PATH . '/runtime/logs/wechat.log',
        'level' => 'debug',
    ],
    
    // 模式: normal, dev, sandbox
    'mode' => env('WECHAT_MODE', 'normal'),
],
```

### 环境变量配置

`.env` 文件：

```bash
# 支付宝配置
ALIPAY_APP_ID=your_app_id
ALIPAY_APP_SECRET_CERT=/path/to/app_cert.crt
ALIPAY_PUBLIC_CERT_PATH=/path/to/alipay_cert.crt
ALIPAY_ROOT_CERT_PATH=/path/to/alipay_root_cert.crt
ALIPAY_MODE=normal

# 微信支付配置
WECHAT_APP_ID=your_app_id
WECHAT_MINI_APP_ID=your_mini_app_id
WECHAT_MCH_ID=your_mch_id
WECHAT_MCH_SECRET_KEY=your_mch_key
WECHAT_MCH_SECRET_CERT=/path/to/apiclient_cert.pem
WECHAT_MCH_PUBLIC_CERT_PATH=/path/to/apiclient_key.pem
WECHAT_MODE=normal
```

## 支付流程

### 1. 创建支付订单

```
用户提交订单
    ↓
创建订单记录
    ↓
选择支付方式
    ↓
调用支付接口
    ↓
返回支付参数
    ↓
用户完成支付
    ↓
接收支付回调
    ↓
更新订单状态
```

### 2. 支付宝支付流程

```php
namespace App\Domain\Payment\Service;

use Yansongda\Pay\Pay;

class AlipayService
{
    /**
     * App 支付
     */
    public function app(array $order): array
    {
        $params = [
            'out_trade_no' => $order['order_no'],
            'total_amount' => $order['pay_amount'],
            'subject' => '商城订单-' . $order['order_no'],
            'body' => '商品订单支付',
        ];

        $result = Pay::alipay()->app($params);
        
        return [
            'order_string' => $result->getBody()->getContents(),
        ];
    }

    /**
     * Web 支付
     */
    public function web(array $order): array
    {
        $params = [
            'out_trade_no' => $order['order_no'],
            'total_amount' => $order['pay_amount'],
            'subject' => '商城订单-' . $order['order_no'],
        ];

        $result = Pay::alipay()->web($params);
        
        return [
            'redirect_url' => $result->getBody()->getContents(),
        ];
    }

    /**
     * H5 支付
     */
    public function wap(array $order): array
    {
        $params = [
            'out_trade_no' => $order['order_no'],
            'total_amount' => $order['pay_amount'],
            'subject' => '商城订单-' . $order['order_no'],
            'quit_url' => env('APP_URL'),
        ];

        $result = Pay::alipay()->wap($params);
        
        return [
            'redirect_url' => $result->getBody()->getContents(),
        ];
    }

    /**
     * 查询订单
     */
    public function query(string $orderNo): array
    {
        $result = Pay::alipay()->query([
            'out_trade_no' => $orderNo,
        ]);

        return $result->toArray();
    }

    /**
     * 关闭订单
     */
    public function close(string $orderNo): array
    {
        $result = Pay::alipay()->close([
            'out_trade_no' => $orderNo,
        ]);

        return $result->toArray();
    }

    /**
     * 退款
     */
    public function refund(array $params): array
    {
        $result = Pay::alipay()->refund([
            'out_trade_no' => $params['order_no'],
            'refund_amount' => $params['refund_amount'],
            'out_request_no' => $params['refund_no'],
            'refund_reason' => $params['reason'] ?? '用户申请退款',
        ]);

        return $result->toArray();
    }
}
```

### 3. 微信支付流程

```php
namespace App\Domain\Payment\Service;

use Yansongda\Pay\Pay;

class WechatPayService
{
    /**
     * 小程序支付
     */
    public function mini(array $order, string $openid): array
    {
        $params = [
            'out_trade_no' => $order['order_no'],
            'total_fee' => $order['pay_amount'] * 100, // 单位：分
            'body' => '商城订单-' . $order['order_no'],
            'openid' => $openid,
        ];

        $result = Pay::wechat()->mini($params);
        
        return $result->toArray();
    }

    /**
     * App 支付
     */
    public function app(array $order): array
    {
        $params = [
            'out_trade_no' => $order['order_no'],
            'total_fee' => $order['pay_amount'] * 100,
            'body' => '商城订单-' . $order['order_no'],
        ];

        $result = Pay::wechat()->app($params);
        
        return $result->toArray();
    }

    /**
     * 公众号支付
     */
    public function mp(array $order, string $openid): array
    {
        $params = [
            'out_trade_no' => $order['order_no'],
            'total_fee' => $order['pay_amount'] * 100,
            'body' => '商城订单-' . $order['order_no'],
            'openid' => $openid,
        ];

        $result = Pay::wechat()->mp($params);
        
        return $result->toArray();
    }

    /**
     * H5 支付
     */
    public function wap(array $order): array
    {
        $params = [
            'out_trade_no' => $order['order_no'],
            'total_fee' => $order['pay_amount'] * 100,
            'body' => '商城订单-' . $order['order_no'],
        ];

        $result = Pay::wechat()->wap($params);
        
        return [
            'mweb_url' => $result['mweb_url'],
        ];
    }

    /**
     * 查询订单
     */
    public function query(string $orderNo): array
    {
        $result = Pay::wechat()->query([
            'out_trade_no' => $orderNo,
        ]);

        return $result->toArray();
    }

    /**
     * 关闭订单
     */
    public function close(string $orderNo): array
    {
        $result = Pay::wechat()->close([
            'out_trade_no' => $orderNo,
        ]);

        return $result->toArray();
    }

    /**
     * 退款
     */
    public function refund(array $params): array
    {
        $result = Pay::wechat()->refund([
            'out_trade_no' => $params['order_no'],
            'out_refund_no' => $params['refund_no'],
            'total_fee' => $params['total_amount'] * 100,
            'refund_fee' => $params['refund_amount'] * 100,
            'refund_desc' => $params['reason'] ?? '用户申请退款',
        ]);

        return $result->toArray();
    }
}
```

### 4. 余额支付

```php
namespace App\Domain\Payment\Service;

class BalancePayService
{
    public function __construct(
        private WalletRepository $walletRepository
    ) {}

    /**
     * 余额支付
     */
    public function pay(int $memberId, float $amount, string $orderNo): bool
    {
        // 获取钱包
        $wallet = $this->walletRepository->findByMemberId($memberId);
        
        if (!$wallet) {
            throw new \RuntimeException('钱包不存在');
        }

        // 检查余额
        if ($wallet->balance < $amount) {
            throw new \RuntimeException('余额不足');
        }

        // 扣减余额
        $wallet->balance -= $amount;
        $this->walletRepository->save($wallet);

        // 记录交易
        $this->walletRepository->createTransaction([
            'wallet_id' => $wallet->id,
            'type' => 'payment',
            'amount' => -$amount,
            'balance' => $wallet->balance,
            'order_no' => $orderNo,
            'remark' => '订单支付',
        ]);

        return true;
    }

    /**
     * 退款
     */
    public function refund(int $memberId, float $amount, string $orderNo): bool
    {
        // 获取钱包
        $wallet = $this->walletRepository->findByMemberId($memberId);
        
        if (!$wallet) {
            throw new \RuntimeException('钱包不存在');
        }

        // 增加余额
        $wallet->balance += $amount;
        $this->walletRepository->save($wallet);

        // 记录交易
        $this->walletRepository->createTransaction([
            'wallet_id' => $wallet->id,
            'type' => 'refund',
            'amount' => $amount,
            'balance' => $wallet->balance,
            'order_no' => $orderNo,
            'remark' => '订单退款',
        ]);

        return true;
    }
}
```

## 支付回调

### 支付宝回调处理

```php
namespace App\Interface\Api\Controller\Payment;

use Yansongda\Pay\Pay;

class AlipayController
{
    /**
     * 异步回调
     */
    public function notify(): string
    {
        $data = Pay::alipay()->callback();

        // 验证签名
        if (!$data) {
            return 'fail';
        }

        // 处理订单
        $this->handlePaymentSuccess(
            $data['out_trade_no'],
            $data['trade_no'],
            $data['total_amount']
        );

        return 'success';
    }

    /**
     * 同步回调
     */
    public function return(): string
    {
        $data = Pay::alipay()->callback();

        // 跳转到订单详情页
        return redirect('/order/' . $data['out_trade_no']);
    }

    /**
     * 处理支付成功
     */
    private function handlePaymentSuccess(
        string $orderNo,
        string $tradeNo,
        float $amount
    ): void {
        // 查找订单
        $order = $this->orderRepository->findByOrderNo($orderNo);

        if (!$order) {
            logger()->error("Order not found: {$orderNo}");
            return;
        }

        // 检查订单状态
        if ($order->payStatus === PaymentStatus::PAID->value) {
            return; // 已支付，避免重复处理
        }

        // 更新订单状态
        $order->payStatus = PaymentStatus::PAID->value;
        $order->status = OrderStatus::PAID->value;
        $order->payTime = date('Y-m-d H:i:s');
        $order->payNo = $tradeNo;
        $order->payMethod = 'alipay';

        $this->orderRepository->save($order);

        // 发布事件
        event(new OrderPaidEvent($order));
    }
}
```

### 微信支付回调处理

```php
namespace App\Interface\Api\Controller\Payment;

use Yansongda\Pay\Pay;

class WechatPayController
{
    /**
     * 异步回调
     */
    public function notify(): string
    {
        $data = Pay::wechat()->callback();

        // 验证签名
        if (!$data) {
            return Pay::wechat()->success()->getBody()->getContents();
        }

        // 处理订单
        $this->handlePaymentSuccess(
            $data['out_trade_no'],
            $data['transaction_id'],
            $data['total_fee'] / 100
        );

        return Pay::wechat()->success()->getBody()->getContents();
    }

    /**
     * 处理支付成功
     */
    private function handlePaymentSuccess(
        string $orderNo,
        string $transactionId,
        float $amount
    ): void {
        // 同支付宝处理逻辑
        // ...
    }
}
```

## 数据库设计

### 支付记录表

```sql
CREATE TABLE `mall_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL COMMENT '订单ID',
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `payment_no` varchar(64) DEFAULT NULL COMMENT '支付流水号',
  `payment_method` varchar(32) NOT NULL COMMENT '支付方式',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` enum('pending','success','failed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '支付状态',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  `callback_data` json DEFAULT NULL COMMENT '回调数据',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_order_no` (`order_no`),
  KEY `idx_payment_no` (`payment_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付记录表';
```

### 退款记录表

```sql
CREATE TABLE `mall_payment_refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL COMMENT '订单ID',
  `payment_id` bigint unsigned NOT NULL COMMENT '支付记录ID',
  `refund_no` varchar(64) NOT NULL COMMENT '退款单号',
  `refund_amount` decimal(10,2) NOT NULL COMMENT '退款金额',
  `reason` varchar(500) DEFAULT NULL COMMENT '退款原因',
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending' COMMENT '退款状态',
  `refunded_at` datetime DEFAULT NULL COMMENT '退款时间',
  `callback_data` json DEFAULT NULL COMMENT '回调数据',
  
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refund_no` (`refund_no`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='退款记录表';
```

## 安全建议

### 1. 验证签名

所有支付回调必须验证签名，防止伪造请求。

### 2. 幂等性处理

支付回调可能重复发送，需要做幂等性处理。

### 3. 金额校验

回调时需要校验支付金额是否与订单金额一致。

### 4. 日志记录

记录所有支付相关的操作日志，便于排查问题。

### 5. 异常处理

支付失败时需要妥善处理，避免数据不一致。

## 下一步

- [订单设计](/core/order-design) - 了解订单系统设计
- [API 接口](/api/) - 查看支付相关 API
