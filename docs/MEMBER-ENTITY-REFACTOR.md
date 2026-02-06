# Member 模块（需要实体）改造总结

## 改造日期
2026-02-06

## 模块类型
**复杂业务逻辑 - 需要实体**

## 判断标准

✅ **需要实体的原因：**
- 有复杂的业务规则验证（昵称、手机号、成长值、状态等）
- 有多个业务行为方法（create、update、updateStatus、syncTags）
- 需要 dirty 追踪机制（只更新修改的字段）
- 有聚合根概念（管理会员钱包、标签等）
- 有小程序登录、绑定手机号等复杂业务逻辑

---

## 改造内容

### 创建的文件

1. **Contract 接口**
   - `app/Domain/Member/Contract/MemberInput.php` - 会员输入契约接口

2. **DTO 类**
   - `app/Interface/Admin/DTO/Member/MemberDto.php` - 会员数据传输对象

### 改造的文件

1. **Entity 层**
   - `app/Domain/Member/Entity/MemberEntity.php`
     - 添加 `create(MemberInput $dto)` 行为方法
     - 添加 `update(MemberInput $dto)` 行为方法
     - 添加 `updateStatus(string $status)` 行为方法
     - 添加 `syncTags(array $tagIds)` 行为方法
     - 添加业务规则验证到 setter 方法
     - 使用 `BusinessException` 替代标准异常

2. **Mapper 层**
   - `app/Domain/Member/Mapper/MemberMapper.php`
     - 添加 `getNewEntity()` 方法

3. **Service 层**
   - `app/Domain/Member/Service/MemberService.php`
     - `create()` 方法改为接收 `MemberInput`
     - `update()` 方法改为接收 `MemberInput`
     - `updateStatus()` 方法改为接收 `int $memberId, string $status`
     - `syncTags()` 方法改为接收 `int $memberId, array $tagIds`
     - 完善 `getEntity()` 方法
     - 使用 `BusinessException` 替代 `RuntimeException`

4. **CommandService 层**
   - `app/Application/Commad/MemberCommandService.php`
     - 参数从 `MemberEntity` 改为 `MemberInput` 或具体参数
     - 添加事务管理
     - 添加缓存清理逻辑
     - 添加 `CacheInterface` 依赖注入

5. **Request 层**
   - `app/Interface/Admin/Request/Member/MemberRequest.php`
     - 添加 `toDto()` 方法

6. **Controller 层**
   - `app/Interface/Admin/Controller/Member/MemberController.php`
     - 移除 `MemberAssembler` 依赖
     - 使用 `Request::toDto()` 转换数据
     - 添加 `CurrentUser` 依赖注入
     - 简化 `updateStatus` 和 `syncTags` 方法

---

## 完整流程

### 创建流程
```
用户请求
    ↓
MemberController::store()
    ↓
MemberRequest::toDto(null, operatorId)
    ↓
MemberDto (implements MemberInput)
    ↓
MemberCommandService::create(input)
    ↓
Db::transaction() 开启事务
    ↓
MemberService::create(dto)
    ↓
MemberMapper::getNewEntity()
    ↓
MemberEntity::create(dto)
    ↓
MemberEntity::toArray() (dirty 追踪)
    ↓
MemberRepository::save(entity)
    ↓
同步标签（如果有）
    ↓
提交事务 + 清理缓存
    ↓
返回响应
```

### 更新流程
```
用户请求
    ↓
MemberController::update(id)
    ↓
MemberRequest::toDto(id, operatorId)
    ↓
MemberDto (implements MemberInput)
    ↓
MemberCommandService::update(input)
    ↓
Db::transaction() 开启事务
    ↓
MemberService::update(dto)
    ↓
MemberRepository::findById(id) 获取 Model
    ↓
MemberMapper::fromModel(model) 转换为 Entity
    ↓
MemberEntity::update(dto)
    ↓
MemberEntity::toArray() (只返回修改的字段)
    ↓
MemberRepository::updateEntity(entity)
    ↓
提交事务 + 清理缓存
    ↓
返回响应
```

### 更新状态流程
```
用户请求
    ↓
MemberController::updateStatus(id)
    ↓
MemberCommandService::updateStatus(id, status)
    ↓
Db::transaction() 开启事务
    ↓
MemberService::updateStatus(id, status)
    ↓
MemberService::getEntity(id) 获取实体
    ↓
MemberEntity::updateStatus(status) 业务规则验证
    ↓
MemberRepository::updateEntity(entity)
    ↓
提交事务 + 清理缓存
    ↓
返回响应
```

---

## Entity 行为方法

### 1. create(MemberInput $dto)
创建会员时的行为方法，接收 DTO 并组装所有字段。

```php
public function create(MemberInput $dto): self
{
    $this->setNickname($dto->getNickname());
    $this->setAvatar($dto->getAvatar());
    $this->setGender($dto->getGender());
    $this->setPhone($dto->getPhone());
    // ... 设置其他字段
    $this->setLevel($dto->getLevel() ?? 'bronze');
    $this->setStatus($dto->getStatus() ?? 'active');
    $this->setSource($dto->getSource() ?? 'admin');
    $this->setTagIds($dto->getTagIds());
    
    return $this;
}
```

### 2. update(MemberInput $dto)
更新会员时的行为方法，只更新非 null 的字段。

```php
public function update(MemberInput $dto): self
{
    $dto->getNickname() !== null && $this->setNickname($dto->getNickname());
    $dto->getAvatar() !== null && $this->setAvatar($dto->getAvatar());
    // ... 更新其他字段
    
    return $this;
}
```

### 3. updateStatus(string $status)
更新状态的行为方法，包含业务规则验证。

```php
public function updateStatus(string $status): self
{
    if (! \in_array($status, ['active', 'inactive', 'banned'], true)) {
        throw new BusinessException(ResultCode::FAIL, '无效的会员状态');
    }
    
    $this->setStatus($status);
    return $this;
}
```

### 4. syncTags(array $tagIds)
同步标签的行为方法。

```php
public function syncTags(array $tagIds): self
{
    $this->setTagIds($tagIds);
    return $this;
}
```

---

## 业务规则验证

Entity 的 setter 方法中添加的业务规则：

### 1. 昵称验证
```php
public function setNickname(?string $nickname): void
{
    if ($nickname !== null) {
        $nickname = trim($nickname);
        if ($nickname === '') {
            throw new BusinessException(ResultCode::FAIL, '昵称不能为空');
        }
        if (mb_strlen($nickname) > 100) {
            throw new BusinessException(ResultCode::FAIL, '昵称长度不能超过100个字符');
        }
    }
    
    $this->nickname = $nickname;
    $this->markDirty('nickname', $nickname);
}
```

### 2. 手机号验证
```php
public function setPhone(?string $phone): void
{
    if ($phone !== null && trim($phone) !== '') {
        // 简单的手机号格式验证
        if (! preg_match('/^1[3-9]\d{9}$/', $phone)) {
            throw new BusinessException(ResultCode::FAIL, '手机号格式不正确');
        }
    }
    
    $this->phone = $phone;
    $this->markDirty('phone', $phone);
}
```

### 3. 状态验证
```php
public function setStatus(?string $status): void
{
    if ($status !== null && ! \in_array($status, ['active', 'inactive', 'banned'], true)) {
        throw new BusinessException(ResultCode::FAIL, '无效的会员状态');
    }
    
    $this->status = $status;
    $this->markDirty('status', $status);
}
```

### 4. 成长值验证
```php
public function setGrowthValue(?int $growthValue): void
{
    if ($growthValue !== null && $growthValue < 0) {
        throw new BusinessException(ResultCode::FAIL, '成长值不能小于0');
    }
    
    $this->growthValue = $growthValue;
    $this->markDirty('growth_value', $growthValue);
}
```

---

## Dirty 追踪机制

Entity 使用 dirty 追踪机制，只更新修改过的字段：

```php
private array $dirtyFields = [];

private function markDirty(string $field, mixed $value = null, bool $force = false): void
{
    if ($force || \func_num_args() === 1) {
        $this->dirtyFields[$field] = true;
        return;
    }
    
    if (empty($value)) {
        return;
    }
    
    $this->dirtyFields[$field] = true;
}

public function toArray(): array
{
    $data = [];
    
    foreach (array_keys($this->dirtyFields) as $field) {
        $data[$field] = match ($field) {
            'nickname' => $this->nickname,
            'phone' => $this->phone,
            // ... 其他字段
            default => null,
        };
    }
    
    return $data;
}
```

---

## Domain Service 的 getEntity() 方法

```php
/**
 * 获取会员实体.
 *
 * 通过 ID 获取 Model，然后通过 Mapper 转换为 Entity.
 * 用于需要调用实体行为方法的场景.
 *
 * @param int $memberId 会员ID
 * @return MemberEntity 会员实体对象
 * @throws BusinessException 当会员不存在时
 */
public function getEntity(int $memberId): MemberEntity
{
    /** @var null|Member $model */
    $model = $this->repository->findById($memberId);
    
    if (! $model) {
        throw new BusinessException(ResultCode::NOT_FOUND, "会员不存在: ID={$memberId}");
    }
    
    return MemberMapper::fromModel($model);
}
```

**使用场景：**
- `updateStatus()` - 需要调用 `Entity::updateStatus()` 验证状态

---

## 异常处理

统一使用 `BusinessException`：

```php
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

// 资源不存在
throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');

// 业务规则验证失败
throw new BusinessException(ResultCode::FAIL, '昵称不能为空');
throw new BusinessException(ResultCode::FAIL, '手机号格式不正确');
throw new BusinessException(ResultCode::FAIL, '无效的会员状态');
throw new BusinessException(ResultCode::FAIL, '成长值不能小于0');
```

---

## 缓存策略

```php
private function forgetCache(int $id): void
{
    // 清理单个会员缓存
    $this->cache->delete("member:{$id}");
    
    // 清理列表缓存
    $this->cache->delete('members:list');
    
    // 清理统计缓存
    $this->cache->delete('members:stats');
    
    // 清理概览缓存
    $this->cache->delete('members:overview');
}
```

---

## 与简单 CRUD 的对比

| 特性 | Member（复杂） | MemberLevel（简单） |
|------|---------------|-------------------|
| Entity | ✅ 需要 | ❌ 不需要 |
| Mapper | ✅ 需要 | ❌ 不需要 |
| DTO::toArray() | ❌ 不需要 | ✅ 需要 |
| Entity::create/update | ✅ 需要 | ❌ 不需要 |
| Service::getEntity() | ✅ 需要 | ❌ 不需要 |
| Dirty 追踪 | ✅ 需要 | ❌ 不需要 |
| 业务规则验证 | Entity 层 | Request 层 |
| 行为方法 | 多个 | 无 |

---

## 关键改进点

### 1. Entity 包含业务逻辑
- ✅ `create()` 方法组装创建数据
- ✅ `update()` 方法组装更新数据
- ✅ `updateStatus()` 方法验证状态
- ✅ `syncTags()` 方法同步标签
- ✅ Setter 方法包含业务规则验证

### 2. Dirty 追踪机制
- ✅ 只更新修改过的字段
- ✅ 提高性能
- ✅ 避免覆盖未修改的数据

### 3. 统一异常处理
- ✅ 使用 `BusinessException`
- ✅ 使用 `ResultCode` 枚举
- ✅ 提供清晰的错误信息

### 4. 事务管理和缓存清理
- ✅ 所有写操作都有事务管理
- ✅ 操作成功后清理相关缓存
- ✅ 保证数据一致性

---

## 保留的复杂业务逻辑

Member 模块还保留了以下复杂业务逻辑（未改造）：

1. **小程序登录** - `miniProgramLogin()`
2. **绑定手机号** - `bindPhoneNumber()`

这些方法涉及第三方接口调用，保持原有实现。

---

## 总结

Member 模块是典型的**需要实体**的场景：
- ✅ 有复杂的业务规则验证
- ✅ 有多个业务行为方法
- ✅ 需要 dirty 追踪机制
- ✅ 有聚合根概念

通过改造，Member 模块现在完全符合 DDD 架构规范，具有清晰的分层结构和职责划分。

---

## 参考文档

- [DDD 架构规范](./DDD-ARCHITECTURE.md)
- [Member 模块改造总结](./MEMBER-MODULE-REFACTOR.md)
- [简单 CRUD 总结](./MEMBER-SIMPLE-CRUD-SUMMARY.md)

## 版本

1.0.0
