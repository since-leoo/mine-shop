# 秒杀活动DDD改造总结

## 改造概述

按照DDD架构规范，对秒杀活动模块进行了全面改造，主要包括：

1. **正确理解业务模型**：活动是场次的容器，时间在场次中定义
2. **删除Assembler**：按DDD规范使用DTO + Contract
3. **增强实体业务逻辑**：添加业务规则验证和行为方法
4. **引入值对象**：封装活动规则等复杂数据
5. **添加领域事件**：活动创建、更新、删除、状态变更等
6. **集成SystemSetting**：通过配置服务管理全局配置
7. **优化请求验证**：更严格的业务规则验证

## 业务模型

### 三层结构

```
秒杀活动（Activity）
  └── 秒杀场次（Session）- 有开始/结束时间
        └── 场次商品（SessionProduct）- 关联具体商品
```

### 关键理解

- **活动（Activity）**：只是容器，定义全局规则，无时间信息
- **场次（Session）**：有具体的开始和结束时间
- **商品（SessionProduct）**：场次中的具体商品及其秒杀价格

## 文件结构

### 新增文件

```
app/Domain/Seckill/
├── Contract/
│   └── SeckillActivityInput.php          # 输入契约接口
├── ValueObject/
│   ├── ActivityRules.php                 # 活动规则值对象
│   └── ActivityStatistics.php            # 活动统计值对象
├── Event/
│   ├── SeckillActivityCreatedEvent.php   # 活动创建事件
│   ├── SeckillActivityUpdatedEvent.php   # 活动更新事件
│   ├── SeckillActivityDeletedEvent.php   # 活动删除事件
│   ├── SeckillActivityEnabledEvent.php   # 活动启用/禁用事件
│   └── SeckillActivityStatusChangedEvent.php # 状态变更事件
├── Listener/
│   └── SeckillActivityCacheListener.php  # 缓存监听器
├── Mapper/
│   └── SeckillActivityMapper.php         # Model到Entity映射
├── Service/
│   └── SeckillConfigService.php          # 配置服务
└── Trait/
    └── SeckillActivityEntityTrait.php    # 实体转换Trait

app/Interface/Admin/DTO/Seckill/
└── SeckillActivityDto.php                # 数据传输对象

config/
└── seckill.php                           # 秒杀配置定义
```

### 删除文件

```
app/Application/Mapper/
└── SeckillActivityAssembler.php          # 已删除，改用DTO

app/Domain/Seckill/ValueObject/
└── ActivityPeriod.php                    # 已删除，活动无时间
```

## 核心改造点

### 1. 实体（Entity）

**改造前**：简单的数据容器
```php
public function __construct(
    private int $id = 0,
    private ?string $title = null,
    // ...
) {}
```

**改造后**：富领域模型
```php
final class SeckillActivityEntity
{
    // 私有构造函数
    private function __construct() {}
    
    // 工厂方法
    public static function create(string $title, ...): self
    
    // 重建方法
    public static function reconstitute(...): self
    
    // 行为方法
    public function create(SeckillActivityInput $dto): self
    public function update(SeckillActivityInput $dto): self
    
    // 业务规则
    public function canBeEnabled(): bool
    public function canBeEdited(): bool
    public function canBeDeleted(): bool
    public function canBeCancelled(): bool
    
    // 状态转换
    public function cancel(): self
    public function start(): self
    public function end(): self
}
```

### 2. 值对象（ValueObject）

#### ActivityRules - 活动规则

```php
final class ActivityRules
{
    private readonly int $maxQuantityPerUser;
    private readonly int $minPurchaseQuantity;
    private readonly bool $requireMemberLevel;
    private readonly ?int $requiredMemberLevelId;
    private readonly bool $allowRefund;
    private readonly int $refundDeadlineHours;
    
    public function __construct(array $rules)
    {
        // 验证和初始化
        $this->validate();
    }
    
    public function canUserPurchase(int $quantity, ?int $userMemberLevelId = null): bool
    {
        // 业务逻辑
    }
    
    public static function default(): self
    {
        // 默认规则
    }
}
```

### 3. 领域事件（Event）

```php
// 活动创建事件
final class SeckillActivityCreatedEvent
{
    public function __construct(
        private readonly SeckillActivityEntity $activity,
        private readonly int $activityId
    ) {}
}

// 活动更新事件
final class SeckillActivityUpdatedEvent
{
    public function __construct(
        private readonly SeckillActivityEntity $activity,
        private readonly int $activityId,
        private readonly array $changedFields = []
    ) {}
}
```

### 4. 事件监听器（Listener）

```php
#[Listener]
final class SeckillActivityCacheListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            SeckillActivityCreatedEvent::class,
            SeckillActivityUpdatedEvent::class,
            SeckillActivityDeletedEvent::class,
            SeckillActivityEnabledEvent::class,
            SeckillActivityStatusChangedEvent::class,
        ];
    }
    
    public function process(object $event): void
    {
        // 根据事件类型清除相应缓存
    }
}
```

### 5. DTO + Contract

**Contract接口**：
```php
interface SeckillActivityInput
{
    public function getId(): int;
    public function getTitle(): ?string;
    public function getDescription(): ?string;
    public function getStatus(): ?string;
    public function getRules(): ?array;
    public function getRemark(): ?string;
}
```

**DTO实现**：
```php
final class SeckillActivityDto implements SeckillActivityInput
{
    public ?int $id = null;
    public ?string $title = null;
    // ... 其他字段
    
    // Getter方法实现Contract
}
```

**Request转换**：
```php
public function toDto(?int $id = null): SeckillActivityInput
{
    $params = $this->validated();
    $params['id'] = $id;
    return Mapper::map($params, new SeckillActivityDto());
}
```

### 6. 领域服务（Domain Service）

```php
final class SeckillActivityService
{
    public function create(SeckillActivityInput $dto): SeckillActivity
    {
        // 1. 通过Mapper获取新实体
        $entity = SeckillActivityMapper::getNewEntity();
        
        // 2. 调用实体的create行为方法
        $entity->create($dto);
        
        // 3. 持久化
        $activity = $this->repository->createFromEntity($entity);
        
        // 4. 触发领域事件
        $this->eventDispatcher->dispatch(
            new SeckillActivityCreatedEvent($entity, $entity->getId())
        );
        
        return $activity;
    }
    
    public function getEntity(int $id): SeckillActivityEntity
    {
        // 获取实体用于调用业务行为
    }
}
```

### 7. 应用服务（Application Service）

```php
final class SeckillActivityCommandService
{
    public function create(SeckillActivityInput $dto): SeckillActivity
    {
        // 事务管理
        return Db::transaction(fn () => $this->activityService->create($dto));
    }
}
```

### 8. 配置服务（Config Service）

```php
final class SeckillConfigService
{
    public function __construct(
        private readonly SystemSettingService $settingService
    ) {}
    
    public function getMaxQuantityPerUser(): int
    {
        return (int) $this->settingService->get('seckill_max_quantity_per_user', 999);
    }
    
    public function isRefundAllowed(): bool
    {
        return (bool) $this->settingService->get('seckill_allow_refund', false);
    }
    
    // ... 其他配置获取方法
}
```

## 数据流

### 创建流程

```
用户请求
  ↓
Controller 接收请求
  ↓
Request 验证数据
  ↓
Request::toDto() 转换为 DTO
  ↓
Controller 调用 CommandService
  ↓
CommandService 开启事务
  ↓
CommandService 调用 Domain Service
  ↓
Domain Service 获取新实体 (Mapper::getNewEntity)
  ↓
Entity::create(dto) 组装数据
  ↓
Repository::createFromEntity() 持久化
  ↓
EventDispatcher 发布领域事件
  ↓
CacheListener 清除缓存
  ↓
CommandService 提交事务
  ↓
Controller 返回响应
```

### 更新流程

```
用户请求
  ↓
Controller 接收请求
  ↓
Request 验证数据
  ↓
Request::toDto(id) 转换为 DTO
  ↓
Controller 调用 CommandService
  ↓
CommandService 开启事务
  ↓
CommandService 调用 Domain Service
  ↓
Domain Service 通过 Repository 获取 Model
  ↓
Mapper::fromModel() 转换为 Entity
  ↓
Entity::update(dto) 更新数据
  ↓
Repository::updateFromEntity() 持久化
  ↓
EventDispatcher 发布领域事件
  ↓
CacheListener 清除缓存
  ↓
CommandService 提交事务
  ↓
Controller 返回响应
```

## 配置集成

### 配置定义（config/seckill.php）

```php
return [
    'label' => '秒杀活动',
    'description' => '秒杀活动相关配置',
    'settings' => [
        'seckill_max_quantity_per_user' => [
            'label' => '每人最大购买数量',
            'type' => 'integer',
            'default' => 999,
            // ...
        ],
        // ... 其他配置
    ],
];
```

### 配置使用

```php
// 在值对象中使用
private function validate(): void
{
    $maxLimit = 999;
    try {
        $configService = ApplicationContext::getContainer()
            ->get(SeckillConfigService::class);
        $maxLimit = $configService->getMaxQuantityPerUser();
    } catch (\Throwable $e) {
        // 使用默认值
    }
    
    if ($this->maxQuantityPerUser > $maxLimit) {
        throw new \InvalidArgumentException("超过限制{$maxLimit}");
    }
}
```

## 业务规则

### 活动状态转换规则

```php
// 启用检查
public function canBeEnabled(): bool
{
    // 已取消或已结束的活动不能启用
    return !in_array($this->status, [
        SeckillStatus::CANCELLED,
        SeckillStatus::ENDED
    ]);
}

// 编辑检查
public function canBeEdited(): bool
{
    // 进行中或已结束的活动不能编辑
    return !in_array($this->status, [
        SeckillStatus::ACTIVE,
        SeckillStatus::ENDED
    ]);
}

// 删除检查
public function canBeDeleted(): bool
{
    // 进行中的活动不能删除
    return $this->status !== SeckillStatus::ACTIVE;
}
```

### 活动规则验证

```php
// 购买数量验证
public function canUserPurchase(int $quantity, ?int $userMemberLevelId = null): bool
{
    // 检查数量范围
    if ($quantity < $this->minPurchaseQuantity || 
        $quantity > $this->maxQuantityPerUser) {
        return false;
    }
    
    // 检查会员等级
    if ($this->requireMemberLevel && 
        $userMemberLevelId !== $this->requiredMemberLevelId) {
        return false;
    }
    
    return true;
}
```

## 缓存策略

### 缓存键设计

```php
private const CACHE_PREFIX = 'seckill:activity:';
private const CACHE_LIST_KEY = 'seckill:activity:list';
private const CACHE_STATISTICS_KEY = 'seckill:activity:statistics';
```

### 缓存清除策略

- **创建活动**：清除列表缓存、统计缓存
- **更新活动**：清除详情缓存、列表缓存（如果标题/状态变更）
- **删除活动**：清除详情缓存、列表缓存、统计缓存
- **状态变更**：清除详情缓存、列表缓存、统计缓存

## 验证职责划分

### Request层验证（格式验证）

```php
public function storeRules(): array
{
    return [
        'title' => ['required', 'string', 'min:2', 'max:100'],
        'rules.max_quantity_per_user' => ['nullable', 'integer', 'min:1', 'max:999'],
        // ... 格式、长度、类型验证
    ];
}
```

### Entity层验证（业务规则）

```php
private function validate(): void
{
    if ($this->maxQuantityPerUser < 1) {
        throw new \InvalidArgumentException('每人最大购买数量必须大于0');
    }
    
    if ($this->minPurchaseQuantity > $this->maxQuantityPerUser) {
        throw new \InvalidArgumentException('最小购买数量不能大于最大购买数量');
    }
    
    // ... 业务规则验证
}
```

## 关键改进

1. **代码更简洁**：删除Assembler，减少中间层
2. **职责更清晰**：DTO负责数据传输，Entity负责业务逻辑
3. **可维护性更好**：遵循DDD规范，结构清晰
4. **可扩展性更强**：通过事件机制解耦
5. **配置更灵活**：集成SystemSetting，支持动态配置
6. **业务规则集中**：所有业务逻辑在Entity中，易于维护

## 注意事项

1. **活动无时间**：时间信息在场次（Session）中定义
2. **使用Contract**：DTO必须实现Contract接口
3. **事务管理**：在Application层（CommandService）管理事务
4. **事件发布**：在Domain Service中发布领域事件
5. **配置获取**：通过SeckillConfigService统一获取配置
6. **异常使用**：使用标准PHP异常（\DomainException, \InvalidArgumentException等）

## 后续工作

1. 按照相同模式改造秒杀场次（Session）模块
2. 按照相同模式改造场次商品（SessionProduct）模块
3. 完善单元测试
4. 添加集成测试
5. 完善API文档

---

**改造日期**：2026-02-06  
**改造人员**：开发团队  
**版本**：1.0.0
