# MemberLevel 模块 DDD 改造总结

## 改造日期
2026-02-06

## 改造内容

### 1. 创建的新文件

#### Contract 接口
- ✅ `app/Domain/Member/Contract/MemberLevelInput.php` - 会员等级输入契约接口

#### DTO 类
- ✅ `app/Interface/Admin/DTO/Member/MemberLevelDto.php` - 会员等级数据传输对象

#### Mapper 类
- ✅ `app/Domain/Member/Mapper/MemberLevelMapper.php` - 会员等级映射器

### 2. 改造的文件

#### Interface 层
- ✅ `app/Interface/Admin/Request/Member/MemberLevelRequest.php`
  - 添加 `toDto()` 方法
  - 引入 `MemberLevelInput` 和 `MemberLevelDto`

- ✅ `app/Interface/Admin/Controller/Member/MemberLevelController.php`
  - 移除 `MemberLevelAssembler` 依赖
  - 使用 `Request::toDto()` 转换数据
  - 添加 `CurrentUser` 依赖注入

#### Application 层
- ✅ `app/Application/Commad/MemberLevelCommandService.php`
  - 参数从 `MemberLevelEntity` 改为 `MemberLevelInput`
  - 添加事务管理（使用 `Db::transaction`）
  - 添加缓存清理逻辑
  - 添加 `CacheInterface` 依赖注入

#### Domain 层
- ✅ `app/Domain/Member/Entity/MemberLevelEntity.php`
  - 添加 `create()` 行为方法
  - 添加 `update()` 行为方法
  - 添加 dirty 追踪机制
  - 所有 setter 方法添加业务规则验证
  - 所有 setter 方法返回 `self` 支持链式调用
  - `toArray()` 方法支持 dirty 追踪

- ✅ `app/Domain/Member/Service/MemberLevelService.php`
  - `create()` 方法改为接收 `MemberLevelInput`
  - `update()` 方法改为接收 `MemberLevelInput`
  - 添加 `getEntity()` 方法
  - 使用 `MemberLevelMapper` 进行转换

## 改造后的完整流程

### 创建流程
```
用户请求
    ↓
MemberLevelController::store()
    ↓
MemberLevelRequest::toDto(null, operatorId)
    ↓
MemberLevelDto (implements MemberLevelInput)
    ↓
MemberLevelCommandService::create(input)
    ↓
Db::transaction() 开启事务
    ↓
MemberLevelService::create(dto)
    ↓
MemberLevelMapper::getNewEntity()
    ↓
MemberLevelEntity::create(dto)
    ↓
MemberLevelEntity::toArray() (dirty 追踪)
    ↓
MemberLevelRepository::save(entity)
    ↓
提交事务 + 清理缓存
    ↓
返回响应
```

### 更新流程
```
用户请求
    ↓
MemberLevelController::update(id)
    ↓
MemberLevelRequest::toDto(id, operatorId)
    ↓
MemberLevelDto (implements MemberLevelInput)
    ↓
MemberLevelCommandService::update(input)
    ↓
Db::transaction() 开启事务
    ↓
MemberLevelService::update(dto)
    ↓
MemberLevelRepository::findById(id) 获取 Model
    ↓
MemberLevelMapper::fromModel(model) 转换为 Entity
    ↓
MemberLevelEntity::update(dto)
    ↓
MemberLevelEntity::toArray() (只返回修改的字段)
    ↓
MemberLevelRepository::updateEntity(entity)
    ↓
提交事务 + 清理缓存
    ↓
返回响应
```

## 符合的 DDD 规范

### ✅ 已实现的规范

1. **DTO 和 Contract 层**
   - DTO 实现 Contract 接口
   - Request 提供 `toDto()` 方法

2. **Mapper 模式**
   - 提供 `fromModel()` 方法
   - 提供 `getNewEntity()` 方法

3. **Entity 行为方法**
   - `create(dto)` 接收 DTO 并组装数据
   - `update(dto)` 接收 DTO 并更新数据
   - 所有 setter 包含业务规则验证

4. **Dirty 追踪机制**
   - Entity 使用 `markDirty()` 标记修改
   - `toArray()` 只返回修改过的字段

5. **Domain Service 职责**
   - 提供 `getEntity()` 方法
   - 使用 Mapper 进行转换
   - 包含业务逻辑

6. **Application 层职责**
   - 事务管理
   - 缓存清理
   - 不包含业务逻辑

7. **异常处理**
   - 使用标准 PHP 异常（`\DomainException`, `\RuntimeException`）

## 业务规则验证

Entity 中添加的业务规则：

1. **等级名称验证**
   - 不能为空
   - 自动 trim 处理

2. **等级值验证**
   - 必须大于 0

3. **成长值验证**
   - 最低成长值不能小于 0
   - 最高成长值不能小于最低成长值

4. **折扣率验证**
   - 必须在 0-100 之间

5. **积分倍率验证**
   - 不能小于 0

6. **状态验证**
   - 只能是 'active' 或 'inactive'

## 缓存策略

CommandService 中实现的缓存清理：

```php
private function forgetCache(int $id): void
{
    $this->cache->delete("member_level:{$id}");
    $this->cache->delete('member_levels:list');
}
```

## 可以删除的文件

改造完成后，以下文件可以删除（已不再使用）：

- ❌ `app/Application/Mapper/MemberLevelAssembler.php`

## 后续改造建议

### 其他 Member 子模块

按照相同的模式改造以下模块：

1. **MemberTag 模块**（简单 CRUD）
   - 创建 Contract 和 DTO
   - 创建 Mapper
   - 改造 Entity（添加 create/update 方法）
   - 改造 Service（添加 getEntity 方法）
   - 改造 CommandService（添加事务和缓存）

2. **Member 模块**（复杂业务逻辑）
   - 创建 Contract 和 DTO
   - 完善 Mapper
   - 完善 Entity 的业务行为方法
   - 改造 Service
   - 改造 CommandService

3. **MemberAccount 模块**（钱包管理）
   - 创建 Contract 和 DTO
   - 创建 Mapper
   - 完善 Entity 的业务行为方法
   - 改造 Service
   - 完善 CommandService（已有事务和事件）

## 测试建议

改造后需要测试的功能：

1. ✅ 创建会员等级
2. ✅ 更新会员等级
3. ✅ 删除会员等级
4. ✅ 查询会员等级
5. ✅ 业务规则验证（等级名称、等级值、成长值等）
6. ✅ 事务回滚测试
7. ✅ 缓存清理测试

## 参考文档

- [DDD 架构规范](./DDD-ARCHITECTURE.md)
- [领域驱动设计](https://en.wikipedia.org/wiki/Domain-driven_design)

## 改造者

开发团队

## 版本

1.0.0
