# 设计模式

本系统使用了多种设计模式来提高代码的可维护性和可扩展性。

## 使用的设计模式

| 模式 | 应用场景 | 位置 |
|------|---------|------|
| Repository | 数据访问抽象 | Domain/Repository |
| Strategy | 订单类型处理 | Domain/Order/Strategy |
| Factory | 策略创建 | Domain/Order/Factory |
| CQRS | 读写分离 | Application/Service |
| Event | 事件驱动 | Domain/Event |
| Assembler | 数据转换 | Application/Assembler |
| Singleton | 单例服务 | Infrastructure/Service |

## Repository 模式

### 目的

将数据访问逻辑与业务逻辑分离，提供统一的数据访问接口。

### 实现

```php
// 定义接口（领域层）
namespace App\Domain\Product\Repository;

interface ProductRepository
{
    public function find(int $id): ?ProductEntity;
    public function save(ProductEntity $entity): ProductEntity;
    public function delete(int $id): bool;
    public function page(array $params): array;
}

// 实现接口（基础设施层）
namespace App\Infrastructure\Repository;

class ProductRepository implements ProductRepositoryInterface
{
    public function find(int $id): ?ProductEntity
    {
        $model = Product::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function save(ProductEntity $entity): ProductEntity
    {
        // 数据库操作
    }
}
```

### 优势

- 业务逻辑不依赖具体的数据访问实现
- 易于切换数据源（MySQL、MongoDB 等）
- 便于单元测试（可以 Mock Repository）

## Strategy 模式

### 目的

定义一系列算法，将每个算法封装起来，使它们可以互相替换。

### 实现

```php
// 策略接口
namespace App\Domain\Order\Strategy;

interface OrderTypeStrategyInterface
{
    public function type(): string;
    public function validate(OrderEntity $entity): void;
    public function buildDraft(array $data): OrderEntity;
    public function postCreate(OrderEntity $entity): void;
}

// 普通订单策略
class NormalOrderStrategy implements OrderTypeStrategyInterface
{
    public function type(): string
    {
        return OrderType::NORMAL->value;
    }

    public function validate(OrderEntity $entity): void
    {
        // 验证逻辑
    }

    public function buildDraft(array $data): OrderEntity
    {
        // 构建订单
    }

    public function postCreate(OrderEntity $entity): void
    {
        // 后置处理
    }
}

// 秒杀订单策略
class SeckillOrderStrategy implements OrderTypeStrategyInterface
{
    public function type(): string
    {
        return OrderType::SECKILL->value;
    }

    public function validate(OrderEntity $entity): void
    {
        // 秒杀特定验证
    }

    // ...
}
```

### 策略工厂

```php
namespace App\Domain\Order\Factory;

class OrderTypeStrategyFactory
{
    private array $strategies = [];

    public function __construct(
        NormalOrderStrategy $normalStrategy,
        SeckillOrderStrategy $seckillStrategy,
        GroupBuyOrderStrategy $groupBuyStrategy
    ) {
        $this->strategies = [
            $normalStrategy->type() => $normalStrategy,
            $seckillStrategy->type() => $seckillStrategy,
            $groupBuyStrategy->type() => $groupBuyStrategy,
        ];
    }

    public function make(string $type): OrderTypeStrategyInterface
    {
        if (!isset($this->strategies[$type])) {
            throw new \InvalidArgumentException("Unknown order type: {$type}");
        }

        return $this->strategies[$type];
    }
}
```

### 使用

```php
// 在订单服务中使用
public function submit(OrderEntity $entity): OrderEntity
{
    // 获取策略
    $strategy = $this->strategyFactory->make($entity->orderType);
    
    // 使用策略验证
    $strategy->validate($entity);
    
    // 保存订单
    $order = $this->repository->save($entity);
    
    // 使用策略后置处理
    $strategy->postCreate($order);
    
    return $order;
}
```

### 优势

- 易于扩展新的订单类型
- 避免大量的 if-else 判断
- 每种订单类型的逻辑独立

## Factory 模式

### 目的

提供创建对象的接口，让子类决定实例化哪个类。

### 实现

```php
namespace App\Domain\Order\Factory;

class OrderFactory
{
    public function createFromRequest(array $data): OrderEntity
    {
        $entity = new OrderEntity();
        $entity->orderNo = $this->generateOrderNo();
        $entity->memberId = $data['member_id'];
        $entity->orderType = $data['order_type'];
        
        // 创建订单项
        foreach ($data['items'] as $itemData) {
            $entity->items[] = $this->createOrderItem($itemData);
        }
        
        // 创建地址
        if (isset($data['address'])) {
            $entity->address = $this->createAddress($data['address']);
        }
        
        return $entity;
    }

    private function createOrderItem(array $data): OrderItemEntity
    {
        return new OrderItemEntity([
            'productId' => $data['product_id'],
            'skuId' => $data['sku_id'],
            'quantity' => $data['quantity'],
            // ...
        ]);
    }

    private function createAddress(array $data): OrderAddressValue
    {
        return new OrderAddressValue(
            $data['consignee'],
            $data['mobile'],
            $data['province'],
            $data['city'],
            $data['district'],
            $data['address']
        );
    }

    private function generateOrderNo(): string
    {
        return 'ORD' . date('YmdHis') . rand(1000, 9999);
    }
}
```

### 优势

- 封装对象创建逻辑
- 统一对象创建方式
- 便于维护和修改

## CQRS 模式

### 目的

将读操作和写操作分离，优化性能和可维护性。

### 实现

```php
// Command Service（写操作）
namespace App\Application\Product\Service;

class ProductCommandService
{
    public function create(array $data): array
    {
        // 写操作
    }

    public function update(int $id, array $data): array
    {
        // 写操作
    }

    public function delete(int $id): bool
    {
        // 写操作
    }
}

// Query Service（读操作）
class ProductQueryService
{
    public function page(array $params): array
    {
        // 读操作
    }

    public function find(int $id): ?array
    {
        // 读操作
    }

    public function stats(): array
    {
        // 读操作
    }
}
```

### 优势

- 读写分离，性能优化
- 可以针对读写使用不同的数据源
- 代码职责更清晰

## Event 模式

### 目的

实现事件驱动架构，解耦业务逻辑。

### 实现

```php
// 定义事件
namespace App\Domain\Order\Event;

class OrderCreatedEvent
{
    public function __construct(
        public OrderEntity $order
    ) {}
}

// 定义监听器
namespace App\Domain\Order\Listener;

class OrderStatusNotifyListener
{
    public function handle(OrderCreatedEvent $event): void
    {
        // 发送通知
        $this->notificationService->send(
            $event->order->memberId,
            '订单创建成功',
            "您的订单 {$event->order->orderNo} 已创建"
        );
    }
}

class OrderStockListener
{
    public function handle(OrderCreatedEvent $event): void
    {
        // 同步库存到数据库
        foreach ($event->order->items as $item) {
            $this->skuRepository->decrementStock(
                $item->skuId,
                $item->quantity
            );
        }
    }
}

// 注册监听器
// config/autoload/listeners.php
return [
    OrderCreatedEvent::class => [
        OrderStatusNotifyListener::class,
        OrderStockListener::class,
    ],
];

// 发布事件
event(new OrderCreatedEvent($order));
```

### 优势

- 解耦业务逻辑
- 支持异步处理
- 易于扩展新的监听器

## Assembler 模式

### 目的

负责数据转换和组装，将不同层次的数据模型进行转换。

### 实现

```php
namespace App\Application\Product\Assembler;

class ProductAssembler
{
    /**
     * 请求数据转实体
     */
    public function toCreateEntity(array $data): ProductEntity
    {
        return new ProductEntity([
            'name' => $data['name'],
            'categoryId' => $data['category_id'],
            'brandId' => $data['brand_id'] ?? null,
            'description' => $data['description'] ?? '',
            'skus' => array_map(
                fn($sku) => $this->toSkuEntity($sku),
                $data['skus']
            ),
        ]);
    }

    /**
     * 实体转响应数据
     */
    public function toResponse(ProductEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'category_id' => $entity->categoryId,
            'brand_id' => $entity->brandId,
            'description' => $entity->description,
            'min_price' => $entity->minPrice,
            'max_price' => $entity->maxPrice,
            'skus' => array_map(
                fn($sku) => $this->skuToArray($sku),
                $entity->skus
            ),
        ];
    }

    private function toSkuEntity(array $data): ProductSkuEntity
    {
        return new ProductSkuEntity([
            'skuName' => $data['sku_name'],
            'salePrice' => $data['sale_price'],
            'stock' => $data['stock'],
            // ...
        ]);
    }

    private function skuToArray(ProductSkuEntity $sku): array
    {
        return [
            'id' => $sku->id,
            'sku_name' => $sku->skuName,
            'sale_price' => $sku->salePrice,
            'stock' => $sku->stock,
        ];
    }
}
```

### 优势

- 统一数据转换逻辑
- 避免在多处重复转换代码
- 易于维护和修改

## Singleton 模式

### 目的

确保一个类只有一个实例，并提供全局访问点。

### 实现

Hyperf 框架通过依赖注入容器自动实现单例：

```php
namespace App\Infrastructure\Service;

use Hyperf\Di\Annotation\Inject;

#[Singleton]
class CacheService
{
    #[Inject]
    private Redis $redis;

    public function get(string $key): mixed
    {
        return $this->redis->get($key);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->redis->setex($key, $ttl, serialize($value));
    }
}
```

### 优势

- 节省资源
- 全局访问
- 避免重复创建

## 设计原则

### SOLID 原则

#### 1. 单一职责原则 (SRP)

每个类只负责一个功能。

```php
// ✅ 好的设计
class ProductService
{
    // 只负责产品业务逻辑
}

class ProductRepository
{
    // 只负责数据访问
}

// ❌ 不好的设计
class ProductService
{
    // 既有业务逻辑，又有数据访问
}
```

#### 2. 开闭原则 (OCP)

对扩展开放，对修改关闭。

```php
// ✅ 使用策略模式，扩展新订单类型无需修改现有代码
class OrderService
{
    public function submit(OrderEntity $entity): OrderEntity
    {
        $strategy = $this->strategyFactory->make($entity->orderType);
        $strategy->validate($entity);
        // ...
    }
}
```

#### 3. 里氏替换原则 (LSP)

子类可以替换父类。

```php
// 所有策略都实现相同接口，可以互相替换
interface OrderTypeStrategyInterface
{
    public function validate(OrderEntity $entity): void;
}
```

#### 4. 接口隔离原则 (ISP)

使用多个专门的接口，而不是单一的总接口。

```php
// ✅ 分离读写接口
interface ProductCommandService { }
interface ProductQueryService { }

// ❌ 单一接口包含所有方法
interface ProductService { }
```

#### 5. 依赖倒置原则 (DIP)

依赖抽象，不依赖具体实现。

```php
// ✅ 依赖接口
class ProductService
{
    public function __construct(
        private ProductRepository $repository  // 接口
    ) {}
}

// ❌ 依赖具体实现
class ProductService
{
    public function __construct(
        private ProductModel $model  // 具体类
    ) {}
}
```

## 下一步

- [DDD 架构](/architecture/ddd) - 了解 DDD 架构
- [分层设计](/architecture/layers) - 了解分层设计
