# 订单设计

本文档详细介绍订单系统的设计和实现。

## 订单模型

### 订单实体 (OrderEntity)

```php
namespace App\Domain\Order\Entity;

class OrderEntity
{
    // 基本信息
    public ?int $id = null;
    public string $orderNo;              // 订单号
    public int $memberId;                // 会员ID
    public string $orderType;            // 订单类型
    
    // 状态信息
    public string $status;               // 订单状态
    public string $payStatus;            // 支付状态
    public string $shippingStatus;       // 发货状态
    
    // 金额信息
    public float $goodsAmount;           // 商品金额
    public float $shippingFee;           // 运费
    public float $discountAmount;        // 优惠金额
    public float $totalAmount;           // 订单总金额
    public float $payAmount;             // 实付金额
    
    // 支付信息
    public ?string $payTime = null;      // 支付时间
    public ?string $payNo = null;        // 支付流水号
    public ?string $payMethod = null;    // 支付方式
    
    // 备注信息
    public ?string $buyerRemark = null;  // 买家备注
    public ?string $sellerRemark = null; // 卖家备注
    
    // 关联数据
    public array $items = [];            // 订单项
    public ?OrderAddressValue $address = null;  // 收货地址
    public ?OrderPriceValue $priceDetail = null; // 价格详情
    
    // 其他信息
    public int $packageCount = 0;        // 包裹数量
    public ?string $expireTime = null;   // 过期时间
}
```

### 订单项实体 (OrderItemEntity)

```php
namespace App\Domain\Order\Entity;

class OrderItemEntity
{
    public ?int $id = null;
    public int $orderId;                 // 订单ID
    public int $productId;               // 商品ID
    public int $skuId;                   // SKU ID
    
    // 商品信息快照
    public string $productName;          // 商品名称
    public string $skuName;              // SKU名称
    public string $productImage;         // 商品图片
    public array $specValues;            // 规格值
    
    // 价格和数量
    public float $unitPrice;             // 单价
    public int $quantity;                // 数量
    public float $totalPrice;            // 总价
    
    // 其他信息
    public float $weight;                // 重量
}
```

## 订单状态

### 订单状态枚举

```php
namespace App\Domain\Order\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';           // 待支付
    case PAID = 'paid';                 // 已支付
    case SHIPPED = 'shipped';           // 已发货
    case COMPLETED = 'completed';       // 已完成
    case CANCELLED = 'cancelled';       // 已取消
    case REFUNDED = 'refunded';         // 已退款
}
```

### 支付状态枚举

```php
namespace App\Domain\Order\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';           // 待支付
    case PAID = 'paid';                 // 已支付
    case FAILED = 'failed';             // 支付失败
    case CANCELLED = 'cancelled';       // 已取消
    case REFUNDED = 'refunded';         // 已退款
}
```

### 发货状态枚举

```php
namespace App\Domain\Order\Enum;

enum ShippingStatus: string
{
    case PENDING = 'pending';           // 待发货
    case PARTIAL_SHIPPED = 'partial_shipped'; // 部分发货
    case SHIPPED = 'shipped';           // 已发货
    case DELIVERED = 'delivered';       // 已送达
}
```

### 状态流转图

```
订单状态流转:

PENDING (待支付)
    ↓ 支付成功
PAID (已支付)
    ↓ 发货
SHIPPED (已发货)
    ↓ 确认收货
COMPLETED (已完成)

或者:
PENDING → CANCELLED (取消订单)
PAID → REFUNDED (申请退款)
```

## 订单类型

### 订单类型枚举

```php
namespace App\Domain\Order\Enum;

enum OrderType: string
{
    case NORMAL = 'normal';             // 普通订单
    case SECKILL = 'seckill';           // 秒杀订单
    case GROUP_BUY = 'group_buy';       // 团购订单
}
```

### 策略模式实现

```php
namespace App\Domain\Order\Strategy;

interface OrderTypeStrategyInterface
{
    /**
     * 获取订单类型
     */
    public function type(): string;

    /**
     * 验证订单
     */
    public function validate(OrderEntity $entity): void;

    /**
     * 构建订单草稿
     */
    public function buildDraft(array $data): OrderEntity;

    /**
     * 订单创建后处理
     */
    public function postCreate(OrderEntity $entity): void;
}
```

### 普通订单策略

```php
namespace App\Domain\Order\Strategy;

class NormalOrderStrategy implements OrderTypeStrategyInterface
{
    public function type(): string
    {
        return OrderType::NORMAL->value;
    }

    public function validate(OrderEntity $entity): void
    {
        // 验证商品库存
        foreach ($entity->items as $item) {
            $sku = $this->skuRepository->find($item->skuId);
            
            if (!$sku || $sku->stock < $item->quantity) {
                throw new \RuntimeException(
                    "商品 {$item->productName} 库存不足"
                );
            }
        }

        // 验证收货地址
        if (!$entity->address) {
            throw new \RuntimeException('请选择收货地址');
        }
    }

    public function buildDraft(array $data): OrderEntity
    {
        $entity = new OrderEntity();
        $entity->orderType = $this->type();
        $entity->memberId = $data['member_id'];
        
        // 构建订单项
        foreach ($data['items'] as $itemData) {
            $entity->items[] = $this->buildOrderItem($itemData);
        }
        
        // 计算金额
        $entity->calculateAmount();
        
        return $entity;
    }

    public function postCreate(OrderEntity $entity): void
    {
        // 设置订单过期时间（30分钟）
        $entity->expireTime = date('Y-m-d H:i:s', time() + 1800);
    }
}
```

## 订单服务

### OrderService (领域服务)

```php
namespace App\Domain\Order\Service;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\Order\Strategy\OrderTypeStrategyFactory;
use App\Domain\Order\Event\OrderCreatedEvent;

class OrderService
{
    public function __construct(
        private OrderRepository $repository,
        private OrderStockService $stockService,
        private OrderTypeStrategyFactory $strategyFactory
    ) {}

    /**
     * 订单预览
     */
    public function preview(array $data): OrderEntity
    {
        // 获取订单策略
        $strategy = $this->strategyFactory->make($data['order_type']);
        
        // 构建订单草稿
        $entity = $strategy->buildDraft($data);
        
        // 验证订单
        $strategy->validate($entity);
        
        return $entity;
    }

    /**
     * 提交订单
     */
    public function submit(OrderEntity $entity): OrderEntity
    {
        // 获取订单策略
        $strategy = $this->strategyFactory->make($entity->orderType);
        
        // 验证订单
        $strategy->validate($entity);
        
        // 生成订单号
        $entity->orderNo = $this->generateOrderNo();
        
        // 获取库存锁
        $locks = $this->stockService->acquireLocks($entity->items);
        
        try {
            // 扣减库存
            $this->stockService->reserve($entity->items);
            
            // 保存订单
            $order = $this->repository->save($entity);
            
            // 后置处理
            $strategy->postCreate($order);
            
            // 发布事件
            event(new OrderCreatedEvent($order));
            
            return $order;
            
        } catch (\Exception $e) {
            // 回滚库存
            $this->stockService->rollback($entity->items);
            throw $e;
            
        } finally {
            // 释放锁
            $this->stockService->releaseLocks($locks);
        }
    }

    /**
     * 发货
     */
    public function ship(int $orderId, array $data): bool
    {
        $order = $this->repository->find($orderId);
        
        if (!$order) {
            throw new \RuntimeException('订单不存在');
        }
        
        if ($order->status !== OrderStatus::PAID->value) {
            throw new \RuntimeException('订单状态不正确');
        }
        
        // 更新订单状态
        $result = $this->repository->ship($orderId, $data);
        
        if ($result) {
            // 发布事件
            event(new OrderShippedEvent($order));
        }
        
        return $result;
    }

    /**
     * 取消订单
     */
    public function cancel(int $orderId, string $reason): bool
    {
        $order = $this->repository->find($orderId);
        
        if (!$order) {
            throw new \RuntimeException('订单不存在');
        }
        
        if ($order->status !== OrderStatus::PENDING->value) {
            throw new \RuntimeException('订单状态不正确');
        }
        
        // 恢复库存
        $this->stockService->rollback($order->items);
        
        // 更新订单状态
        $result = $this->repository->cancel($orderId, $reason);
        
        if ($result) {
            // 发布事件
            event(new OrderCancelledEvent($order));
        }
        
        return $result;
    }

    /**
     * 生成订单号
     */
    private function generateOrderNo(): string
    {
        return 'ORD' . date('YmdHis') . rand(1000, 9999);
    }
}
```

## 应用层服务

### OrderCommandService (写操作)

```php
namespace App\Application\Order\Service;

use App\Application\Order\Assembler\OrderAssembler;
use App\Domain\Order\Service\OrderService;

class OrderCommandService
{
    public function __construct(
        private OrderService $orderService,
        private OrderAssembler $assembler
    ) {}

    /**
     * 订单预览
     */
    public function preview(array $data): array
    {
        $entity = $this->orderService->preview($data);
        return $entity->toArray();
    }

    /**
     * 提交订单
     */
    public function submit(array $data): array
    {
        $entity = $this->assembler->toEntity($data);
        $order = $this->orderService->submit($entity);
        return $order->toArray();
    }

    /**
     * 发货
     */
    public function ship(int $orderId, array $data): bool
    {
        return $this->orderService->ship($orderId, $data);
    }

    /**
     * 取消订单
     */
    public function cancel(int $orderId, string $reason): bool
    {
        return $this->orderService->cancel($orderId, $reason);
    }
}
```

### OrderQueryService (读操作)

```php
namespace App\Application\Order\Service;

use App\Domain\Order\Repository\OrderRepository;

class OrderQueryService
{
    public function __construct(
        private OrderRepository $repository
    ) {}

    /**
     * 分页查询
     */
    public function page(array $params): array
    {
        return $this->repository->page($params);
    }

    /**
     * 查询详情
     */
    public function find(int $id): ?array
    {
        $order = $this->repository->find($id);
        return $order?->toArray();
    }

    /**
     * 统计数据
     */
    public function stats(): array
    {
        return $this->repository->stats();
    }
}
```

## 数据库设计

### 订单表 (mall_orders)

```sql
CREATE TABLE `mall_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `member_id` bigint unsigned NOT NULL COMMENT '会员ID',
  `order_type` enum('normal','seckill','group_buy') NOT NULL DEFAULT 'normal' COMMENT '订单类型',
  
  -- 状态
  `status` enum('pending','paid','shipped','completed','cancelled','refunded') NOT NULL DEFAULT 'pending' COMMENT '订单状态',
  `pay_status` enum('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending' COMMENT '支付状态',
  `shipping_status` enum('pending','partial_shipped','shipped','delivered') NOT NULL DEFAULT 'pending' COMMENT '发货状态',
  
  -- 金额
  `goods_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品金额',
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  
  -- 支付信息
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `pay_no` varchar(64) DEFAULT NULL COMMENT '支付流水号',
  `pay_method` varchar(32) DEFAULT NULL COMMENT '支付方式',
  
  -- 备注
  `buyer_remark` varchar(500) DEFAULT NULL COMMENT '买家备注',
  `seller_remark` varchar(500) DEFAULT NULL COMMENT '卖家备注',
  
  -- 其他
  `package_count` int NOT NULL DEFAULT '0' COMMENT '包裹数量',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_member_status` (`member_id`, `status`),
  KEY `idx_status_created` (`status`, `created_at`),
  KEY `idx_pay_status_time` (`pay_status`, `pay_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';
```

### 订单项表 (mall_order_items)

```sql
CREATE TABLE `mall_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL COMMENT '订单ID',
  `product_id` bigint unsigned NOT NULL COMMENT '商品ID',
  `sku_id` bigint unsigned NOT NULL COMMENT 'SKU ID',
  
  -- 商品信息快照
  `product_name` varchar(200) NOT NULL COMMENT '商品名称',
  `sku_name` varchar(200) DEFAULT NULL COMMENT 'SKU名称',
  `product_image` varchar(500) DEFAULT NULL COMMENT '商品图片',
  `spec_values` json DEFAULT NULL COMMENT '规格值',
  
  -- 价格和数量
  `unit_price` decimal(10,2) NOT NULL COMMENT '单价',
  `quantity` int NOT NULL COMMENT '数量',
  `total_price` decimal(10,2) NOT NULL COMMENT '总价',
  
  -- 其他
  `weight` decimal(10,2) DEFAULT '0.00' COMMENT '重量(kg)',
  
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_sku_id` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单项表';
```

### 订单地址表 (mall_order_addresses)

```sql
CREATE TABLE `mall_order_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL COMMENT '订单ID',
  `consignee` varchar(50) NOT NULL COMMENT '收货人',
  `mobile` varchar(20) NOT NULL COMMENT '手机号',
  `province` varchar(50) NOT NULL COMMENT '省份',
  `city` varchar(50) NOT NULL COMMENT '城市',
  `district` varchar(50) NOT NULL COMMENT '区县',
  `address` varchar(200) NOT NULL COMMENT '详细地址',
  `zip_code` varchar(10) DEFAULT NULL COMMENT '邮编',
  
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单地址表';
```

## 事件驱动

### 订单创建事件

```php
namespace App\Domain\Order\Event;

class OrderCreatedEvent
{
    public function __construct(
        public OrderEntity $order
    ) {}
}
```

### 事件监听器

```php
namespace App\Domain\Order\Listener;

use App\Domain\Order\Event\OrderCreatedEvent;

class OrderStatusNotifyListener
{
    public function handle(OrderCreatedEvent $event): void
    {
        // 发送订单创建通知
        $this->notificationService->send(
            $event->order->memberId,
            '订单创建成功',
            "您的订单 {$event->order->orderNo} 已创建成功"
        );
    }
}
```

## 下一步

- [库存管理](/core/stock-management) - 了解库存管理实现
- [支付系统](/core/payment) - 了解支付系统实现
- [API 接口](/api/admin) - 查看订单相关 API
