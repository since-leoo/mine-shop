# 实现计划：订单结算 DDD 重构

## 概述

按 DDD 架构规范重构订单预览/提交流程，修复 Bug，补全 Contract→DTO→Request 链路，改造策略扩展性，加强验证规则。采用自底向上的实现顺序：先 Domain 层，再 Interface 层，最后 Application 层串联。

## Tasks

- [x] 1. Domain 层 Contract 接口与 Entity 改造
  - [x] 1.1 创建 OrderPreviewInput 和 OrderSubmitInput 契约接口
    - 在 `app/Domain/Order/Contract/` 下创建 `OrderPreviewInput.php` 和 `OrderSubmitInput.php`
    - OrderSubmitInput 继承 OrderPreviewInput，增加 getTotalAmount()、getUserName()
    - _Requirements: 4.1, 4.2_
  - [x] 1.2 改造 OrderEntity：替换 create() 为 initFromInput()，新增 verifyPrice()
    - 将 `create(OrderCreateDto)` 替换为 `initFromInput(OrderPreviewInput)`
    - 新增 `verifyPrice(int $frontendAmountCent)` 方法，使用分为单位比较
    - _Requirements: 1.3, 10.1, 10.2, 10.3_
  - [x] 1.3 Property 测试：verifyPrice 价格校验
    - **Property 9: 价格校验（分为单位）**
    - **Validates: Requirements 10.1, 10.2, 10.3**

- [x] 2. 策略接口扩展与工厂改造
  - [x] 2.1 扩展 OrderTypeStrategyInterface，新增 applyCoupon() 和 adjustPrice()
    - 在接口中声明 `applyCoupon(OrderEntity, array)` 和 `adjustPrice(OrderEntity)`
    - _Requirements: 9.1, 11.1_
  - [x] 2.2 改造 NormalOrderStrategy 实现新接口方法
    - applyCoupon 和 adjustPrice 为空实现（直通）
    - _Requirements: 9.3, 11.3_
  - [x] 2.3 改造 OrderTypeStrategyFactory 为自动注册模式
    - 构造函数接收 `OrderTypeStrategyInterface[]` 数组
    - 在 `config/autoload/dependencies.php` 注册工厂闭包
    - _Requirements: 8.1, 8.2, 8.3_
  - [x] 2.4 Property 测试：策略工厂注册与查找
    - **Property 7: 策略工厂注册与查找**
    - **Validates: Requirements 8.1, 8.2, 8.3**
  - [x] 2.5 Property 测试：NormalOrderStrategy 钩子不改变价格
    - **Property 8: NormalOrderStrategy 钩子方法不改变价格**
    - **Validates: Requirements 9.3, 11.3**

- [x] 3. Checkpoint - 确保 Domain 层改造完成
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Interface 层 DTO、Request 改造
  - [x] 4.1 创建 OrderPreviewDto 和 OrderCommitDto
    - 在 `app/Interface/Api/DTO/Order/` 下创建两个 DTO 类
    - OrderPreviewDto 实现 OrderPreviewInput，OrderCommitDto 继承 OrderPreviewDto 并实现 OrderSubmitInput
    - getBuyerRemark() 从 store_info_list 提取 remark
    - _Requirements: 4.3, 4.4_
  - [x] 4.2 改造 OrderPreviewRequest：移除 prepareForValidation，新增 toDto()，加强验证规则
    - 删除整个 prepareForValidation() 方法
    - 新增 `toDto(int $memberId): OrderPreviewInput`
    - 加强验证规则：user_address 子字段类型和长度限制
    - _Requirements: 4.5, 5.1, 7.1, 7.4, 7.5_
  - [x] 4.3 改造 OrderCommitRequest：移除 prepareForValidation，新增 toDto()，加强验证规则
    - 删除整个 prepareForValidation() 方法
    - 新增 `toDto(int $memberId): OrderSubmitInput`
    - total_amount 改为 required|integer|min:0，新增 order_type required|string|in:normal
    - _Requirements: 4.6, 5.2, 7.2, 7.3_
  - [x] 4.4 Property 测试：toDto 映射一致性
    - **Property 3: Request toDto 映射一致性**
    - **Validates: Requirements 4.5, 4.6**
  - [x] 4.5 Property 测试：camelCase 字段被拒绝
    - **Property 4: camelCase 字段被拒绝**
    - **Validates: Requirements 5.1, 5.2**
  - [x] 4.6 Property 测试：必填字段缺失时验证失败
    - **Property 5: 必填字段缺失时验证失败**
    - **Validates: Requirements 7.1, 7.2, 7.3**
  - [x] 4.7 Property 测试：地址子字段超长时验证失败
    - **Property 6: 地址子字段超长时验证失败**
    - **Validates: Requirements 7.5**

- [x] 5. Application 层与 Domain Service 串联
  - [x] 5.1 改造 OrderService：接收 Contract 输入，地址解析下沉，修复 submit 顺序
    - preview() 和 submit() 接收 OrderPreviewInput/OrderSubmitInput
    - 新增 buildEntityFromInput() 和 resolveAddress() 私有方法
    - submit 中先 buildDraft 再 reserve（修复顺序 Bug）
    - 在 reserve 前调用 verifyPrice()
    - 策略管线：validate → buildDraft → applyCoupon → adjustPrice
    - _Requirements: 3.1, 3.2, 3.3, 6.1, 6.2, 6.3, 6.4, 9.2, 11.2_
  - [x] 5.2 改造 OrderCommandApiService：移除 PayloadFactory 依赖，使用 Contract 类型
    - preview() 接收 OrderPreviewInput，submit() 接收 OrderSubmitInput
    - submit 包裹 Db::transaction()
    - 移除 OrderPayloadFactory 的构造函数注入
    - _Requirements: 1.1, 1.2, 6.1, 6.2_
  - [x] 5.3 改造 OrderController：调用 Request::toDto()
    - preview 和 submit 方法改为 `$request->toDto($this->currentMember->id())`
    - _Requirements: 1.1_
  - [x] 5.4 单元测试：submit 执行顺序验证
    - Mock StockService 和 Strategy，验证 buildDraft 在 reserve 之前调用
    - **Property 2: 商品下架时提交不扣库存**
    - **Validates: Requirements 3.1, 3.2**

- [x] 6. Bug 修复与事件监听
  - [x] 6.1 修复 PayService::payByBalance() 支付方式覆盖 Bug
    - 删除第二行 `setPayMethod(PayType::WECHAT->value)`
    - _Requirements: 2.1, 2.2_
  - [x] 6.2 Property 测试：余额支付方式正确记录
    - **Property 1: 余额支付方式正确记录**
    - **Validates: Requirements 2.1, 2.2**
  - [x] 6.3 创建 OrderCreatedListener
    - 在 `app/Domain/Order/Listener/` 下创建 `OrderCreatedListener.php`
    - 监听 OrderCreatedEvent，记录日志（order_no, member_id, pay_amount）
    - 在 `config/autoload/listeners.php` 注册
    - _Requirements: 12.1, 12.2, 12.3_
  - [x] 6.4 单元测试：OrderCreatedListener 日志记录
    - 验证 listener 处理事件时记录正确的日志字段
    - _Requirements: 12.2_

- [x] 7. 清理与最终检查
  - [x] 7.1 删除或标记废弃 OrderPayloadFactory 和 OrderSubmitCommand
    - OrderPayloadFactory 不再被引用，可删除或标记 @deprecated
    - OrderSubmitCommand 未使用，可删除
    - 删除 OrderEntity 中对 OrderCreateDto 的引用
  - [x] 7.2 清理 OrderEntity::fromModel() 中的重复逻辑（已在 OrderMapper 中）
    - 确保 fromModel 只在 OrderMapper 中存在

- [x] 8. Final checkpoint - 确保所有测试通过
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- 实现顺序：Domain Contract → Strategy → Interface DTO/Request → Application Service → Bug Fix → Cleanup
- OrderPayloadFactory 的地址解析和条目构建逻辑下沉到 OrderService 的私有方法
- 策略管线顺序：validate → buildDraft → applyCoupon → adjustPrice → [verifyPrice] → [reserve]
