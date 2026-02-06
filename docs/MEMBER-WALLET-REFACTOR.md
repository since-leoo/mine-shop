# MemberAccount（会员钱包）模块改造总结

## 改造日期
2026-02-06

## 模块类型
**复杂业务逻辑 - 需要实体 + 值对象 + 领域事件**

## 判断标准

✅ **需要实体的原因：**
- 有复杂的业务规则验证（余额不能为负数）
- 有业务行为方法（adjustBalance、changeBalance）
- 有领域不变量（余额不能为负）
- 涉及金额计算，需要精确控制
- 有领域事件（MemberBalanceAdjusted）

✅ **需要值对象的原因：**
- 封装余额变更的结果（变更前、变更后、变更金额）
- 提供不可变的数据传递
- 增强类型安全

---

## 改造内容

### 创建的文件

1. **ValueObject（值对象）**
   - `app/Domain/Member/ValueObject/BalanceChangeVo.php` - 余额变更值对象

2. **Contract 接口**
   - `app/Domain/Member/Contract/MemberWalletInput.php` - 钱包输入契约接口

3. **DTO 类**
   - `app/Interface/Admin/DTO/Member/MemberWalletDto.php` - 钱包数据传输对象

4. **Mapper 类**
   - `app/Domain/Member/Mapper/MemberWalletMapper.php` - 钱包映射器

### 改造的文件

1. **Entity 层**
   - `app/Domain/Member/Entity/MemberWalletEntity.php`
     - 添加 `adjustBalance(MemberWalletInput $dto)` 行为方法
     - 改造 `changeBalance()` 方法使用 `BusinessException`
     - 添加领域不变量验证（余额不能为负）
     - 返回 `BalanceChangeVo` 值对象

2. **Service 层**
   - `app/Domain/Member/Service/MemberWalletService.php`
     - `adjustBalance()` 方法改为接收 `MemberWalletInput`
     - 添加 `getEntity()` 方法
     - 使用 `BusinessException`
     - 返回 `BalanceChangeVo`

3. **Repository 层**
   - `app/Domain/Member/Repository/MemberWalletRepository.php`
     - 添加 `findByMemberIdAndType()` 方法

4. **CommandService 层**
   - `app/Application/Commad/MemberAccountCommandService.php`
     - 参数从 `MemberWalletEntity` 改为 `MemberWalletInput`
     - 添加缓存清理逻辑
     - 添加 `CacheInterface` 依赖注入
     - 使用 `BalanceChangeVo` 处理结果

5. **Request 层**
   - `app/Interface/Admin/Request/Member/MemberAccountRequest.php`
     - 添加 `toDto()` 方法

6. **Controller 层**
   - `app/Interface/Admin/Controller/Member/MemberAccountController.php`
     - 移除 `MemberAccountAssembler` 依赖
     - 使用 `Request::toDto()` 转换数据

---

## 完整流程

### 调整余额流程
```
用户请求
    ↓
MemberAccountController::adjust()
    ↓
MemberAccountRequest::toDto(operatorId)
    ↓
MemberWalletDto (implements MemberWalletInput)
    ↓
MemberAccountCommandService::adjustBalance(input, operator)
    ↓
Db::transaction() 开启事务
    ↓
MemberWalletService::adjustBalance(dto)
    ↓
MemberWalletService::getEntity(memberId, type)
    ↓
MemberWalletRepository::findByMemberIdAndType()
    ↓
MemberWalletMapper::fromModel(model)
    ↓
MemberWalletEntity::adjustBalance(dto)
    ↓
MemberWalletEntity::changeBalance() 验证领域不变量
    ↓
返回 BalanceChangeVo
    ↓
MemberWalletRepository::save(entity)
    ↓
发布领域事件 MemberBalanceAdjusted
    ↓
RecordMemberBalanceLogListener 记录交易日志
    ↓
提交事务 + 清理缓存
    ↓
返回响应
```

---

## 值对象（ValueObject）

### BalanceChangeVo

封装余额变更的结果，提供不可变的数据传递。

```php
final class BalanceChangeVo
{
    public function __construct(
        public readonly int $memberId,
        public readonly string $walletType,
        public readonly float $beforeBalance,
        public readonly float $afterBalance,
        public readonly float $changeAmount,
        public readonly bool $success = true,
        public readonly string $message = '',
    ) {}
    
    public static function success(...): self;
    public static function fail(string $message): self;
}
```

**特点：**
- ✅ 不可变（readonly 属性）
- ✅ 类型安全
- ✅ 提供工厂方法（success、fail）
- ✅ 封装业务结果

**使用场景：**
- Entity 的行为方法返回值
- Service 层的返回值
- 跨层传递数据

---

## Entity 行为方法

### adjustBalance(MemberWalletInput $dto)

调整余额的行为方法，接收 DTO 并执行余额变更。

```php
public function adjustBalance(MemberWalletInput $dto): BalanceChangeVo
{
    $this->setChangeBalance($dto->getValue());
    $this->setSource($dto->getSource());
    $this->setRemark($dto->getRemark());
    
    // 调用余额变更逻辑
    $this->changeBalance();
    
    return BalanceChangeVo::success(
        memberId: $this->memberId,
        walletType: $this->type,
        beforeBalance: $this->beforeBalance,
        afterBalance: $this->afterBalance,
        changeAmount: $this->changeBalance
    );
}
```

### changeBalance()

执行余额变更的核心方法，包含领域不变量验证。

```php
public function changeBalance(): void
{
    if ($this->changeBalance === 0.0) {
        throw new BusinessException(ResultCode::FAIL, '变动值不能为 0');
    }
    
    $this->setBeforeBalance($this->balance);
    $after = (float) bcadd((string) $this->balance, (string) $this->changeBalance, 2);
    
    // 领域不变量：余额不能为负数
    if ($after < 0) {
        throw new BusinessException(
            ResultCode::FAIL,
            sprintf('余额不足，当前余额：%.2f，变动金额：%.2f', $this->balance, $this->changeBalance)
        );
    }
    
    $this->setAfterBalance($after);
    $this->balance = $after;
    
    // 更新累计充值或消费
    if ($this->changeBalance > 0) {
        $this->totalRecharge = (float) bcadd((string) $this->totalRecharge, (string) $this->changeBalance, 2);
    } else {
        $consume = (string) abs($this->changeBalance);
        $this->totalConsume = (float) bcadd((string) $this->totalConsume, $consume, 2);
    }
}
```

**业务规则：**
- ✅ 变动值不能为 0
- ✅ 余额不能为负数（领域不变量）
- ✅ 使用 bcadd 进行精确计算
- ✅ 自动更新累计充值/消费

---

## 领域事件

### MemberBalanceAdjusted

会员余额变更事件，在余额调整后发布。

```php
final class MemberBalanceAdjusted
{
    public function __construct(
        public readonly int $memberId,
        public readonly ?int $walletId,
        public readonly string $walletType,
        public readonly float $changeAmount,
        public readonly float $beforeBalance,
        public readonly float $afterBalance,
        public readonly string $source = 'manual',
        public readonly string $remark = '',
        public readonly array $operator = [],
        public readonly ?string $relatedType = null,
        public readonly ?int $relatedId = null,
    ) {}
}
```

### RecordMemberBalanceLogListener

监听余额变更事件，记录交易日志。

```php
final class RecordMemberBalanceLogListener implements ListenerInterface
{
    public function listen(): array
    {
        return [MemberBalanceAdjusted::class];
    }
    
    public function process(object $event): void
    {
        // 记录交易日志到 member_wallet_transactions 表
        $this->transactionRepository->create([...]);
    }
}
```

**事件驱动的好处：**
- ✅ 解耦业务逻辑
- ✅ 异步处理日志记录
- ✅ 易于扩展（可添加更多监听器）
- ✅ 符合 DDD 领域事件模式

---

## Domain Service 的 getEntity() 方法

```php
/**
 * 获取钱包实体.
 *
 * 通过会员ID和钱包类型获取 Model，然后通过 Mapper 转换为 Entity.
 *
 * @param int $memberId 会员ID
 * @param string $type 钱包类型
 * @return MemberWalletEntity 钱包实体对象
 * @throws BusinessException 当钱包不存在时
 */
public function getEntity(int $memberId, string $type): MemberWalletEntity
{
    /** @var null|MemberWallet $model */
    $model = $this->walletRepository->findByMemberIdAndType($memberId, $type);
    
    if (! $model) {
        throw new BusinessException(
            ResultCode::NOT_FOUND,
            "会员钱包不存在: 会员ID={$memberId}, 类型={$type}"
        );
    }
    
    return MemberWalletMapper::fromModel($model);
}
```

---

## 缓存策略

```php
private function forgetCache(int $memberId): void
{
    // 清理会员钱包缓存
    $this->cache->delete("member_wallet:{$memberId}");
    
    // 清理余额缓存
    $this->cache->delete("member_wallet:{$memberId}:balance");
    
    // 清理积分缓存
    $this->cache->delete("member_wallet:{$memberId}:points");
    
    // 清理交易列表缓存
    $this->cache->delete('member_wallet_transactions:list');
}
```

---

## 精确计算

使用 `bcadd` 进行金额计算，避免浮点数精度问题：

```php
// ✅ 正确：使用 bcadd
$after = (float) bcadd((string) $this->balance, (string) $this->changeBalance, 2);

// ❌ 错误：直接相加
$after = $this->balance + $this->changeBalance;
```

**原因：**
- PHP 浮点数有精度问题
- 金额计算必须精确到分
- bcadd 提供任意精度的数学运算

---

## 验证职责划分

### Request 层验证
```php
public function adjustRules(): array
{
    return [
        'member_id' => ['required', 'integer', 'min:1'],
        'value' => ['required', 'numeric', 'between:-1000000,1000000', 'not_in:0'],
        'source' => ['nullable', 'string', 'max:50'],
        'type' => ['required', Rule::in(['balance', 'points'])],
        'remark' => ['nullable', 'string', 'max:255'],
    ];
}
```

**验证内容：**
- ✅ 格式验证（numeric）
- ✅ 范围验证（between）
- ✅ 枚举验证（in）
- ✅ 非零验证（not_in:0）

### Entity 层验证
```php
public function changeBalance(): void
{
    // 业务规则验证
    if ($this->changeBalance === 0.0) {
        throw new BusinessException(ResultCode::FAIL, '变动值不能为 0');
    }
    
    // 领域不变量验证
    if ($after < 0) {
        throw new BusinessException(ResultCode::FAIL, '余额不足');
    }
}
```

**验证内容：**
- ✅ 业务规则（变动值不能为0）
- ✅ 领域不变量（余额不能为负）

---

## 关键改进点

### 1. 引入值对象
- ✅ `BalanceChangeVo` 封装余额变更结果
- ✅ 不可变对象，类型安全
- ✅ 提供工厂方法

### 2. Entity 包含业务逻辑
- ✅ `adjustBalance()` 方法组装数据并执行变更
- ✅ `changeBalance()` 方法验证领域不变量
- ✅ 使用 `BusinessException`
- ✅ 精确计算（bcadd）

### 3. 领域事件
- ✅ `MemberBalanceAdjusted` 事件
- ✅ `RecordMemberBalanceLogListener` 监听器
- ✅ 解耦业务逻辑

### 4. 统一异常处理
- ✅ 使用 `BusinessException`
- ✅ 使用 `ResultCode` 枚举
- ✅ 提供详细的错误信息

### 5. 事务管理和缓存清理
- ✅ 所有写操作都有事务管理
- ✅ 操作成功后清理相关缓存
- ✅ 保证数据一致性

---

## 与其他模块的对比

| 特性 | MemberWallet（钱包） | Member（会员） | MemberLevel（等级） |
|------|---------------------|---------------|-------------------|
| Entity | ✅ 需要 | ✅ 需要 | ❌ 不需要 |
| Mapper | ✅ 需要 | ✅ 需要 | ❌ 不需要 |
| ValueObject | ✅ 需要 | ❌ 不需要 | ❌ 不需要 |
| 领域事件 | ✅ 需要 | ❌ 不需要 | ❌ 不需要 |
| 业务规则验证 | Entity 层 | Entity 层 | Request 层 |
| 领域不变量 | ✅ 有（余额≥0） | ❌ 无 | ❌ 无 |
| 精确计算 | ✅ bcadd | ❌ 不需要 | ❌ 不需要 |

---

## 总结

MemberAccount（会员钱包）模块是典型的**需要实体 + 值对象 + 领域事件**的场景：
- ✅ 有复杂的业务规则验证
- ✅ 有领域不变量（余额不能为负）
- ✅ 需要精确计算（金额）
- ✅ 有领域事件（余额变更）
- ✅ 需要值对象封装结果

通过改造，MemberAccount 模块现在完全符合 DDD 架构规范，具有清晰的分层结构、类型安全的数据传递、以及完善的事件驱动机制。

---

## 参考文档

- [DDD 架构规范](./DDD-ARCHITECTURE.md)
- [Member 模块改造总结](./MEMBER-MODULE-REFACTOR.md)
- [验证职责划分](./VALIDATION-RESPONSIBILITY.md)

## 版本

1.0.0
