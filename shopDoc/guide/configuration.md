# 配置说明

本文档详细介绍系统的各项配置。

## 环境变量配置

环境变量配置文件位于项目根目录的 `.env` 文件。

## 商城配置

配置文件：`config/autoload/mall.php`

### 货币设置

```php
'currency' => [
    'code' => 'CNY',
    'symbol' => '¥',
    'decimal_places' => 2,
],
```

### 产品设置

```php
'product' => [
    // 默认状态
    'default_status' => 'active',
    
    // 是否自动审核
    'auto_approve' => true,
    
    // 图片限制
    'max_images' => 10,
    'max_image_size' => 5 * 1024 * 1024, // 5MB
],
```

### 订单设置

```php
'order' => [
    // 自动确认收货天数
    'auto_confirm_days' => 7,
    
    // 自动关闭未支付订单（分钟）
    'auto_close_minutes' => 30,
    
    // 订单号前缀
    'order_no_prefix' => 'ORD',
],
```

### 会员设置

```php
'member' => [
    // 默认会员等级
    'default_level' => 1,
    
    // 积分兑换比例（1元 = N积分）
    'points_ratio' => 100,
],
```

### 支付方式配置

```php
'payment' => [
    'methods' => [
        'alipay' => [
            'name' => '支付宝',
            'enabled' => false,
        ],
        'wechat' => [
            'name' => '微信支付',
            'enabled' => false,
        ],
        'balance' => [
            'name' => '余额支付',
            'enabled' => true,
        ],
    ],
],
```

### 配送方式配置

```php
'shipping' => [
    'methods' => [
        'express' => [
            'name' => '快递配送',
            'enabled' => true,
        ],
        'self_pickup' => [
            'name' => '到店自提',
            'enabled' => false,
        ],
    ],
],
```

## 支付配置

配置文件：`config/autoload/pay.php`

### 支付宝配置

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

## 微信配置

配置文件：`config/autoload/wechat.php`

### 小程序配置

```php
'mini_program' => [
    'app_id' => env('WECHAT_MINI_APP_ID'),
    'secret' => env('WECHAT_MINI_APP_SECRET'),
    'token' => env('WECHAT_MINI_APP_TOKEN'),
    'aes_key' => env('WECHAT_MINI_APP_AES_KEY'),
],
```

### 公众号配置

```php
'official_account' => [
    'app_id' => env('WECHAT_OFFICIAL_APP_ID'),
    'secret' => env('WECHAT_OFFICIAL_APP_SECRET'),
    'token' => env('WECHAT_OFFICIAL_APP_TOKEN'),
    'aes_key' => env('WECHAT_OFFICIAL_APP_AES_KEY'),
],
```


## 下一步

- [DDD 架构](/architecture/ddd) - 了解系统架构设计
- [订单设计](/core/order-design) - 了解订单系统设计
- [库存管理](/core/stock-management) - 了解库存管理实现
