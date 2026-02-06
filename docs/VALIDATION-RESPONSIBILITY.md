# 验证职责划分指南

## 核心原则

**能在 Request 层验证的，就在 Request 层验证。**

Request 层是第一道防线，Entity 层专注于业务规则。

---

## Request 层验证（优先）

### 应该在 Request 层验证的内容

✅ **格式验证**
- 邮箱格式：`'email'`
- 手机号格式：`'regex:/^1[3-9]\d{9}$/'`
- URL 格式：`'url'`
- 日期格式：`'date'`, `'date_format:Y-m-d'`
- IP 地址：`'ip'`
- JSON 格式：`'json'`

✅ **长度验证**
- 字符串长度：`'max:100'`, `'min:6'`, `'between:6,20'`
- 数组长度：`'max:10'`, `'min:1'`

✅ **类型验证**
- 整数：`'integer'`
- 数字：`'numeric'`
- 字符串：`'string'`
- 布尔值：`'boolean'`
- 数组：`'array'`

✅ **必填验证**
- 必填：`'required'`
- 条件必填：`'required_if:field,value'`
- 至少一个：`'required_without:field'`

✅ **范围验证**
- 最小值：`'min:0'`
- 最大值：`'max:100'`
- 区间：`'between:0,100'`
- 大于等于：`'gte:field'`

✅ **枚举验证**
- 枚举值：`Rule::in(['active', 'inactive'])`
- 存在性：`'exists:table,column'`

✅ **唯一性验证**
- 唯一：`'unique:table,column'`
- 唯一（排除自己）：`Rule::unique('table')->ignore($id)`

✅ **正则表达式验证**
- 自定义格式：`'regex:/pattern/'`

### Request 验证示例

```php
public function storeRules(): array
{
    return [
        // 格式验证
        'nickname' => ['required', 'string', 'max:100'],
        'phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/', 'unique:members,phone'],
        'email' => ['nullable', 'email', 'max:255'],
        'birthday' => ['nullable', 'date'],
        
        // 类型和范围验证
        'age' => ['nullable', 'integer', 'min:0', 'max:150'],
        'growth_value' => ['nullable', 'integer', 'min:0'],
        
        // 枚举验证
        'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
        'gender' => ['nullable', Rule::in(['unknown', 'male', 'female'])],
        'level' => ['nullable', Rule::in(['bronze', 'silver', 'gold', 'diamond'])],
        
        // 关联验证
        'tags' => ['nullable', 'array'],
        'tags.*' => ['integer', 'exists:member_tags,id'],
    ];
}

public function updateRules(): array
{
    $id = (int) $this->route('id');
    
    return [
        'nickname' => ['nullable', 'string', 'max:100'],
        'phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/', Rule::unique('members')->ignore($id)],
        'email' => ['nullable', 'email', 'max:255', Rule::unique('members')->ignore($id)],
        // ...
    ];
}
```

---

## Entity 层验证（业务规则）

### 应该在 Entity 层验证的内容

✅ **复杂的业务规则**
- 折扣率必须在会员等级允许的范围内
- VIP 会员才能使用某些功能
- 特定条件下的限制

✅ **跨字段的业务逻辑**
- 最高成长值必须大于最低成长值
- 结束时间必须晚于开始时间（且有业务含义）
- 价格必须在成本价之上

✅ **状态转换规则**
- 已支付的订单不能取消
- 已发货的订单不能修改地址
- 已完成的订单不能退款

✅ **领域不变量**
- 账户余额不能为负数
- 库存不能为负数
- 积分不能为负数

✅ **需要查询数据库的业务规则**
- 检查库存是否充足
- 检查优惠券是否可用
- 检查用户权限

### Entity 验证示例

```php
// ✅ 正确：验证业务规则
public function setGrowthMax(?int $value): self
{
    // 跨字段业务逻辑
    if ($value !== null && $this->growthMin !== null && $value < $this->growthMin) {
        throw new BusinessException(ResultCode::FAIL, '最高成长值不能小于最低成长值');
    }
    
    $this->growthMax = $value;
    $this->markDirty('growth_value_max');
    return $this;
}

// ✅ 正确：验证状态转换规则
public function updateStatus(string $newStatus): self
{
    // 状态转换规则
    if ($this->status === 'paid' && $newStatus === 'cancelled') {
        throw new BusinessException(ResultCode::FAIL, '已支付的订单不能取消');
    }
    
    if ($this->status === 'completed') {
        throw new BusinessException(ResultCode::FAIL, '已完成的订单不能修改状态');
    }
    
    $this->status = $newStatus;
    $this->markDirty('status');
    return $this;
}

// ✅ 正确：验证领域不变量
public function deductBalance(int $amount): self
{
    $newBalance = $this->balance - $amount;
    
    // 领域不变量
    if ($newBalance < 0) {
        throw new BusinessException(ResultCode::FAIL, '余额不足');
    }
    
    $this->balance = $newBalance;
    $this->markDirty('balance');
    return $this;
}

// ❌ 错误：不要在 Entity 中验证格式
public function setPhone(?string $phone): void
{
    // ❌ 格式验证应该在 Request 层
    if ($phone !== null && ! preg_match('/^1[3-9]\d{9}$/', $phone)) {
        throw new BusinessException(ResultCode::FAIL, '手机号格式不正确');
    }
    
    $this->phone = $phone;
    $this->markDirty('phone');
}

// ✅ 正确：Entity 不验证格式
public function setPhone(?string $phone): void
{
    $this->phone = $phone;
    $this->markDirty('phone');
}
```

---

## 验证层次对比表

| 验证类型 | Request 层 | Entity 层 | 说明 |
|---------|-----------|----------|------|
| 格式验证（邮箱、手机号等） | ✅ 优先 | ❌ 不应该 | Request 层处理 |
| 长度验证 | ✅ 优先 | ❌ 不应该 | Request 层处理 |
| 类型验证 | ✅ 优先 | ❌ 不应该 | Request 层处理 |
| 必填验证 | ✅ 优先 | ❌ 不应该 | Request 层处理 |
| 简单范围验证（min/max） | ✅ 优先 | ❌ 不应该 | Request 层处理 |
| 枚举验证（固定值列表） | ✅ 优先 | ⚠️ 看情况 | 简单枚举在 Request，业务枚举在 Entity |
| 唯一性验证 | ✅ 优先 | ❌ 不应该 | Request 层处理 |
| 跨字段业务逻辑 | ⚠️ 简单的可以 | ✅ 复杂的必须 | 复杂的在 Entity |
| 状态转换规则 | ❌ 不应该 | ✅ 必须 | Entity 层处理 |
| 领域不变量 | ❌ 不应该 | ✅ 必须 | Entity 层处理 |
| 需要查询数据库的规则 | ❌ 不应该 | ✅ 必须 | Entity 或 Service 层处理 |

---

## 实际案例对比

### 案例 1：会员昵称验证

#### ❌ 错误做法
```php
// Request 层
public function storeRules(): array
{
    return [
        'nickname' => ['required', 'string'], // 缺少长度验证
    ];
}

// Entity 层
public function setNickname(?string $nickname): void
{
    // ❌ 在 Entity 中验证格式和长度
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
    $this->markDirty('nickname');
}
```

#### ✅ 正确做法
```php
// Request 层：验证格式和长度
public function storeRules(): array
{
    return [
        'nickname' => ['required', 'string', 'max:100'],
    ];
}

// Entity 层：不需要验证
public function setNickname(?string $nickname): void
{
    $this->nickname = $nickname;
    $this->markDirty('nickname');
}
```

---

### 案例 2：会员等级成长值验证

#### ✅ 正确做法
```php
// Request 层：验证基本范围
public function storeRules(): array
{
    return [
        'growth_value_min' => ['required', 'integer', 'min:0'],
        'growth_value_max' => ['nullable', 'integer', 'gte:growth_value_min'], // 简单的跨字段验证
    ];
}

// Entity 层：验证业务规则（更复杂的跨字段逻辑）
public function setGrowthMax(?int $value): self
{
    // 业务规则：考虑已有数据的情况
    if ($value !== null && $this->growthMin !== null && $value < $this->growthMin) {
        throw new BusinessException(ResultCode::FAIL, '最高成长值不能小于最低成长值');
    }
    
    $this->growthMax = $value;
    $this->markDirty('growth_value_max');
    return $this;
}
```

---

### 案例 3：订单状态转换验证

#### ✅ 正确做法
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
    // 业务规则：状态转换限制
    if ($this->status === 'paid' && $newStatus === 'cancelled') {
        throw new BusinessException(ResultCode::FAIL, '已支付的订单不能取消');
    }
    
    if ($this->status === 'completed') {
        throw new BusinessException(ResultCode::FAIL, '已完成的订单不能修改状态');
    }
    
    $this->status = $newStatus;
    $this->markDirty('status');
    return $this;
}
```

---

### 案例 4：账户余额扣减

#### ✅ 正确做法
```php
// Request 层：验证金额格式
public function deductRules(): array
{
    return [
        'amount' => ['required', 'integer', 'min:1'], // 基本验证
    ];
}

// Entity 层：验证领域不变量
public function deductBalance(int $amount): self
{
    $newBalance = $this->balance - $amount;
    
    // 领域不变量：余额不能为负
    if ($newBalance < 0) {
        throw new BusinessException(ResultCode::FAIL, '余额不足，当前余额：' . $this->balance);
    }
    
    $this->balance = $newBalance;
    $this->markDirty('balance');
    return $this;
}
```

---

## 总结

### 验证职责划分原则

1. **Request 层是第一道防线**
   - 验证所有格式、类型、长度、范围等基础验证
   - 使用 Laravel 验证规则
   - 快速失败，提前拦截无效请求

2. **Entity 层是业务规则守护者**
   - 只验证业务规则和领域不变量
   - 保护领域模型的完整性
   - 确保业务逻辑的正确性

3. **避免重复验证**
   - Request 层验证过的，Entity 层不需要再验证
   - 保持代码简洁，避免冗余

4. **保持 Entity 纯粹**
   - Entity 专注于业务逻辑
   - 不关心数据格式
   - 不处理 HTTP 相关的验证

### 快速判断方法

**问自己：这个验证是否与业务规则相关？**

- ✅ 是 → Entity 层验证
- ❌ 否 → Request 层验证

**示例：**
- "手机号格式是否正确？" → ❌ 不是业务规则 → Request 层
- "已支付的订单能否取消？" → ✅ 是业务规则 → Entity 层
- "昵称长度是否超过100？" → ❌ 不是业务规则 → Request 层
- "余额是否足够扣减？" → ✅ 是业务规则 → Entity 层

---

## 参考文档

- [DDD 架构规范](./DDD-ARCHITECTURE.md)
- [Member 模块改造总结](./MEMBER-MODULE-REFACTOR.md)

## 版本

1.0.0
