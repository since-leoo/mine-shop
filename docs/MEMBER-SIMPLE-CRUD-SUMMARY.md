# Member 模块简单 CRUD 改造总结

## 改造日期
2026-02-06

## 已完成的模块 ✅

### 1. MemberLevel - 会员等级
### 2. MemberTag - 会员标签

两个模块都是**简单 CRUD**，不需要实体。

---

## 简单 CRUD 的标准实现流程

### 数据流
```
Request → DTO (implements Contract) → CommandService (事务) 
→ Domain Service → DTO::toArray() → Repository
```

### 关键特征
- ❌ 不创建 Entity
- ❌ 不创建 Mapper
- ✅ DTO 实现 toArray() 方法
- ✅ Contract 接口声明 toArray()
- ✅ Domain Service 直接使用 DTO::toArray()

---

## 实现细节

### 1. Contract 接口

```php
<?php

namespace App\Domain\Member\Contract;

interface MemberLevelInput
{
    public function getId(): int;
    public function getName(): string;
    // ... 其他 getter 方法
    
    /**
     * 转换为数组（用于简单 CRUD 操作）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
```

**关键点：**
- 声明 `toArray()` 方法
- 所有 getter 方法

---

### 2. DTO 实现

```php
<?php

namespace App\Interface\Admin\DTO\Member;

use App\Domain\Member\Contract\MemberLevelInput;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\DTO\Annotation\Validation\Required;

#[Valid]
class MemberLevelDto implements MemberLevelInput
{
    public ?int $id = null;
    
    #[Required]
    public string $name = '';
    
    // ... 其他属性
    
    #[Required]
    public int $operator_id = 0;
    
    // Getter 方法
    public function getId(): int
    {
        return $this->id ?? 0;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    // ... 其他 getter
    
    /**
     * 转换为数组（用于简单 CRUD 操作）.
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'level' => $this->level,
            // ... 其他字段
        ];
        
        // 创建时添加 created_by
        if ($this->id === null) {
            $data['created_by'] = $this->operator_id;
        } else {
            // 更新时添加 updated_by
            $data['updated_by'] = $this->operator_id;
        }
        
        return array_filter($data, static fn ($value) => $value !== null);
    }
}
```

**关键点：**
- 实现 Contract 接口
- 实现 `toArray()` 方法
- 根据 `$id` 是否为 null 判断是创建还是更新
- 创建时添加 `created_by`，更新时添加 `updated_by`
- 过滤掉 null 值

---

### 3. Request

```php
<?php

namespace App\Interface\Admin\Request\Member;

use App\Domain\Member\Contract\MemberLevelInput;
use App\Interface\Admin\DTO\Member\MemberLevelDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

class MemberLevelRequest extends BaseRequest
{
    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            // ... 其他验证规则
        ];
    }
    
    public function updateRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:50'],
            // ... 其他验证规则
        ];
    }
    
    /**
     * 转换为 DTO.
     * @param int|null $id 会员等级ID，创建时为null，更新时传入
     * @param int $operatorId 操作者ID
     */
    public function toDto(?int $id, int $operatorId): MemberLevelInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        $params['operator_id'] = $operatorId;
        
        return Mapper::map($params, new MemberLevelDto());
    }
}
```

**关键点：**
- 添加 `toDto()` 方法
- 接收 `$id` 和 `$operatorId` 参数
- 使用 `Mapper::map()` 转换为 DTO

---

### 4. Domain Service

```php
<?php

namespace App\Domain\Member\Service;

use App\Domain\Member\Contract\MemberLevelInput;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\MemberLevel;
use App\Interface\Common\ResultCode;

final class MemberLevelService extends IService
{
    public function __construct(public readonly MemberLevelRepository $repository) {}
    
    /**
     * 创建会员等级.
     */
    public function create(MemberLevelInput $dto): MemberLevel
    {
        // 使用 DTO 的 toArray() 方法获取数据
        return $this->repository->create($dto->toArray());
    }
    
    /**
     * 更新会员等级.
     */
    public function update(MemberLevelInput $dto): ?MemberLevel
    {
        $level = $this->repository->findById($dto->getId());
        if (! $level) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员等级不存在');
        }
        
        // 使用 DTO 的 toArray() 方法获取数据
        $this->repository->updateById($dto->getId(), $dto->toArray());
        
        return $level->refresh();
    }
    
    /**
     * 删除会员等级.
     */
    public function delete(int $id): bool
    {
        if (! $this->repository->existsById($id)) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员等级不存在');
        }
        
        return $this->repository->deleteById($id) > 0;
    }
}
```

**关键点：**
- 直接使用 `$dto->toArray()` 获取数据
- 不需要 Entity 和 Mapper
- 使用 `BusinessException` 抛出异常
- 返回 Model 对象

---

### 5. CommandService

```php
<?php

namespace App\Application\Commad;

use App\Domain\Member\Contract\MemberLevelInput;
use App\Domain\Member\Service\MemberLevelService;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

final class MemberLevelCommandService
{
    public function __construct(
        private readonly MemberLevelService $memberLevelService,
        private readonly CacheInterface $cache
    ) {}
    
    /**
     * 创建会员等级.
     */
    public function create(MemberLevelInput $input): array
    {
        // 1. 事务管理
        $level = Db::transaction(fn () => $this->memberLevelService->create($input));
        
        // 2. 缓存清理
        $this->forgetCache((int) $level->id);
        
        return $level->toArray();
    }
    
    /**
     * 更新会员等级.
     */
    public function update(MemberLevelInput $input): array
    {
        // 1. 事务管理
        $level = Db::transaction(fn () => $this->memberLevelService->update($input));
        
        // 2. 缓存清理
        $this->forgetCache($input->getId());
        
        return $level->toArray();
    }
    
    /**
     * 删除会员等级.
     */
    public function delete(int $id): bool
    {
        // 1. 事务管理
        $result = Db::transaction(fn () => $this->memberLevelService->delete($id));
        
        // 2. 缓存清理
        if ($result) {
            $this->forgetCache($id);
        }
        
        return $result;
    }
    
    /**
     * 清理缓存.
     */
    private function forgetCache(int $id): void
    {
        $this->cache->delete("member_level:{$id}");
        $this->cache->delete('member_levels:list');
    }
}
```

**关键点：**
- 添加事务管理 `Db::transaction()`
- 添加缓存清理逻辑
- 依赖注入 `CacheInterface`

---

### 6. Controller

```php
<?php

namespace App\Interface\Admin\Controller\Member;

use App\Application\Commad\MemberLevelCommandService;
use App\Application\Query\MemberLevelQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Request\Member\MemberLevelRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Result;

final class MemberLevelController extends AbstractController
{
    public function __construct(
        private readonly MemberLevelQueryService $queryService,
        private readonly MemberLevelCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}
    
    #[PostMapping(path: '')]
    public function store(MemberLevelRequest $request): Result
    {
        $level = $this->commandService->create(
            $request->toDto(null, $this->currentUser->id())
        );
        return $this->success($level, '创建会员等级成功', 201);
    }
    
    #[PutMapping(path: '{id:\d+}')]
    public function update(int $id, MemberLevelRequest $request): Result
    {
        $level = $this->commandService->update(
            $request->toDto($id, $this->currentUser->id())
        );
        return $this->success($level, '更新会员等级成功');
    }
    
    #[DeleteMapping(path: '{id:\d+}')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除会员等级成功');
    }
}
```

**关键点：**
- 使用 `$request->toDto()` 转换数据
- 创建时传入 `null` 作为 ID
- 更新时传入路由参数 `$id`
- 传入当前用户 ID 作为操作者

---

## 文件结构

### 需要创建的文件
```
app/Domain/Member/Contract/
├── MemberLevelInput.php       ✅ 创建
└── MemberTagInput.php          ✅ 创建

app/Interface/Admin/DTO/Member/
├── MemberLevelDto.php          ✅ 创建
└── MemberTagDto.php            ✅ 创建
```

### 需要改造的文件
```
app/Interface/Admin/Request/Member/
├── MemberLevelRequest.php      ✅ 添加 toDto()
└── MemberTagRequest.php        ✅ 添加 toDto()

app/Interface/Admin/Controller/Member/
├── MemberLevelController.php   ✅ 使用 DTO
└── MemberTagController.php     ✅ 使用 DTO

app/Application/Commad/
├── MemberLevelCommandService.php  ✅ 添加事务和缓存
└── MemberTagCommandService.php    ✅ 添加事务和缓存

app/Domain/Member/Service/
├── MemberLevelService.php      ✅ 使用 DTO::toArray()
└── MemberTagService.php        ✅ 使用 DTO::toArray()
```

### 不需要的文件（已删除）
```
app/Domain/Member/Entity/
├── MemberLevelEntity.php       ❌ 删除
└── MemberTagEntity.php         ❌ 删除

app/Domain/Member/Mapper/
├── MemberLevelMapper.php       ❌ 删除
└── MemberTagMapper.php         ❌ 删除
```

---

## 与复杂业务逻辑的对比

| 特性 | 简单 CRUD | 复杂业务逻辑 |
|------|-----------|--------------|
| Entity | ❌ 不需要 | ✅ 需要 |
| Mapper | ❌ 不需要 | ✅ 需要 |
| DTO::toArray() | ✅ 需要 | ❌ 不需要 |
| Entity::create/update | ❌ 不需要 | ✅ 需要 |
| Service::getEntity() | ❌ 不需要 | ✅ 需要 |
| Dirty 追踪 | ❌ 不需要 | ✅ 需要 |
| 业务规则验证 | Request 层 | Entity 层 |

---

## 判断标准

### 使用简单 CRUD（不需要实体）
- ✅ 只有增删改查操作
- ✅ 业务规则验证在 Request 层完成
- ✅ 没有复杂的状态变更
- ✅ 没有多个业务行为方法
- ✅ 不需要 dirty 追踪

**示例：** MemberLevel、MemberTag

### 使用复杂业务逻辑（需要实体）
- ✅ 有复杂的业务规则验证
- ✅ 有多个业务行为方法
- ✅ 需要 dirty 追踪机制
- ✅ 有状态机或生命周期管理
- ✅ 有聚合根概念

**示例：** Member、MemberAccount

---

## 异常处理

统一使用 `BusinessException`：

```php
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

// 资源不存在
throw new BusinessException(ResultCode::NOT_FOUND, '会员等级不存在');

// 业务失败
throw new BusinessException(ResultCode::FAIL, '操作失败');
```

---

## 缓存策略

```php
private function forgetCache(int $id): void
{
    // 清理单个资源缓存
    $this->cache->delete("member_level:{$id}");
    
    // 清理列表缓存
    $this->cache->delete('member_levels:list');
    
    // 清理选项缓存（如果有）
    $this->cache->delete('member_levels:options');
}
```

---

## 总结

简单 CRUD 的核心思想：
1. **不创建 Entity 和 Mapper**
2. **DTO 提供 toArray() 方法**
3. **Domain Service 直接使用 DTO::toArray()**
4. **保持事务管理和缓存清理**

这样可以减少不必要的代码，保持架构简洁。

---

## 参考文档

- [DDD 架构规范](./DDD-ARCHITECTURE.md)
- [Member 模块改造总结](./MEMBER-MODULE-REFACTOR.md)

## 版本

1.0.0
