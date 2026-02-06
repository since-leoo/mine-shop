# 缓存使用原则

## 核心原则

**不要强行添加缓存逻辑！**

缓存是性能优化手段，不是架构的必需部分。只有在确实需要的时候才添加缓存。

---

## 什么时候需要缓存？

### ✅ 需要缓存的场景

1. **原本就有缓存业务**
   - 如果原始代码中已经有缓存逻辑，改造时保留并完善

2. **高频读取的数据**
   - 用户信息（频繁验证权限）
   - 配置信息（每次请求都读取）
   - 字典数据（下拉选项等）

3. **计算成本高的数据**
   - 复杂的统计数据
   - 聚合查询结果
   - 需要多次数据库查询的数据

4. **变更频率低的数据**
   - 系统配置
   - 地区数据
   - 分类数据

### ❌ 不需要缓存的场景

1. **原本没有缓存业务**
   - 不要为了"完整性"而添加缓存

2. **变更频繁的数据**
   - 订单状态
   - 库存数量
   - 实时数据

3. **读取频率低的数据**
   - 后台管理的 CRUD 操作
   - 低频访问的详情页

4. **数据一致性要求高的场景**
   - 金额相关
   - 库存扣减
   - 支付流程

---

## 改造时的判断标准

### 第一步：检查原始代码

```php
// 查看原始 CommandService 是否有 CacheInterface 依赖
public function __construct(
    private readonly MemberLevelService $memberLevelService,
    private readonly CacheInterface $cache  // ← 有这个依赖吗？
) {}
```

**判断：**
- ✅ 有 `CacheInterface` 依赖 → 保留并完善缓存逻辑
- ❌ 没有 `CacheInterface` 依赖 → 不要添加缓存

### 第二步：检查是否有缓存清理逻辑

```php
// 查看原始代码是否有 forgetCache 或类似方法
private function forgetCache(int $id): void
{
    $this->cache->delete("member_level:{$id}");
}
```

**判断：**
- ✅ 有缓存清理逻辑 → 保留并完善
- ❌ 没有缓存清理逻辑 → 不要添加

---

## 正确的做法

### 案例 1：原本没有缓存（MemberLevel）

#### ❌ 错误做法
```php
final class MemberLevelCommandService
{
    public function __construct(
        private readonly MemberLevelService $memberLevelService,
        private readonly CacheInterface $cache  // ❌ 强行添加
    ) {}
    
    public function create(MemberLevelInput $input): array
    {
        $level = Db::transaction(fn () => $this->memberLevelService->create($input));
        
        // ❌ 强行添加缓存清理
        $this->forgetCache((int) $level->id);
        
        return $level->toArray();
    }
    
    private function forgetCache(int $id): void
    {
        $this->cache->delete("member_level:{$id}");
        $this->cache->delete('member_levels:list');
    }
}
```

#### ✅ 正确做法
```php
final class MemberLevelCommandService
{
    public function __construct(
        private readonly MemberLevelService $memberLevelService
        // 不添加 CacheInterface
    ) {}
    
    public function create(MemberLevelInput $input): array
    {
        // 只需要事务管理
        $level = Db::transaction(fn () => $this->memberLevelService->create($input));
        
        return $level->toArray();
    }
}
```

---

### 案例 2：原本就有缓存（User）

#### ✅ 正确做法
```php
final class UserCommandService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly CacheInterface $cache  // ✅ 原本就有
    ) {}
    
    public function create(UserInput $input): User
    {
        // 1. 事务管理
        $user = Db::transaction(fn () => $this->userService->create($input));
        
        // 2. 缓存清理（因为原本就有缓存）
        $this->forgetCache((int) $user->id);
        
        return $user;
    }
    
    private function forgetCache(int $id): void
    {
        $this->cache->delete("user:{$id}");
        $this->cache->delete('users:list');
        $this->cache->delete('users:permissions');
    }
}
```

---

## Application 层的职责

### CommandService 的核心职责

1. **事务管理** - ✅ 必须
2. **领域事件发布** - ✅ 如果有领域事件
3. **缓存清理** - ⚠️ 只有原本就有缓存时才需要

### 不是 CommandService 的职责

- ❌ 添加不必要的缓存
- ❌ 为了"完整性"而添加功能
- ❌ 过度设计

---

## Member 模块的缓存情况

根据改造后的实际情况：

| 模块 | 原本有缓存？ | 改造后 |
|------|------------|--------|
| MemberLevel | ❌ 没有 | ❌ 不添加缓存 |
| MemberTag | ❌ 没有 | ❌ 不添加缓存 |
| Member | ❌ 没有 | ❌ 不添加缓存 |
| MemberAccount | ❌ 没有 | ❌ 不添加缓存 |

**结论：** Member 模块的所有子模块都不需要缓存逻辑。

---

## 何时添加缓存？

### 添加缓存的时机

1. **性能测试发现瓶颈**
   - 通过监控发现某个查询很慢
   - 数据库压力大

2. **业务需求明确**
   - 产品明确要求缓存
   - 有具体的性能指标

3. **数据特征适合**
   - 读多写少
   - 变更频率低
   - 数据量大

### 添加缓存的步骤

1. **先测量，再优化**
   - 不要过早优化
   - 用数据说话

2. **选择合适的缓存策略**
   - 缓存时间
   - 缓存粒度
   - 缓存更新策略

3. **完善缓存清理**
   - 写操作后清理
   - 考虑缓存穿透
   - 考虑缓存雪崩

---

## 总结

### 核心原则

1. **不要强行添加缓存**
   - 原本没有就不要添加
   - 不要为了"完整性"而添加

2. **保持简单**
   - 只做必要的事情
   - 避免过度设计

3. **按需添加**
   - 有性能问题时再添加
   - 有明确需求时再添加

4. **遵循原有设计**
   - 原本有缓存就保留
   - 原本没有就不添加

### 快速判断

**问自己：原始代码有缓存吗？**

- ✅ 有 → 保留并完善
- ❌ 没有 → 不要添加

---

## 参考文档

- [DDD 架构规范](./DDD-ARCHITECTURE.md)
- [Member 模块改造总结](./MEMBER-MODULE-REFACTOR.md)

## 版本

1.0.0
