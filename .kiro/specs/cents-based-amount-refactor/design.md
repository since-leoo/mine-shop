# 设计文档：全站金额分单位存储与 Transformer 精简

## 概述

核心改动：
1. 数据库金额字段从 `decimal(10,2)` 改为 `int`（存分）
2. Model $casts 从 `decimal:2` 改为 `integer`
3. Entity/ValueObject 金额属性从 `float` 改为 `int`
4. Transformer 移除 `toCent()` 转换，精简 `formatGoodsDetail`
5. 前端统一分→元展示

## 架构

### 金额流转（改造后）

```
DB (int/分) → Model (int) → Entity (int) → Transformer (int, 直出) → API Response (int/分) → 前端 (÷100 展示)
```

改造前：
```
DB (decimal/元) → Model (float) → Entity (float) → Transformer (toCent→int) → API Response (int/分) → 前端展示
```

### 迁移策略

采用单次迁移脚本，在事务中完成：
1. 修改列类型 decimal→int
2. 数据转换 value * 100
3. 提供 rollback

## 组件设计

### 1. 迁移脚本

```php
// databases/migrations/xxxx_convert_amounts_to_cents.php
// 统一处理所有表的金额字段
// 每张表：ALTER COLUMN decimal→int + UPDATE value = value * 100
```

涉及的表和字段见 requirements.md 影响范围表。

### 2. Model 层改造

以 Order Model 为例：

```php
// 改造前
protected array $casts = [
    'goods_amount' => 'decimal:2',
    'shipping_fee' => 'decimal:2',
    // ...
];

// 改造后
protected array $casts = [
    'goods_amount' => 'integer',
    'shipping_fee' => 'integer',
    // ...
];
```

所有涉及金额的 Model 统一改为 `integer` cast。

### 3. OrderPriceValue 改造

```php
final class OrderPriceValue
{
    private int $goodsAmount = 0;
    private int $discountAmount = 0;
    private int $shippingFee = 0;
    private int $totalAmount = 0;
    private int $payAmount = 0;

    public function setGoodsAmount(int $goodsAmount): void
    {
        $this->goodsAmount = $goodsAmount;
        $this->recalculate();
    }

    // ... 其他 setter 同理，移除 round()

    private function recalculate(): void
    {
        $this->totalAmount = $this->goodsAmount - $this->discountAmount;
        $this->payAmount = $this->totalAmount + $this->shippingFee;
    }
}
```

### 4. OrderItemEntity 改造

```php
private int $unitPrice = 0;   // 分
private int $totalPrice = 0;  // 分

private function syncTotalPrice(): void
{
    $this->totalPrice = $this->unitPrice * $this->quantity;
}

public function toArray(): array
{
    return [
        'product_id' => $this->getProductId(),
        'sku_id' => $this->getSkuId(),
        'product_name' => $this->getProductName(),
        'sku_name' => $this->getSkuName(),
        'product_image' => $this->getProductImage(),
        'spec_values' => $this->getSpecValues(),
        'unit_price' => $this->getUnitPrice(),  // int 分
        'quantity' => $this->getQuantity(),
        'total_price' => $this->getTotalPrice(), // int 分
        'weight' => $this->getWeight(),
    ];
}
```

### 5. OrderEntity 改造

所有金额属性改为 int：

```php
private int $goodsAmount = 0;
private int $shippingFee = 0;
private int $discountAmount = 0;
private int $totalAmount = 0;
private int $payAmount = 0;
private int $couponAmount = 0;

public function verifyPrice(int $frontendAmountCent): void
{
    // 直接比较，不再需要 round($this->getPayAmount() * 100)
    if ($frontendAmountCent !== $this->getPayAmount()) {
        throw new \DomainException('商品价格已变动，请重新下单');
    }
}
```

### 6. OrderCheckoutTransformer 精简

```php
final class OrderCheckoutTransformer
{
    public function __construct(private readonly MallSettingService $mallSettingService) {}

    public function transform(OrderEntity $order): array
    {
        $storeName = $this->mallSettingService->basic()->mallName();
        $price = $order->getPriceDetail();

        $items = array_map(
            fn (OrderItemEntity $item): array => $this->formatGoodsDetail($item, $storeName),
            $order->getItems(),
        );

        $goodsCount = array_sum(array_map(
            static fn (OrderItemEntity $item) => $item->getQuantity(),
            $order->getItems(),
        ));

        $storeGoodsList = [];
        if ($items !== []) {
            $storeGoodsList[] = [
                'store_id' => '1',
                'store_name' => $storeName,
                'remark' => '',
                'goods_count' => \count($items),
                'delivery_fee' => $price?->getShippingFee() ?? 0,
                'store_total_amount' => $price?->getGoodsAmount() ?? 0,
                'store_total_pay_amount' => $price?->getPayAmount() ?? 0,
                'store_total_discount_amount' => $price?->getDiscountAmount() ?? 0,
                'store_total_coupon_amount' => $order->getCouponAmount(),
                'sku_detail_list' => $items,
                'coupon_list' => [],
            ];
        }

        return [
            'settle_type' => $order->getAddress() ? 1 : 0,
            'user_address' => $this->formatAddress($order->getAddress()),
            'total_goods_count' => $goodsCount,
            'package_count' => $items === [] ? 0 : 1,
            'total_amount' => $price?->getGoodsAmount() ?? 0,
            'total_pay_amount' => $price?->getPayAmount() ?? 0,
            'total_discount_amount' => $price?->getDiscountAmount() ?? 0,
            'total_coupon_amount' => $order->getCouponAmount(),
            'total_goods_amount' => $price?->getGoodsAmount() ?? 0,
            'total_delivery_fee' => $price?->getShippingFee() ?? 0,
            'store_goods_list' => $storeGoodsList,
            'invalid_goods_list' => [],
            'out_of_stock_goods_list' => [],
            'limit_goods_list' => [],
            'invoice_support' => $this->mallSettingService->order()->enableInvoice() ? 1 : 0,
        ];
    }

    /**
     * 基于 Entity toArray() 输出，仅补充展示所需的额外字段.
     */
    private function formatGoodsDetail(OrderItemEntity $item, string $storeName): array
    {
        return array_merge($item->toArray(), [
            'store_id' => '1',
            'store_name' => $storeName,
        ]);
    }

    // formatAddress 保持不变
    // 移除 toCent() 方法
}
```

### 7. NormalOrderStrategy 优惠券计算改造

```php
// 金额已经是分，直接整数运算
$goodsAmount = $orderEntity->getPriceDetail()?->getGoodsAmount() ?? 0;

// 满减门槛（分）
$minAmount = (int) $coupon->min_amount;
if ($minAmount > 0 && $goodsAmount < $minAmount) { ... }

// 折扣计算
private function calculateCouponDiscount(object $coupon, int $goodsAmount): int
{
    $value = (int) $coupon->value;
    $type = (string) ($coupon->type ?? 'fixed');

    return match ($type) {
        'percent', 'discount' => (int) round($goodsAmount * (1 - $value / 1000)),
        default => $value, // fixed: 面值（分）
    };
}
```

注意：percent 类型的 value 含义需要确认。当前 value=8.5 表示 8.5 折。改为分存储后，percent 类型的 value 不是金额，而是折扣率，需要特殊处理：
- fixed 类型：value 存分（如 1000 = 10元）
- percent 类型：value 保持原值（如 85 = 8.5折），不做 *100 转换

### 8. 前端工具函数

```js
// shopProgramMini/utils/price.js
export function formatPrice(cents) {
  if (cents === null || cents === undefined) return '0.00';
  return (Number(cents) / 100).toFixed(2);
}

export function formatPriceYuan(cents) {
  return '¥' + formatPrice(cents);
}
```

### 9. 前端 orderConfirm.js 适配

```js
// transformPreviewResponse 中金额字段直接透传（已经是分）
// 移除 toCentString() 函数
// 金额字段直接用 String(value) 或 Number(value)
```

## 数据模型

### 迁移前后对比

| 字段 | 迁移前 | 迁移后 | 示例 |
|---|---|---|---|
| orders.goods_amount | decimal(10,2) = 99.90 | int = 9990 | ¥99.90 |
| order_items.unit_price | decimal(10,2) = 49.95 | int = 4995 | ¥49.95 |
| coupons.value (fixed) | decimal(10,2) = 10.00 | int = 1000 | ¥10.00 |
| coupons.value (percent) | decimal(10,2) = 8.50 | int = 850 | 8.5折 |
| coupons.min_amount | decimal(10,2) = 100.00 | int = 10000 | 满¥100 |

### percent 类型 value 特殊说明

percent 类型的 value 表示折扣率而非金额：
- 存储值 850 表示 8.5 折（即打 85%）
- 计算公式：`discount = goodsAmount - (int) round(goodsAmount * value / 1000)`
- 迁移时 percent 类型的 value 也做 *100 转换（8.50 → 850）

## 错误处理

| 场景 | 处理 |
|---|---|
| 迁移中断 | 事务回滚，数据不变 |
| 旧数据有小数精度丢失 | 迁移时 `ROUND(value * 100)` 确保整数 |
| 前端未适配分单位 | 金额显示异常（×100），需同步发布 |

## 测试策略

- 迁移脚本：验证数据转换正确性（元→分→元 round-trip）
- Entity 单元测试：金额属性为 int，计算结果正确
- Transformer 测试：输出金额为 int，无 toCent 调用
- 端到端：preview/submit 流程金额正确
