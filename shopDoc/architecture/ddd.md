# DDD 架构

本系统采用 **领域驱动设计（Domain-Driven Design, DDD）** 架构，将业务逻辑清晰地组织为四个层次。

## 什么是 DDD？

领域驱动设计是一种软件开发方法论，强调：

- **以业务领域为核心**: 将业务逻辑放在领域层
- **统一语言**: 开发人员和业务人员使用相同的术语
- **分层架构**: 清晰的职责划分
- **领域模型**: 用代码表达业务规则

## 四层架构

```
┌─────────────────────────────────────┐
│      Interface Layer (接口层)        │
│   HTTP Controllers, Middleware      │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│    Application Layer (应用层)        │
│  Command/Query Service, Assembler   │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│      Domain Layer (领域层)           │
│  Entity, Service, Repository        │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  Infrastructure Layer (基础设施层)    │
│   Model, Database, Cache, Queue     │
└─────────────────────────────────────┘
```

## Interface Layer (接口层)

### 职责

- 处理 HTTP 请求和响应
- 参数验证和转换
- 权限验证
- 操作日志记录
- 异常处理

### 目录结构

```
app/Interface/
├── Admin/              # 后台管理接口
│   ├── Controller/    # 控制器
│   ├── Middleware/    # 中间件
│   ├── Request/       # 请求验证
│   └── Vo/            # 视图对象
├── Api/               # 前端 API 接口
│   ├── Controller/
│   ├── Middleware/
│   └── Request/
└── Common/            # 公共组件
    ├── Controller/
    ├── Middleware/
    ├── Result.php     # 统一响应
    └── ResultCode.php # 响应码
```

### 示例代码

```php
namespace App\Interface\Admin\Controller\Product;

use App\Application\Product\Service\ProductCommandService;
use App\Application\Product\Service\ProductQueryService;
use App\Interface\Admin\Request\Product\ProductCreateRequest;
use App\Interface\Common\Result;

class ProductController
{
    public function __construct(
        private ProductCommandService $commandService,
        private ProductQueryService $queryService
    ) {}

    /**
     * 产品列表
     */
    public function list(ProductListRequest $request): Result
    {
        $data = $this->queryService->page($request->validated());
        return Result::success($data);
    }

    /**
     * 创建产品
     */
    public function create(ProductCreateRequest $request): Result
    {
        $product = $this->commandService->create($request->validated());
        return Result::success($product);
    }
}
```

## Application Layer (应用层)

### 职责

- 协调领域服务
- 实现业务流程编排
- 事务管理
- 事件分发
- 数据转换（Assembler）

### CQRS 模式

应用层采用 **CQRS（Command Query Responsibility Segregation）** 模式，将读写操作分离：

- **CommandService**: 处理写操作（创建、更新、删除）
- **QueryService**: 处理读操作（查询、统计）

### 目录结构

```
app/Application/
├── Order/
│   ├── Assembler/
│   │   └── OrderAssembler.php
│   └── Service/
│       ├── OrderCommandService.php
│       └── OrderQueryService.php
├── Product/
│   ├── Assembler/
│   │   └── ProductAssembler.php
│   └── Service/
│       ├── ProductCommandService.php
│       └── ProductQueryService.php
└── ...
```

### 示例代码

#### CommandService (写操作)

```php
namespace App\Application\Product\Service;

use App\Application\Product\Assembler\ProductAssembler;
use App\Domain\Product\Service\ProductService;

class ProductCommandService
{
    public function __construct(
        private ProductService $productService,
        private ProductAssembler $assembler
    ) {}

    /**
     * 创建产品
     */
    public function create(array $data): array
    {
        // 转换为领域实体
        $entity = $this->assembler->toCreateEntity($data);
        
        // 调用领域服务
        $product = $this->productService->create($entity);
        
        // 返回结果
        return $product->toArray();
    }

    /**
     * 更新产品
     */
    public function update(int $id, array $data): array
    {
        $entity = $this->assembler->toUpdateEntity($id, $data);
        $product = $this->productService->update($entity);
        return $product->toArray();
    }
}
```

#### QueryService (读操作)

```php
namespace App\Application\Product\Service;

use App\Domain\Product\Repository\ProductRepository;

class ProductQueryService
{
    public function __construct(
        private ProductRepository $repository
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
        $product = $this->repository->find($id);
        return $product?->toArray();
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

#### Assembler (数据转换)

```php
namespace App\Application\Product\Assembler;

use App\Domain\Product\Entity\ProductEntity;

class ProductAssembler
{
    /**
     * 转换为创建实体
     */
    public function toCreateEntity(array $data): ProductEntity
    {
        return new ProductEntity([
            'name' => $data['name'],
            'categoryId' => $data['category_id'],
            'brandId' => $data['brand_id'],
            'description' => $data['description'] ?? '',
            'skus' => $data['skus'] ?? [],
            // ...
        ]);
    }

    /**
     * 转换为更新实体
     */
    public function toUpdateEntity(int $id, array $data): ProductEntity
    {
        $entity = $this->toCreateEntity($data);
        $entity->id = $id;
        return $entity;
    }
}
```

## Domain Layer (领域层)

### 职责

- 封装核心业务逻辑
- 维护业务规则和约束
- 发布领域事件
- 定义领域模型

### 目录结构

```
app/Domain/
├── Order/
│   ├── Entity/           # 实体
│   │   ├── OrderEntity.php
│   │   └── OrderItemEntity.php
│   ├── Service/          # 领域服务
│   │   ├── OrderService.php
│   │   └── OrderStockService.php
│   ├── Repository/       # 仓储接口
│   │   └── OrderRepository.php
│   ├── ValueObject/      # 值对象
│   │   ├── OrderAddressValue.php
│   │   └── OrderPriceValue.php
│   ├── Event/            # 领域事件
│   │   ├── OrderCreatedEvent.php
│   │   └── OrderShippedEvent.php
│   ├── Enum/             # 枚举
│   │   ├── OrderStatus.php
│   │   └── PaymentStatus.php
│   ├── Strategy/         # 策略
│   │   └── OrderTypeStrategyInterface.php
│   └── Factory/          # 工厂
│       └── OrderTypeStrategyFactory.php
└── ...
```

### 核心概念

#### Entity (实体)

实体是具有唯一标识的领域对象。

```php
namespace App\Domain\Order\Entity;

class OrderEntity
{
    public ?int $id = null;
    public string $orderNo;
    public int $memberId;
    public string $orderType;
    public string $status;
    public float $totalAmount;
    public array $items = [];
    public ?OrderAddressValue $address = null;

    /**
     * 计算订单总金额
     */
    public function calculateTotalAmount(): float
    {
        $goodsAmount = array_sum(array_map(
            fn($item) => $item->totalPrice,
            $this->items
        ));
        
        return $goodsAmount + $this->shippingFee - $this->discountAmount;
    }

    /**
     * 验证订单
     */
    public function validate(): void
    {
        if (empty($this->items)) {
            throw new \InvalidArgumentException('订单项不能为空');
        }

        if ($this->totalAmount <= 0) {
            throw new \InvalidArgumentException('订单金额必须大于0');
        }
    }
}
```

#### ValueObject (值对象)

值对象是没有唯一标识的领域对象，通过属性值来区分。

```php
namespace App\Domain\Order\ValueObject;

class OrderAddressValue
{
    public function __construct(
        public string $consignee,
        public string $mobile,
        public string $province,
        public string $city,
        public string $district,
        public string $address,
        public ?string $zipCode = null
    ) {}

    /**
     * 获取完整地址
     */
    public function getFullAddress(): string
    {
        return $this->province . $this->city . $this->district . $this->address;
    }
}
```

#### Service (领域服务)

领域服务封装不属于任何实体的业务逻辑。

```php
namespace App\Domain\Order\Service;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\Order\Event\OrderCreatedEvent;

class OrderService
{
    public function __construct(
        private OrderRepository $repository,
        private OrderStockService $stockService
    ) {}

    /**
     * 提交订单
     */
    public function submit(OrderEntity $entity): OrderEntity
    {
        // 验证订单
        $entity->validate();

        // 锁定库存
        $locks = $this->stockService->acquireLocks($entity->items);

        try {
            // 扣减库存
            $this->stockService->reserve($entity->items);

            // 保存订单
            $order = $this->repository->save($entity);

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
}
```

#### Repository (仓储接口)

仓储接口定义数据访问方法，具体实现在基础设施层。

```php
namespace App\Domain\Order\Repository;

use App\Domain\Order\Entity\OrderEntity;

interface OrderRepository
{
    public function find(int $id): ?OrderEntity;
    
    public function save(OrderEntity $entity): OrderEntity;
    
    public function page(array $params): array;
    
    public function ship(int $id, array $data): bool;
    
    public function cancel(int $id, string $reason): bool;
}
```

#### Event (领域事件)

领域事件表示领域中发生的重要业务事件。

```php
namespace App\Domain\Order\Event;

use App\Domain\Order\Entity\OrderEntity;

class OrderCreatedEvent
{
    public function __construct(
        public OrderEntity $order
    ) {}
}
```

#### Enum (枚举)

枚举定义业务状态和类型。

```php
namespace App\Domain\Order\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING => '待支付',
            self::PAID => '已支付',
            self::SHIPPED => '已发货',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
            self::REFUNDED => '已退款',
        };
    }
}
```

## Infrastructure Layer (基础设施层)

### 职责

- 数据持久化（数据库操作）
- 缓存管理
- 外部服务集成
- 技术实现细节

### 目录结构

```
app/Infrastructure/
├── Model/              # Eloquent 模型
│   ├── Order/
│   │   ├── Order.php
│   │   └── OrderItem.php
│   └── Product/
│       ├── Product.php
│       └── ProductSku.php
├── Library/            # 工具库
│   ├── Lua/           # Lua 脚本
│   └── DataPermission/ # 数据权限
├── Service/            # 基础设施服务
│   └── Wechat/
├── Exception/          # 异常处理
└── Traits/             # 可复用特性
```

### Repository 实现

```php
namespace App\Infrastructure\Repository;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Repository\OrderRepository as OrderRepositoryInterface;
use App\Infrastructure\Model\Order\Order;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * 查找订单
     */
    public function find(int $id): ?OrderEntity
    {
        $model = Order::with(['items', 'address'])->find($id);
        
        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * 保存订单
     */
    public function save(OrderEntity $entity): OrderEntity
    {
        $model = new Order();
        $model->fill($entity->toArray());
        $model->save();

        // 保存订单项
        foreach ($entity->items as $item) {
            $model->items()->create($item->toArray());
        }

        return $this->toEntity($model);
    }

    /**
     * 模型转实体
     */
    private function toEntity(Order $model): OrderEntity
    {
        return new OrderEntity($model->toArray());
    }
}
```

## 依赖关系

```
Interface Layer
    ↓ 依赖
Application Layer
    ↓ 依赖
Domain Layer
    ↑ 实现
Infrastructure Layer
```

**依赖规则**：

- 外层依赖内层
- 内层不依赖外层
- 领域层定义接口，基础设施层实现接口

## 优势

### 1. 清晰的职责划分

每一层都有明确的职责，代码组织清晰，易于理解和维护。

### 2. 业务逻辑集中

核心业务逻辑集中在领域层，不会分散在各个层次。

### 3. 易于测试

各层独立，可以单独进行单元测试。

### 4. 易于扩展

新增功能只需在相应层次添加代码，不影响其他层次。

### 5. 技术无关

领域层不依赖具体技术实现，可以轻松切换数据库、缓存等技术。

## 下一步

- [分层设计](/architecture/layers) - 详细了解各层设计
- [设计模式](/architecture/patterns) - 了解使用的设计模式
- [订单设计](/core/order-design) - 查看订单模块的 DDD 实现
