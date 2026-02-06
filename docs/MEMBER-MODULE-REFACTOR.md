# Member 模块 DDD 改造总结

## 改造日期
2026-02-06

## 改造概览

Member 模块包含以下子模块：
1. **MemberLevel** - 会员等级（简单 CRUD，不需要实体）✅ 已完成
2. **MemberTag** - 会员标签（简单 CRUD，不需要实体）✅ 已完成
3. **Member** - 会员管理（复杂业务逻辑，需要实体）⏳ 待处理
4. **MemberAccount** - 会员账户/钱包（复杂业务逻辑，需要实体）⏳ 待处理

---

## 1. MemberLevel 模块改造（已完成）✅

### 判断标准
- ❌ 只有简单的 CRUD 操作
- ❌ 没有复杂业务规则（验证在 Request 层完成）
- ❌ 没有状态变更
- **结论：不需要实体，使用 DTO::toArray()**

### 创建的文件
- `app/Domain/Member/Contract/MemberLevelInput.php` - 输入契约接口（包含 toArray()）
- `app/Interface/Admin/DTO/Member/MemberLevelDto.php` - 数据传输对象（实现 toArray()）

### 改造的文件
- `app/Interface/Admin/Request/Member/MemberLevelRequest.php` - 添加 toDto()
- `app/Interface/Admin/Controller/Member/MemberLevelController.php` - 使用 DTO
- `app/Application/Commad/MemberLevelCommandService.php` - 添加事务和缓存
- `app/Domain/Member/Service/MemberLevelService.php` - 直接使用 DTO::toArray()

### 删除的文件
- ❌ `app/Domain/Member/Entity/MemberLevelEntity.php` - 简单 CRUD 不需要
- ❌ `app/Domain/Member/Mapper/MemberLevelMapper.php` - 简单 CRUD 不需要

### DTO::toArray() 实现
```php
public function toArray(): array
{
    $data = [
        'name' => $this->name,
        'level' => $this->level,
        'growth_value_min' => $this->growth_value_min,
        'growth_value_max' => $this->growth_value_max,
        'discount_rate' => $this->discount_rate,
        'point_rate' => $this->point_rate,
        'privileges' => $this->privileges,
        'icon' => $this->icon,
        'color' => $this->color,
        'status' => $this->status,
        'sort_order' => $this->sort_order,
        'description' => $this->description,
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
```

### 异常处理
使用 `BusinessException(ResultCode::NOT_FOUND, '会员等级不存在')`

---

## 2. MemberTag 模块改造（已完成）✅

### 判断标准
- ❌ 只有简单的 CRUD 操作
- ❌ 没有复杂业务规则
- ❌ 没有状态变更
- **结论：不需要实体，使用 DTO::toArray()**

### 创建的文件
- `app/Domain/Member/Contract/MemberTagInput.php` - 输入契约接口（包含 toArray()）
- `app/Interface/Admin/DTO/Member/MemberTagDto.php` - 数据传输对象（实现 toArray()）

### 改造的文件
- `app/Interface/Admin/Request/Member/MemberTagRequest.php` - 添加 toDto()
- `app/Interface/Admin/Controller/Member/MemberTagController.php` - 使用 DTO
- `app/Application/Commad/MemberTagCommandService.php` - 添加事务和缓存
- `app/Domain/Member/Service/MemberTagService.php` - 直接使用 DTO::toArray()

### 删除的文件
- ❌ `app/Domain/Member/Entity/MemberTagEntity.php` - 简单 CRUD 不需要
- ❌ `app/Domain/Member/Mapper/MemberTagMapper.php` - 简单 CRUD 不需要

### DTO::toArray() 实现
```php
public function toArray(): array
{
    $data = [
        'name' => $this->name,
        'color' => $this->color,
        'description' => $this->description,
        'status' => $this->status,
        'sort_order' => $this->sort_order,
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
```

---

## 3. Member 模块改造（进行中）🔄

### 判断标准
- ✅ 有复杂的业务逻辑（小程序登录、绑定手机号）
- ✅ 有多个业务行为方法（miniProgramLogin、bindPhoneNumber）
- ✅ 需要 dirty 追踪机制
- ✅ 有聚合根概念（管理会员钱包、标签等）
- **结论：需要实体**

### 当前状态
- ✅ Entity 已存在并实现 dirty 追踪
- ✅ Mapper 已存在
- ✅ Service 已有复杂业务逻辑
- ⏳ 需要创建 Contract 和 DTO
- ⏳ 需要改造 Request、Controller、CommandService

### 待创建的文件
- `app/Domain/Member/Contract/MemberInput.php` - 输入契约接口
- `app/Interface/Admin/DTO/Member/MemberDto.php` - 数据传输对象

### 待改造的文件
- `app/Interface/Admin/Request/Member/MemberRequest.php` - 添加 toDto()
- `app/Interface/Admin/Controller/Member/MemberController.php` - 使用 DTO
- `app/Application/Commad/MemberCommandService.php` - 添加事务和缓存
- `app/Domain/Member/Service/MemberService.php` - 完善 getEntity()
- `app/Domain/Member/Entity/MemberEntity.php` - 添加 create/update 方法和 BusinessException

### 业务行为方法
- `miniProgramLogin()` - 小程序登录
- `bindPhoneNumber()` - 绑定手机号
- `create()` - 创建会员
- `update()` - 更新会员档案
- `updateStatus()` - 更新会员状态
- `syncTags()` - 同步会员标签

---

## 4. MemberAccount 模块改造（待处理）⏳

### 判断标准
- ✅ 有复杂的业务逻辑（余额变更、积分变更）
- ✅ 有业务行为方法（changeBalance）
- ✅ 需要 dirty 追踪机制
- ✅ 有领域事件（MemberBalanceAdjusted）
- **结论：需要实体**

### 当前状态
- ✅ Entity 已存在（MemberWalletEntity）
- ✅ 领域事件已存在
- ⏳ 需要创建 Contract 和 DTO
- ⏳ 需要创建 Mapper
- ⏳ 需要改造 Request、Controller、CommandService、Service

### 待创建的文件
- `app/Domain/Member/Contract/MemberWalletInput.php` - 输入契约接口
- `app/Interface/Admin/DTO/Member/MemberAccountDto.php` - 数据传输对象
- `app/Domain/Member/Mapper/MemberWalletMapper.php` - 映射器

### 待改造的文件
- `app/Interface/Admin/Request/Member/MemberAccountRequest.php` - 添加 toDto()
- `app/Interface/Admin/Controller/Member/MemberAccountController.php` - 使用 DTO
- `app/Application/Commad/MemberAccountCommandService.php` - 完善事务和缓存
- `app/Domain/Member/Service/MemberWalletService.php` - 添加 getEntity()
- `app/Domain/Member/Entity/MemberWalletEntity.php` - 添加 create/update 和 BusinessException

---

## 关键改造点总结

### 1. 判断是否需要实体的标准

#### 需要实体 ✅
- 有复杂的业务规则验证
- 有多个业务行为方法
- 需要 dirty 追踪机制
- 有状态机或生命周期管理
- 有聚合根概念

**示例：** Member、MemberAccount

#### 不需要实体 ❌
- 简单的 CRUD 操作
- 没有业务规则验证（验证在 Request 层完成）
- 没有状态变更
- 关联关系简单

**示例：** MemberLevel、MemberTag

### 2. 简单 CRUD 的实现方式

```php
// Contract 接口声明 toArray()
interface MemberTagInput
{
    public function toArray(): array;
}

// DTO 实现 toArray()
class MemberTagDto implements MemberTagInput
{
    public function toArray(): array
    {
        $data = [...];
        
        if ($this->id === null) {
            $data['created_by'] = $this->operator_id;
        } else {
            $data['updated_by'] = $this->operator_id;
        }
        
        return array_filter($data, static fn ($value) => $value !== null);
    }
}

// Domain Service 直接使用 toArray()
public function create(MemberTagInput $dto): MemberTag
{
    return $this->repository->create($dto->toArray());
}
```

### 3. 复杂业务逻辑的实现方式

```php
// Domain Service 使用 Mapper 和 Entity
public function create(MemberInput $dto): Member
{
    // 1. 通过 Mapper 获取新实体
    $entity = MemberMapper::getNewEntity();
    
    // 2. 调用实体的 create 行为方法
    $entity->create($dto);
    
    // 3. 调用仓储持久化
    $member = $this->repository->save($entity);
    
    return $member;
}
```

### 4. 异常处理

统一使用 `BusinessException`：

```php
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

throw new BusinessException(ResultCode::FAIL, '错误信息');
throw new BusinessException(ResultCode::NOT_FOUND, '资源不存在');
```

### 5. 事务管理和缓存清理

所有 CommandService 都应该有：

```php
public function create(MemberLevelInput $input): array
{
    // 1. 事务管理
    $level = Db::transaction(fn () => $this->memberLevelService->create($input));
    
    // 2. 缓存清理
    $this->forgetCache((int) $level['id']);
    
    return $level;
}

private function forgetCache(int $id): void
{
    $this->cache->delete("member_level:{$id}");
    $this->cache->delete('member_levels:list');
}
```

---

## 改造进度

| 模块 | 类型 | 状态 | 进度 |
|------|------|------|------|
| MemberLevel | 简单 CRUD | ✅ 已完成 | 100% |
| MemberTag | 简单 CRUD | ✅ 已完成 | 100% |
| Member | 需要实体 | ⏳ 待处理 | 0% |
| MemberAccount | 需要实体 | ⏳ 待处理 | 0% |

---

## 下一步计划

1. ✅ 完成 MemberLevel 模块改造
2. ✅ 完成 MemberTag 模块改造
3. 🔄 完成 Member 模块改造
   - 创建 Contract 和 DTO
   - 改造 Request、Controller、CommandService
   - 完善 Entity 的 create/update 方法
   - 更新异常为 BusinessException
4. ⏳ 完成 MemberAccount 模块改造
   - 创建 Contract、DTO、Mapper
   - 改造 Request、Controller、CommandService、Service
   - 完善 Entity 的业务行为方法

---

## 参考文档

- [DDD 架构规范](./DDD-ARCHITECTURE.md)
- [MemberLevel 改造总结](./MEMBER-LEVEL-REFACTOR.md)

## 版本

1.0.0
