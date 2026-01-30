# 分层设计

本文档详细介绍系统的分层架构设计。

## 分层概览

系统采用经典的四层架构：

```
┌─────────────────────────────────────┐
│      Interface Layer (接口层)        │  ← HTTP 请求入口
│   Controllers, Middleware, Request  │
└─────────────────────────────────────┘
              ↓ 调用
┌─────────────────────────────────────┐
│    Application Layer (应用层)        │  ← 业务流程编排
│  CommandService, QueryService       │
└─────────────────────────────────────┘
              ↓ 调用
┌─────────────────────────────────────┐
│      Domain Layer (领域层)           │  ← 核心业务逻辑
│  Entity, Service, Repository        │
└─────────────────────────────────────┘
              ↑ 实现
┌─────────────────────────────────────┐
│  Infrastructure Layer (基础设施层)    │  ← 技术实现
│   Model, Database, Cache, Queue     │
└─────────────────────────────────────┘
```

## Interface Layer (接口层)

### 职责

- 接收和处理 HTTP 请求
- 参数验证和转换
- 权限验证
- 操作日志记录
- 返回统一格式的响应

### 组件

#### Controller (控制器)

负责处理 HTTP 请求，调用应用层服务。

```php
namespace App\Interface\Admin\Controller\Product;

class ProductController
{
    public function __construct(
        private ProductCommandService $commandService,
        private ProductQueryService $queryService
    ) {}

    public function list(ProductListRequest $request): Result
    {
        $data = $this->queryService->page($request->validated());
        return Result::success($data);
    }

    public function create(ProductCreateRequest $request): Result
    {
        $product = $this->commandService->create($request->validated());
        return Result::success($product);
    }
}
```

#### Middleware (中间件)

处理请求前后的逻辑。

```php
// 认证中间件
class AccessTokenMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');
        
        if (!$token) {
            throw new UnauthorizedException('未授权');
        }

        // 验证 Token
        $user = $this->jwtService->verify($token);
        
        // 设置当前用户
        Context::set('user', $user);

        return $handler->handle($request);
    }
}

// 权限中间件
class PermissionMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = Context::get('user');
        $route = $request->getAttribute('route');

        if (!$this->permissionService->check($user, $route)) {
            throw new ForbiddenException('无权限访问');
        }

        return $handler->handle($request);
    }
}

// 操作日志中间件
class OperationMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // 记录操作日志
        $this->logService->record([
            'user_id' => Context::get('user')->id,
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'params' => $request->getParsedBody(),
        ]);

        return $response;
    }
}
```

#### Request (请求验证)

验证请求参数。

```php
namespace App\Interface\Admin\Request\Product;

class ProductCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'category_id' => 'required|integer|exists:mall_categories,id',
            'brand_id' => 'nullable|integer|exists:mall_brands,id',
            'description' => 'nullable|string',
            'skus' => 'required|array|min:1',
            'skus.*.sku_name' => 'required|string',
            'skus.*.sale_price' => 'required|numeric|min:0',
            'skus.*.stock' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '商品名称不能为空',
            'category_id.required' => '请选择分类',
            'skus.required' => '请添加至少一个SKU',
        ];
    }
}
```

## Application Layer (应用层)

### 职责

- 协调领域服务
- 实现业务流程编排
- 事务管理
- 事件分发
- 数据转换

### CQRS 模式

#### CommandService (写操作)

```php
namespace App\Application\Product\Service;

class ProductCommandService
{
    public function __construct(
        private ProductService $productService,
        private ProductAssembler $assembler
    ) {}

    public function create(array $data): array
    {
        // 转换为领域实体
        $entity = $this->assembler->toCreateEntity($data);
        
        // 调用领域服务
        $product = $this->productService->create($entity);
        
        return $product->toArray();
    }

    public function update(int $id, array $data): array
    {
        $entity = $this->assembler->toUpdateEntity($id, $data);
        $product = $this->productService->update($entity);
        return $product->toArray();
    }

    public function delete(int $id): bool
    {
        return $this->productService->delete($id);
    }
}
```

#### QueryService (读操作)

```php
namespace App\Application\Product\Service;

class ProductQueryService
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function page(array $params): array
    {
        return $this->repository->page($params);
    }

    public function find(int $id): ?array
    {
        $product = $this->repository->find($id);
        return $product?->toArray();
    }

    public function stats(): array
    {
        return $this->repository->stats();
    }
}
```

#### Assembler (数据转换)

```php
namespace App\Application\Product\Assembler;

class ProductAssembler
{
    public function toCreateEntity(array $data): ProductEntity
    {
        return new ProductEntity([
            'name' => $data['name'],
            'categoryId' => $data['category_id'],
            'brandId' => $data['brand_id'] ?? null,
            'description' => $data['description'] ?? '',
            'skus' => array_map(
                fn($sku) => new ProductSkuEntity($sku),
                $data['skus']
            ),
        ]);
    }

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

### 组件

#### Entity (实体)

```php
namespace App\Domain\Product\Entity;

class ProductEntity
{
    public ?int $id = null;
    public string $name;
    public int $categoryId;
    public ?int $brandId = null;
    public string $description;
    public array $skus = [];
    public float $minPrice = 0;
    public float $maxPrice = 0;

    /**
     * 同步价格范围
     */
    public function syncPriceRange(): void
    {
        if (empty($this->skus)) {
            $this->minPrice = 0;
            $this->maxPrice = 0;
            return;
        }

        $prices = array_map(fn($sku) => $sku->salePrice, $this->skus);
        $this->minPrice = min($prices);
        $this->maxPrice = max($prices);
    }

    /**
     * 验证
     */
    public function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('商品名称不能为空');
        }

        if (empty($this->skus)) {
            throw new \InvalidArgumentException('请添加至少一个SKU');
        }
    }
}
```

#### Service (领域服务)

```php
namespace App\Domain\Product\Service;

class ProductService
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function create(ProductEntity $entity): ProductEntity
    {
        // 验证
        $entity->validate();

        // 同步价格范围
        $entity->syncPriceRange();

        // 保存
        $product = $this->repository->save($entity);

        // 发布事件
        event(new ProductCreated($product));

        return $product;
    }

    public function update(ProductEntity $entity): ProductEntity
    {
        $entity->validate();
        $entity->syncPriceRange();
        
        $product = $this->repository->save($entity);
        event(new ProductUpdated($product));

        return $product;
    }

    public function delete(int $id): bool
    {
        $product = $this->repository->find($id);
        
        if (!$product) {
            throw new \RuntimeException('商品不存在');
        }

        $result = $this->repository->delete($id);
        
        if ($result) {
            event(new ProductDeleted($product));
        }

        return $result;
    }
}
```

#### Repository (仓储接口)

```php
namespace App\Domain\Product\Repository;

interface ProductRepository
{
    public function find(int $id): ?ProductEntity;
    
    public function save(ProductEntity $entity): ProductEntity;
    
    public function delete(int $id): bool;
    
    public function page(array $params): array;
    
    public function stats(): array;
}
```

## Infrastructure Layer (基础设施层)

### 职责

- 数据持久化
- 缓存管理
- 外部服务集成
- 技术实现细节

### 组件

#### Model (数据模型)

```php
namespace App\Infrastructure\Model\Product;

class Product extends Model
{
    protected $table = 'mall_products';

    protected $fillable = [
        'product_code',
        'category_id',
        'brand_id',
        'name',
        'description',
        'min_price',
        'max_price',
        'status',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'attributes' => 'array',
    ];

    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
```

#### Repository Implementation (仓储实现)

```php
namespace App\Infrastructure\Repository;

use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Repository\ProductRepository as ProductRepositoryInterface;
use App\Infrastructure\Model\Product\Product;

class ProductRepository implements ProductRepositoryInterface
{
    public function find(int $id): ?ProductEntity
    {
        $model = Product::with(['skus', 'attributes'])->find($id);
        
        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(ProductEntity $entity): ProductEntity
    {
        if ($entity->id) {
            $model = Product::find($entity->id);
        } else {
            $model = new Product();
        }

        $model->fill($entity->toArray());
        $model->save();

        // 保存 SKU
        $model->skus()->delete();
        foreach ($entity->skus as $sku) {
            $model->skus()->create($sku->toArray());
        }

        return $this->toEntity($model->fresh(['skus']));
    }

    public function delete(int $id): bool
    {
        $model = Product::find($id);
        
        if (!$model) {
            return false;
        }

        // 删除关联数据
        $model->skus()->delete();
        $model->attributes()->delete();
        $model->gallery()->delete();

        return $model->delete();
    }

    public function page(array $params): array
    {
        $query = Product::query();

        // 筛选条件
        if (!empty($params['keyword'])) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // 排序
        $orderBy = $params['order_by'] ?? 'created_at';
        $orderDirection = $params['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // 分页
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
            'total_pages' => $paginator->lastPage(),
        ];
    }

    private function toEntity(Product $model): ProductEntity
    {
        return new ProductEntity($model->toArray());
    }
}
```

## 依赖关系

### 依赖方向

```
Interface → Application → Domain ← Infrastructure
```

### 依赖注入

使用 Hyperf 的依赖注入容器：

```php
// config/autoload/dependencies.php
return [
    // 仓储接口绑定实现
    \App\Domain\Product\Repository\ProductRepository::class => 
        \App\Infrastructure\Repository\ProductRepository::class,
    
    \App\Domain\Order\Repository\OrderRepository::class => 
        \App\Infrastructure\Repository\OrderRepository::class,
];
```

## 下一步

- [DDD 架构](/architecture/ddd) - 了解 DDD 架构
- [设计模式](/architecture/patterns) - 了解使用的设计模式
