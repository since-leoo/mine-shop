# DDD 架构规范文档

本文档定义了项目中 DDD（领域驱动设计）的完整架构规范和实现流程。

## 目录

- [架构概览](#架构概览)
- [层次结构](#层次结构)
- [完整流程](#完整流程)
- [实现规范](#实现规范)
- [判断标准](#判断标准)
- [代码示例](#代码示例)

---

## 架构概览

```
┌─────────────────────────────────────────────────────────┐
│                    Interface 层                          │
│  Controller → Request::toDto() → DTO (implements Contract)│
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                   Application 层                         │
│  CommandService (业务编排、事务管理、领域事件发布)        │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                     Domain 层                            │
│  Service → Mapper → Entity → Repository                 │
└─────────────────────────────────────────────────────────┘
```

---

## 层次结构

### 1. Interface 层（接口层）

**职责：**
- 接收 HTTP 请求
- 数据验证
- DTO 转换
- 返回响应

**组件：**
- `Controller` - 控制器
- `Request` - 请求验证类
- `DTO` - 数据传输对象
- `Contract` - 契约接口

### 2. Application 层（应用层）

**职责：**
- 业务流程编排
- 事务管理
- 领域事件发布
- 缓存管理

**组件：**
- `CommandService` - 命令服务（写操作）
- `QueryService` - 查询服务（读操作）

### 3. Domain 层（领域层）

**职责：**
- 核心业务逻辑
- 领域模型
- 业务规则验证

**组件：**
- `Service` - 领域服务
- `Entity` - 实体（聚合根）
- `Mapper` - 模型转换器
- `Repository` - 仓储接口
- `ValueObject` - 值对象

---

## 完整流程

### 流程图

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
Domain Service 判断是否需要实体
    ↓
┌─────────────────────┬─────────────────────┐
│   需要实体          │   不需要实体         │
│                     │                     │
│ Mapper::getNewEntity│ DTO::toArray()      │
│        ↓            │        ↓            │
│ Entity::create(dto) │ Repository::create()│
│        ↓            │                     │
│ Entity::toArray()   │                     │
│        ↓            │                     │
│ Repository::create()│                     │
└─────────────────────┴─────────────────────┘
    ↓
返回结果
    ↓
CommandService 提交事务
    ↓
CommandService 发布领域事件
    ↓
Controller 返回响应
```

---

## 实现规范

### 1. Interface 层实现

#### Request 类

```php
<?php

namespace App\Interface\Admin\Request\Permission;

use App\Domain\Permission\Contract\User\UserInput;
use App\Interface\Admin\Dto\Permission\UserDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;

class UserRequest extends BaseRequest
{
    public function createRules(): array
    {
        return [
            'username' => 'required|string|max:20',
            'nickname' => 'required|string|max:60',
            'password' => 'required|string|min:6',
            // ...
        ];
    }

    public function saveRules(): array
    {
        return [
            'username' => 'required|string|max:20',
            'nickname' => 'required|string|max:60',
            // ...
        ];
    }

    /**
     * 转换为 DTO.
     * @param int|null $id 用户ID，创建时为null，更新时传入
     * @param int $operatorId 操作者ID
     */
    public function toDto(?int $id, int $operatorId): UserInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        $params['operator_id'] = $operatorId;
        
        return Mapper::map($params, new UserDto());
    }
}
```

#### Controller 类

```php
<?php

namespace App\Interface\Admin\Controller\Permission;

use App\Application\Commad\UserCommandService;
use App\Interface\Admin\Request\Permission\UserRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Result;

class UserController extends AbstractController
{
    public function __construct(
        private readonly UserCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[PostMapping(path: '')]
    public function create(UserRequest $request): Result
    {
        $this->commandService->create($request->toDto(null, $this->currentUser->id()));
        return $this->success();
    }

    #[PutMapping(path: '{id}')]
    public function save(int $id, UserRequest $request): Result
    {
        $this->commandService->update($request->toDto($id, $this->currentUser->id()));
        return $this->success();
    }
}
```

#### DTO 类

```php
<?php

namespace App\Interface\Admin\Dto\Permission;

use App\Domain\Permission\Contract\User\UserInput;
use Hyperf\DTO\Annotation\Validation\Required;

class UserDto implements UserInput
{
    public ?int $id = null;
    
    #[Required]
    public string $username = '';
    
    #[Required]
    public string $nickname = '';
    
    public ?string $password = null;
    
    #[Required]
    public int $operator_id = 0;
    
    // Getter 方法
    public function getId(): int
    {
        return $this->id ?? 0;
    }
    
    public function getUsername(): string
    {
        return $this->username;
    }
    
    public function getNickname(): string
    {
        return $this->nickname;
    }
    
    public function getPassword(): ?string
    {
        return $this->password;
    }
    
    public function getOperatorId(): int
    {
        return $this->operator_id;
    }
}
```

#### Contract 接口

```php
<?php

namespace App\Domain\Permission\Contract\User;

interface UserInput
{
    public function getId(): int;
    public function getUsername(): string;
    public function getNickname(): string;
    public function getPassword(): ?string;
    public function getOperatorId(): int;
}
```

---

### 2. Application 层实现

#### CommandService 类

```php
<?php

namespace App\Application\Commad;

use App\Domain\Permission\Contract\User\UserInput;
use App\Domain\Permission\Service\UserService;
use App\Infrastructure\Model\Permission\User;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

final class UserCommandService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly CacheInterface $cache
    ) {}

    /**
     * 创建用户.
     */
    public function create(UserInput $input): User
    {
        // 1. 事务管理
        $user = Db::transaction(fn () => $this->userService->create($input));
        
        // 2. 领域事件发布（如果需要）
        // event(new UserCreated($user));
        
        // 3. 缓存清理
        $this->forgetCache((int) $user->id);
        
        return $user;
    }

    /**
     * 更新用户.
     */
    public function update(UserInput $input): ?User
    {
        // 1. 事务管理
        $user = Db::transaction(fn () => $this->userService->update($input));
        
        // 2. 缓存清理
        if ($user) {
            $this->forgetCache((int) $user->id);
        }
        
        return $user;
    }

    private function forgetCache(int $id): void
    {
        $this->cache->delete((string) $id);
    }
}
```

---

### 3. Domain 层实现

#### 3.1 需要实体的场景（有复杂业务逻辑）

##### Domain Service 类

```php
<?php

namespace App\Domain\Permission\Service;

use App\Domain\Permission\Contract\User\UserInput;
use App\Domain\Permission\Entity\UserEntity;
use App\Domain\Permission\Mapper\UserMapper;
use App\Domain\Permission\Repository\UserRepository;
use App\Infrastructure\Model\Permission\User;

final class UserService
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * 创建用户.
     */
    public function create(UserInput $dto): User
    {
        // 1. 通过 Mapper 获取新实体
        $entity = UserMapper::getNewEntity();
        
        // 2. 调用实体的 create 行为方法（内部组装设置值）
        $entity->create($dto);
        
        // 3. 外部逻辑判断后调用仓储
        $user = $this->repository->create($entity->toArray());
        
        // 4. 同步关联关系（如果需要）
        $this->syncRelations($user, $entity);
        
        return $user;
    }

    /**
     * 更新用户.
     */
    public function update(UserInput $dto): ?User
    {
        // 1. 通过仓储获取 Model
        $user = $this->repository->findById($dto->getId());
        if (!$user) {
            return null;
        }
        
        // 2. 通过 Mapper 将 Model 转换为 Entity
        $entity = UserMapper::fromModel($user);
        
        // 3. 调用实体的 update 行为方法
        $entity->update($dto);
        
        // 4. 持久化修改
        $this->repository->updateById($dto->getId(), $entity->toArray());
        
        // 5. 同步关联关系（如果需要）
        $this->syncRelations($user, $entity);
        
        return $user;
    }

    /**
     * 获取用户实体.
     * 
     * 通过 ID 获取 Model，然后通过 Mapper 转换为 Entity.
     * 用于需要调用实体行为方法的场景（如 grantRoles、resetPassword 等）.
     * 
     * @param int $id 用户ID
     * @return UserEntity 用户实体对象
     * @throws \RuntimeException 当用户不存在时
     */
    public function getEntity(int $id): UserEntity
    {
        /** @var null|User $model */
        $model = $this->repository->findById($id);
        
        if (!$model) {
            throw new \RuntimeException("用户不存在: ID={$id}");
        }
        
        return UserMapper::fromModel($model);
    }

    /**
     * 同步关联关系.
     */
    private function syncRelations(User $user, UserEntity $entity): void
    {
        if ($entity->shouldSyncDepartments()) {
            $user->department()->sync($entity->getDepartmentIds());
        }
        
        if ($entity->shouldSyncPositions()) {
            $user->position()->sync($entity->getPositionIds());
        }
    }
}
```

##### Entity 类

```php
<?php

namespace App\Domain\Permission\Entity;

use App\Domain\Permission\Contract\User\UserInput;

final class UserEntity
{
    private int $id = 0;
    private string $username = '';
    private string $nickname = '';
    private ?string $password = null;
    
    /**
     * @var array<string, bool> dirty 追踪机制
     */
    private array $dirty = [];

    /**
     * 创建行为方法：接收 DTO，内部组装设置值.
     */
    public function create(UserInput $dto): self
    {
        $this->setUsername($dto->getUsername());
        $this->setNickname($dto->getNickname());
        $this->setPassword($dto->getPassword());
        // ... 设置其他属性
        
        return $this;
    }

    /**
     * 更新行为方法：接收 DTO，内部组装设置值.
     */
    public function update(UserInput $dto): self
    {
        $dto->getNickname() && $this->setNickname($dto->getNickname());
        $dto->getEmail() && $this->setEmail($dto->getEmail());
        // ... 更新其他属性
        
        return $this;
    }

    /**
     * 设置用户名.
     */
    public function setUsername(string $username): self
    {
        $username = trim($username);
        if ($username === '') {
            throw new \DomainException('用户名不能为空');
        }
        
        $this->username = $username;
        $this->markDirty('username');
        return $this;
    }

    /**
     * 设置昵称.
     */
    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        $this->markDirty('nickname');
        return $this;
    }

    /**
     * 转换为数组（用于持久化）.
     * 使用 dirty 追踪机制，只返回修改过的字段.
     */
    public function toArray(): array
    {
        $data = [
            'username' => $this->username,
            'nickname' => $this->nickname,
            'password' => $this->password,
            // ...
        ];

        // 如果没有 dirty 标记，返回所有非空字段
        if ($this->dirty === []) {
            return array_filter($data, static fn ($value) => $value !== null);
        }

        // 只返回 dirty 标记的字段
        return array_filter(
            $data,
            function ($value, string $field) {
                return isset($this->dirty[$field]) && $value !== null;
            },
            \ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * 标记字段为已修改.
     */
    private function markDirty(string $field): void
    {
        $this->dirty[$field] = true;
    }

    // Getter 和 Setter 方法...
}
```

##### Mapper 类

```php
<?php

namespace App\Domain\Permission\Mapper;

use App\Domain\Permission\Entity\UserEntity;
use App\Infrastructure\Model\Permission\User;

class UserMapper
{
    /**
     * 从 Model 转换为 Entity.
     */
    public static function fromModel(User $model): UserEntity
    {
        $entity = new UserEntity();
        
        $entity->setId($model->id);
        $entity->setUsername($model->username);
        $entity->setNickname($model->nickname);
        $entity->setEmail($model->email ?? '');
        // ... 设置其他属性
        
        return $entity;
    }

    /**
     * 获取新实体.
     */
    public static function getNewEntity(): UserEntity
    {
        return new UserEntity();
    }
}
```

---

#### 3.2 不需要实体的场景（简单 CRUD）

##### DTO 提供 toArray() 方法

```php
<?php

namespace App\Interface\Admin\Dto\Permission;

use App\Domain\Permission\Contract\Department\DepartmentCreateInput;
use App\Domain\Permission\Contract\Department\DepartmentUpdateInput;

class DepartmentDto implements DepartmentCreateInput, DepartmentUpdateInput
{
    public ?int $id = null;
    public string $name = '';
    public ?int $parent_id = null;
    public array $department_users = [];
    public array $leader = [];
    public int $operator_id = 0;

    // Getter 方法...

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'parent_id' => $this->parent_id,
        ];
        
        // 创建时添加 created_by
        if ($this->id === null) {
            $data['created_by'] = $this->operator_id;
        } else {
            // 更新时添加 updated_by
            $data['updated_by'] = $this->operator_id;
        }
        
        return $data;
    }
}
```

##### Contract 接口声明 toArray()

```php
<?php

namespace App\Domain\Permission\Contract\Department;

interface DepartmentCreateInput
{
    public function getName(): string;
    public function getParentId(): ?int;
    public function getDepartmentUsers(): array;
    public function getLeaders(): array;
    public function getOperatorId(): int;
    
    /**
     * 转换为数组（用于简单 CRUD 操作）.
     */
    public function toArray(): array;
}
```

##### Domain Service 直接使用 toArray()

```php
<?php

namespace App\Domain\Permission\Service;

use App\Domain\Permission\Contract\Department\DepartmentCreateInput;
use App\Domain\Permission\Repository\DepartmentRepository;
use App\Infrastructure\Model\Permission\Department;

final class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepository $repository
    ) {}

    /**
     * 创建部门.
     */
    public function create(DepartmentCreateInput $dto): Department
    {
        // 使用 DTO 的 toArray() 方法获取数据
        $department = $this->repository->create($dto->toArray());
        
        // 同步关联关系
        $department->department_users()->sync($dto->getDepartmentUsers());
        $department->leader()->sync($dto->getLeaders());
        
        return $department;
    }

    /**
     * 更新部门.
     */
    public function update(DepartmentUpdateInput $dto): ?Department
    {
        $department = $this->repository->findById($dto->getId());
        if (!$department) {
            return null;
        }
        
        // 使用 DTO 的 toArray() 方法获取数据
        $this->repository->updateById($dto->getId(), $dto->toArray());
        
        // 同步关联关系
        $department->department_users()->sync($dto->getDepartmentUsers());
        $department->leader()->sync($dto->getLeaders());
        
        return $department;
    }
}
```

---

## 验证职责划分

### Request 层验证（优先）

**应该在 Request 层验证的内容：**
- ✅ 格式验证（邮箱、手机号、URL、日期等）
- ✅ 长度验证（字符串长度、数组长度等）
- ✅ 类型验证（整数、字符串、布尔值等）
- ✅ 必填验证（required）
- ✅ 范围验证（min、max、between）
- ✅ 枚举验证（in、exists）
- ✅ 唯一性验证（unique）
- ✅ 正则表达式验证

**原则：** 能在 Request 层验证的，就在 Request 层验证。Request 层是第一道防线。

**示例：**
```php
public function storeRules(): array
{
    return [
        'nickname' => ['required', 'string', 'max:100'],
        'phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/'],
        'email' => ['nullable', 'email', 'max:255'],
        'age' => ['nullable', 'integer', 'min:0', 'max:150'],
        'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
        'growth_value' => ['nullable', 'integer', 'min:0'],
    ];
}
```

### Entity 层验证（业务规则）

**应该在 Entity 层验证的内容：**
- ✅ 复杂的业务规则（如：折扣率必须在会员等级允许的范围内）
- ✅ 跨字段的业务逻辑（如：最高成长值必须大于最低成长值）
- ✅ 状态转换规则（如：已支付的订单不能取消）
- ✅ 领域不变量（如：账户余额不能为负数）
- ✅ 需要查询数据库的业务规则（如：检查库存是否充足）

**原则：** Entity 层只验证业务规则，不验证格式。

**示例：**
```php
// ❌ 错误：在 Entity 中验证格式
public function setPhone(?string $phone): void
{
    if ($phone !== null && ! preg_match('/^1[3-9]\d{9}$/', $phone)) {
        throw new BusinessException(ResultCode::FAIL, '手机号格式不正确');
    }
    // ...
}

// ✅ 正确：在 Request 中验证格式
public function storeRules(): array
{
    return [
        'phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/'],
    ];
}

// ✅ 正确：在 Entity 中验证业务规则
public function setGrowthMax(?int $value): self
{
    if ($value !== null && $this->growthMin !== null && $value < $this->growthMin) {
        throw new BusinessException(ResultCode::FAIL, '最高成长值不能小于最低成长值');
    }
    // ...
}
```

### 验证层次对比

| 验证类型 | Request 层 | Entity 层 |
|---------|-----------|----------|
| 格式验证 | ✅ 优先 | ❌ 不应该 |
| 长度验证 | ✅ 优先 | ❌ 不应该 |
| 类型验证 | ✅ 优先 | ❌ 不应该 |
| 必填验证 | ✅ 优先 | ❌ 不应该 |
| 范围验证 | ✅ 优先 | ⚠️ 看情况 |
| 枚举验证 | ✅ 优先 | ⚠️ 看情况 |
| 业务规则 | ❌ 不应该 | ✅ 必须 |
| 跨字段逻辑 | ⚠️ 简单的可以 | ✅ 复杂的必须 |
| 状态转换 | ❌ 不应该 | ✅ 必须 |
| 领域不变量 | ❌ 不应该 | ✅ 必须 |

### 实际案例

#### 案例 1：会员昵称验证

```php
// Request 层：验证格式和长度
public function storeRules(): array
{
    return [
        'nickname' => ['required', 'string', 'max:100'],
    ];
}

// Entity 层：不需要再验证（已在 Request 层验证）
public function setNickname(?string $nickname): void
{
    $this->nickname = $nickname;
    $this->markDirty('nickname', $nickname);
}
```

#### 案例 2：会员等级成长值验证

```php
// Request 层：验证基本范围
public function storeRules(): array
{
    return [
        'growth_value_min' => ['required', 'integer', 'min:0'],
        'growth_value_max' => ['nullable', 'integer', 'gte:growth_value_min'],
    ];
}

// Entity 层：验证业务规则（跨字段逻辑）
public function setGrowthMax(?int $value): self
{
    if ($value !== null && $this->growthMin !== null && $value < $this->growthMin) {
        throw new BusinessException(ResultCode::FAIL, '最高成长值不能小于最低成长值');
    }
    
    $this->growthMax = $value;
    $this->markDirty('growth_value_max');
    return $this;
}
```

#### 案例 3：订单状态转换验证

```php
// Request 层：验证状态值是否有效
public function updateStatusRules(): array
{
    return [
        'status' => ['required', Rule::in(['pending', 'paid', 'shipped', 'completed', 'cancelled'])],
    ];
}

// Entity 层：验证状态转换规则（业务规则）
public function updateStatus(string $newStatus): self
{
    // 已支付的订单不能取消
    if ($this->status === 'paid' && $newStatus === 'cancelled') {
        throw new BusinessException(ResultCode::FAIL, '已支付的订单不能取消');
    }
    
    // 已完成的订单不能修改状态
    if ($this->status === 'completed') {
        throw new BusinessException(ResultCode::FAIL, '已完成的订单不能修改状态');
    }
    
    $this->status = $newStatus;
    $this->markDirty('status');
    return $this;
}
```

### 总结

**验证职责划分原则：**
1. **Request 层是第一道防线** - 验证所有格式、类型、长度、范围等基础验证
2. **Entity 层是业务规则守护者** - 只验证业务规则和领域不变量
3. **避免重复验证** - Request 层验证过的，Entity 层不需要再验证
4. **保持 Entity 纯粹** - Entity 专注于业务逻辑，不关心数据格式

---

## 判断标准

### 需要创建实体的场景

✅ **需要实体**，当满足以下任一条件：

1. **有复杂的业务规则验证**
   - 例如：密码强度验证、状态转换规则
   
2. **有多个业务行为方法**
   - 例如：`grantRoles()`, `resetPassword()`, `changeStatus()`
   
3. **需要 dirty 追踪机制**
   - 只更新修改过的字段
   
4. **有状态机或生命周期管理**
   - 例如：订单状态流转、审批流程
   
5. **有聚合根概念**
   - 管理多个子实体或值对象

**示例模块：**
- User（用户）- 有密码重置、角色授予等复杂行为
- Role（角色）- 有权限授予、超级管理员检查等业务规则
- Order（订单）- 有状态流转、支付、退款等复杂逻辑

---

### 不需要创建实体的场景

❌ **不需要实体**，当满足以下条件：

1. **简单的 CRUD 操作**
   - 只有增删改查，没有复杂逻辑
   
2. **没有业务规则验证**
   - 数据验证在 Request 层完成即可
   
3. **没有状态变更**
   - 直接更新数据库字段
   
4. **关联关系简单**
   - 只需要 sync() 同步关联

**示例模块：**
- Department（部门）- 简单的部门管理
- Leader（领导）- 简单的领导关系管理
- Tag（标签）- 简单的标签管理

---

## 代码示例

### 完整示例：User 模块（需要实体）

#### 1. Request

```php
// app/Interface/Admin/Request/Permission/UserRequest.php
public function toDto(?int $id, int $operatorId): UserInput
{
    $params = $this->validated();
    $params['id'] = $id;
    $params['operator_id'] = $operatorId;
    return Mapper::map($params, new UserDto());
}
```

#### 2. Controller

```php
// app/Interface/Admin/Controller/Permission/UserController.php
public function create(UserRequest $request): Result
{
    $this->commandService->create($request->toDto(null, $this->currentUser->id()));
    return $this->success();
}
```

#### 3. CommandService

```php
// app/Application/Commad/UserCommandService.php
public function create(UserInput $input): User
{
    $user = Db::transaction(fn () => $this->userService->create($input));
    $this->forgetCache((int) $user->id);
    return $user;
}
```

#### 4. Domain Service

```php
// app/Domain/Permission/Service/UserService.php
public function create(UserInput $dto): User
{
    $entity = UserMapper::getNewEntity();
    $entity->create($dto);
    $user = $this->repository->create($entity->toArray());
    $this->syncRelations($user, $entity);
    return $user;
}
```

#### 5. Entity

```php
// app/Domain/Permission/Entity/UserEntity.php
public function create(UserInput $dto): self
{
    $this->setUsername($dto->getUsername());
    $this->setNickname($dto->getNickname());
    $this->setPassword($dto->getPassword());
    return $this;
}
```

---

### 完整示例：Department 模块（不需要实体）

#### 1. Request

```php
// app/Interface/Admin/Request/Permission/DepartmentRequest.php
public function toDto(?int $id, int $operatorId): DepartmentCreateInput|DepartmentUpdateInput
{
    $params = $this->validated();
    $params['id'] = $id;
    $params['operator_id'] = $operatorId;
    return Mapper::map($params, new DepartmentDto());
}
```

#### 2. DTO with toArray()

```php
// app/Interface/Admin/DTO/Permission/DepartmentDto.php
public function toArray(): array
{
    return [
        'name' => $this->name,
        'parent_id' => $this->parent_id,
        'created_by' => $this->id === null ? $this->operator_id : null,
        'updated_by' => $this->id !== null ? $this->operator_id : null,
    ];
}
```

#### 3. Domain Service

```php
// app/Domain/Permission/Service/DepartmentService.php
public function create(DepartmentCreateInput $dto): Department
{
    $department = $this->repository->create($dto->toArray());
    $department->department_users()->sync($dto->getDepartmentUsers());
    return $department;
}
```

---

## 关键要点总结

### ✅ 必须遵守的规则

1. **DTO 在 Domain Service 中转换为 Entity**
   - ❌ 不要在 Application 层转换
   - ✅ 在 Domain Service 中调用 `Mapper::getNewEntity()`

2. **Entity 的行为方法接收 DTO**
   - ❌ 不要在外部组装数据
   - ✅ 在 Entity 内部通过 `create(dto)` 或 `update(dto)` 组装

3. **Application 层职责明确**
   - ✅ 事务管理
   - ✅ 领域事件发布
   - ✅ 缓存清理
   - ❌ 不要包含业务逻辑

4. **简单 CRUD 使用 DTO::toArray()**
   - ✅ DTO 提供 `toArray()` 方法
   - ✅ Contract 接口声明 `toArray()`
   - ✅ Domain Service 直接使用

5. **使用 dirty 追踪机制**
   - ✅ Entity 使用 `markDirty()` 标记修改
   - ✅ `toArray()` 只返回修改过的字段

6. **Domain Service 必须提供 getEntity() 方法**
   - ✅ 用于通过 ID 获取实体
   - ✅ 内部调用 `Repository::findById()` 获取 Model
   - ✅ 通过 `Mapper::fromModel()` 转换为 Entity
   - ✅ 用于需要调用实体行为方法的场景

---

## getEntity() 方法详解

### 使用场景

当需要调用实体的业务行为方法时（如 `grantRoles()`, `resetPassword()` 等），需要先获取实体：

```php
// ❌ 错误：直接在 Application 层获取实体
$entity = UserMapper::fromModel($this->repository->findById($id));

// ✅ 正确：通过 Domain Service 的 getEntity() 方法
$entity = $this->userService->getEntity($id);
```

### 标准实现

```php
/**
 * 获取用户实体.
 * 
 * 通过 ID 获取 Model，然后通过 Mapper 转换为 Entity.
 * 用于需要调用实体行为方法的场景.
 * 
 * @param int $id 实体ID
 * @return UserEntity 实体对象
 * @throws \RuntimeException 当实体不存在时
 */
public function getEntity(int $id): UserEntity
{
    /** @var null|User $model */
    $model = $this->repository->findById($id);
    
    if (!$model) {
        throw new \RuntimeException("用户不存在: ID={$id}");
    }
    
    return UserMapper::fromModel($model);
}
```

### 使用示例

#### 示例 1：重置密码

```php
// Domain Service
public function resetPassword(UserResetPasswordInput $input): bool
{
    // 1. 获取实体
    $entity = $this->getEntity($input->getUserId());
    
    // 2. 调用实体行为方法
    $result = $entity->resetPasswordWithValidation();
    
    // 3. 根据结果持久化
    if ($result->needsSave) {
        $this->repository->updateById($entity->getId(), $entity->toArray());
    }
    
    return $result->success;
}
```

#### 示例 2：授予角色

```php
// Domain Service
public function grantRoles(UserGrantRolesInput $input): void
{
    // 1. 获取实体
    $entity = $this->getEntity($input->getUserId());
    
    // 2. 获取角色ID
    $roleIds = $this->roleRepository->getRoleIds($input->getRoleCodes());
    
    // 3. 调用实体行为方法
    $result = $entity->grantRoles($roleIds);
    
    // 4. 同步关联关系
    if ($result->success && $result->shouldSync) {
        $this->roleRepository->syncRoles($input->getUserId(), $result->roleIds);
    }
}
```

#### 示例 3：授予权限（Role）

```php
// Domain Service
public function grantPermissions(RoleGrantPermissionsInput $input): void
{
    // 1. 获取实体
    $entity = $this->getEntity($input->getRoleId());
    
    // 2. 检查是否为超级管理员
    $isSuperAdmin = $entity->isSuperAdmin();
    
    // 3. 获取菜单ID
    $menuIds = $this->menuRepository
        ->listByCodes($input->getPermissionCodes())
        ->pluck('id')
        ->toArray();
    
    // 4. 调用实体行为方法
    $result = $entity->grantPermissions($menuIds, $isSuperAdmin);
    
    // 5. 同步关联关系
    if ($result->success) {
        $roleModel = $this->repository->findById($input->getRoleId());
        $roleModel->menus()->sync($result->menuIds);
    }
}
```

### 命名规范

- ✅ 方法名统一为 `getEntity()`
- ✅ 参数为实体的主键 ID
- ✅ 返回值为对应的 Entity 对象
- ✅ 不存在时抛出 `\RuntimeException`

### 注意事项

1. **只在需要实体的 Service 中提供**
   - 简单 CRUD 的 Service 不需要此方法
   
2. **不要在 Application 层调用**
   - `getEntity()` 是 Domain 层的内部方法
   - Application 层不应该直接操作 Entity
   
3. **异常处理**
   - 实体不存在时抛出异常
   - 让调用方决定如何处理

---

## 异常处理

### 使用标准 PHP 异常

```php
// ✅ 正确：使用标准异常
throw new \DomainException('用户名不能为空');
throw new \RuntimeException('用户不存在');
throw new \InvalidArgumentException('无效的参数');

// ❌ 错误：不要创建自定义领域异常
throw new UserNotFoundException();
```

---

## ValueObject（值对象）

### 命名规范

值对象使用 `Vo` 结尾，而不是 `Result`：

```php
// ✅ 正确
class GrantRolesVo { }
class ResetPasswordVo { }

// ❌ 错误
class GrantRolesResult { }
class ResetPasswordResult { }
```

### 使用场景

值对象用于实体行为方法的返回值：

```php
public function grantRoles(array $roleIds): GrantRolesVo
{
    // 业务逻辑...
    
    return new GrantRolesVo(
        success: true,
        message: '角色授予成功',
        roleIds: $roleIds,
        shouldSync: true
    );
}
```

---

## 文档维护

- **创建日期：** 2026-02-06
- **最后更新：** 2026-02-06
- **维护者：** 开发团队
- **版本：** 1.0.0

---

## 参考资料

- [领域驱动设计（DDD）](https://en.wikipedia.org/wiki/Domain-driven_design)
- [Hyperf 框架文档](https://hyperf.wiki/)
- [PHP 标准异常](https://www.php.net/manual/en/spl.exceptions.php)
