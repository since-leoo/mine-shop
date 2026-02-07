# 需求文档：订单结算 DDD 重构

## 简介

对小程序订单预览（preview）和订单提交（submit）流程进行 DDD 架构重构，修复已知 Bug，使代码符合项目 DDD 架构规范（docs/DDD-ARCHITECTURE.md）和 API 字段标准（api-field-standards.md）。涵盖 Interface 层 Request/DTO/Contract 补全、Application 层职责归位、Domain 层策略扩展性改造，以及价格校验、优惠券占位、事件监听等增强。

## 术语表

- **Order_System**：订单结算系统，包含预览和提交两个核心流程
- **OrderPreviewRequest**：订单预览请求验证类，位于 Interface 层
- **OrderCommitRequest**：订单提交请求验证类，位于 Interface 层
- **OrderPreviewDto**：订单预览数据传输对象，实现 OrderPreviewInput 契约
- **OrderCommitDto**：订单提交数据传输对象，实现 OrderSubmitInput 契约
- **OrderPreviewInput**：订单预览契约接口，位于 Domain/Order/Contract
- **OrderSubmitInput**：订单提交契约接口，位于 Domain/Order/Contract
- **OrderCommandApiService**：应用层命令服务，负责订单结算流程编排
- **OrderPayloadFactory**：订单载荷工厂，当前位于 Application 层（需重构）
- **OrderService**：领域服务，负责订单核心业务逻辑
- **OrderEntity**：订单聚合根实体
- **OrderTypeStrategyFactory**：订单类型策略工厂
- **OrderTypeStrategyInterface**：订单类型策略接口
- **NormalOrderStrategy**：普通订单策略实现
- **PayService**：支付领域服务
- **OrderCreatedEvent**：订单创建领域事件
- **OrderPriceValue**：订单价格值对象

## 需求

### 需求 1：修复 OrderCommandApiService::preview() 方法签名错误

**用户故事：** 作为开发者，我希望订单预览接口能正常调用，以便前端可以获取订单预览数据。

#### 验收标准

1. WHEN OrderController 调用 preview 方法, THE Order_System SHALL 通过 OrderPreviewDto（实现 OrderPreviewInput 契约）传递参数，而非传递原始数组
2. WHEN OrderCommandApiService 接收预览请求, THE Order_System SHALL 使用 OrderPreviewInput 契约类型作为 preview() 方法参数类型
3. WHEN OrderEntity::create() 被调用, THE Order_System SHALL 接受 OrderPreviewInput 契约接口作为参数，替代不存在的 OrderCreateDto 类

### 需求 2：修复 PayService::payByBalance() 支付方式覆盖 Bug

**用户故事：** 作为用户，我希望使用余额支付时支付方式被正确记录为余额支付，以便订单支付记录准确。

#### 验收标准

1. WHEN 用户选择余额支付, THE PayService SHALL 将支付方式设置为 PayType::BALANCE 且不被后续代码覆盖为 PayType::WECHAT
2. WHEN 余额支付完成, THE Order_System SHALL 记录的 pay_method 值为 "balance"

### 需求 3：修复 OrderService::submit() 中 buildDraft 与库存扣减的执行顺序

**用户故事：** 作为用户，我希望提交订单时如果商品已下架能立即得到提示，而不是先扣减库存再报错。

#### 验收标准

1. WHEN 用户提交订单, THE OrderService SHALL 先执行 buildDraft（含商品状态校验）再执行库存扣减
2. IF buildDraft 检测到商品已下架, THEN THE OrderService SHALL 抛出异常且不扣减库存
3. IF 库存扣减后持久化失败, THEN THE OrderService SHALL 回滚已扣减的库存

### 需求 4：补全 Interface 层 Contract + DTO 体系

**用户故事：** 作为开发者，我希望订单预览和提交流程遵循 DDD 架构规范的 Request → toDto() → DTO(implements Contract) → CommandService 链路，以便代码结构统一且可维护。

#### 验收标准

1. THE Order_System SHALL 在 Domain/Order/Contract/ 目录下提供 OrderPreviewInput 接口，声明获取商品列表、地址信息、优惠券列表的方法
2. THE Order_System SHALL 在 Domain/Order/Contract/ 目录下提供 OrderSubmitInput 接口，继承 OrderPreviewInput 并增加获取前端总金额、用户备注的方法
3. THE Order_System SHALL 在 Interface/Api/DTO/Order/ 目录下提供 OrderPreviewDto 类，实现 OrderPreviewInput 接口
4. THE Order_System SHALL 在 Interface/Api/DTO/Order/ 目录下提供 OrderCommitDto 类，实现 OrderSubmitInput 接口
5. WHEN OrderPreviewRequest 验证通过, THE OrderPreviewRequest SHALL 通过 toDto() 方法从 $this->validated() 直接映射生成 OrderPreviewDto，不做字段名转换
6. WHEN OrderCommitRequest 验证通过, THE OrderCommitRequest SHALL 通过 toDto() 方法从 $this->validated() 直接映射生成 OrderCommitDto，不做字段名转换

### 需求 5：移除 Request 层 camelCase 兼容映射

**用户故事：** 作为开发者，我希望后端 Request 不做 camelCase→snake_case 的兼容转换，以便遵循 API 字段标准，由小程序端适配后端字段名。

#### 验收标准

1. THE OrderPreviewRequest SHALL 移除 prepareForValidation() 中所有 camelCase→snake_case 的字段映射逻辑
2. THE OrderCommitRequest SHALL 移除 prepareForValidation() 中所有 camelCase→snake_case 的字段映射逻辑
3. THE OrderPayloadFactory SHALL 移除所有 camelCase 回退取值逻辑（如 `$payload['goodsRequestList']`），仅使用 snake_case 字段名

### 需求 6：重构 OrderPayloadFactory 职责归位

**用户故事：** 作为开发者，我希望 Application 层不包含领域逻辑（地址解析、商品条目构建），以便符合 DDD 分层规范。

#### 验收标准

1. WHEN OrderCommandApiService 接收预览请求, THE OrderCommandApiService SHALL 直接将 OrderPreviewInput 传递给 OrderService，不通过 OrderPayloadFactory 组装 OrderEntity
2. WHEN OrderCommandApiService 接收提交请求, THE OrderCommandApiService SHALL 直接将 OrderSubmitInput 传递给 OrderService，不通过 OrderPayloadFactory 组装 OrderEntity
3. WHEN OrderService 需要解析地址, THE OrderService SHALL 在领域层内完成地址解析逻辑
4. WHEN OrderService 需要构建订单条目, THE OrderService SHALL 在领域层内通过 OrderEntity 行为方法完成条目构建

### 需求 7：加强 Request 验证规则

**用户故事：** 作为开发者，我希望 Request 验证规则严格覆盖所有字段，以便拦截非法输入。

#### 验收标准

1. THE OrderPreviewRequest SHALL 对 goods_request_list 验证为 required|array|min:1，对每项的 sku_id 验证为 required|integer|min:1，对每项的 quantity 验证为 required|integer|min:1|max:999
2. THE OrderCommitRequest SHALL 对 total_amount 验证为 required|integer|min:0，不使用 nullable
3. THE OrderCommitRequest SHALL 对 order_type 验证为 required|string|in:normal，限定可选的订单类型枚举值
4. THE OrderPreviewRequest SHALL 对 address_id 验证为 nullable|integer|min:1
5. THE OrderPreviewRequest SHALL 对 user_address 子字段（name, phone, province, city, district, detail）声明类型和长度限制

### 需求 8：策略工厂可扩展性改造

**用户故事：** 作为开发者，我希望新增订单类型策略时无需修改 OrderTypeStrategyFactory 类，以便符合开闭原则。

#### 验收标准

1. THE OrderTypeStrategyFactory SHALL 通过构造函数接收 OrderTypeStrategyInterface 数组，自动注册所有策略实现
2. WHEN 新增一个订单类型策略实现, THE OrderTypeStrategyFactory SHALL 无需修改工厂代码即可识别新策略
3. WHEN 请求一个未注册的订单类型, THE OrderTypeStrategyFactory SHALL 抛出 RuntimeException 并包含该类型名称

### 需求 9：策略接口增加价格调整钩子

**用户故事：** 作为开发者，我希望不同订单类型策略可以自定义价格调整逻辑（如团购折扣），以便支持多种订单类型的差异化定价。

#### 验收标准

1. THE OrderTypeStrategyInterface SHALL 声明 adjustPrice(OrderEntity) 方法，用于策略特定的价格调整
2. WHEN 构建订单草稿完成后, THE OrderService SHALL 调用策略的 adjustPrice 方法进行价格调整
3. THE NormalOrderStrategy SHALL 实现 adjustPrice 方法，默认不做额外价格调整（直通）

### 需求 10：订单提交时价格校验

**用户故事：** 作为用户，我希望提交订单时系统校验前端传入的总金额与后端计算金额是否一致，以便防止价格篡改。

#### 验收标准

1. WHEN 用户提交订单, THE OrderService SHALL 比较前端传入的 total_amount 与后端计算的 pay_amount
2. IF 前端 total_amount 与后端 pay_amount 不一致, THEN THE OrderService SHALL 抛出业务异常，提示价格已变动
3. THE Order_System SHALL 使用分（cent）为单位进行金额比较，避免浮点精度问题

### 需求 11：优惠券折扣计算与核销

**用户故事：** 作为用户，我希望在下单时选择优惠券后系统自动计算折扣金额，并在提交订单后标记优惠券为已使用，以便享受优惠并防止重复使用。

#### 验收标准

1. THE OrderTypeStrategyInterface SHALL 声明 applyCoupon(OrderEntity, array couponList) 方法，在 buildDraft 之后、adjustPrice 之前调用
2. WHEN 用户传入 coupon_list, THE NormalOrderStrategy::applyCoupon() SHALL 通过 CouponUserRepository 查找该会员未使用且未过期的优惠券记录
3. IF 优惠券不存在或已使用, THEN applyCoupon SHALL 抛出 RuntimeException 提示该优惠券不可用
4. IF 优惠券已失效（status !== 'active'）, THEN applyCoupon SHALL 抛出 RuntimeException 提示已失效
5. IF 商品金额未达到优惠券满减门槛（min_amount）, THEN applyCoupon SHALL 抛出 RuntimeException 提示未满足条件
6. THE NormalOrderStrategy SHALL 根据优惠券类型计算折扣：fixed 类型直接减面值，percent/discount 类型按折扣率计算（goodsAmount * (1 - value/10)）
7. THE NormalOrderStrategy SHALL 确保折扣总额不超过商品总额
8. WHEN applyCoupon 计算完成, THE OrderEntity SHALL 记录 couponAmount（优惠券抵扣金额）和 appliedCouponUserIds（已应用的 coupon_user ID 列表）
9. WHEN 订单提交成功后, THE OrderService SHALL 调用 CouponUserService::markUsed() 将已应用的优惠券标记为已使用
10. WHEN coupon_list 为空, THE applyCoupon SHALL 将 couponAmount 设为 0 且不做任何查询

### 需求 13：小程序端优惠券选择与下单参数适配

**用户故事：** 作为用户，我希望在小程序订单确认页面能选择可用优惠券，选择后系统重新计算订单金额，以便在下单前确认优惠信息。

#### 验收标准

1. WHEN 用户打开优惠券选择弹窗, THE selectCoupons 组件 SHALL 调用 `/api/v1/member/coupons?status=unused` 获取用户未使用的优惠券列表，而非使用本地 mock 数据
2. WHEN 用户选择优惠券后确认, THE 订单确认页 SHALL 将选中的优惠券 ID 列表以 `coupon_list: [{coupon_id: xxx}]` 格式传给后端 preview 接口重新计算
3. THE 小程序端 SHALL 使用 snake_case 字段名（如 sku_id、coupon_id、order_type）直接构建请求 payload，不使用 camelCase 再转换
4. THE 小程序端 SHALL 不传递后端 Request 验证规则中未声明的多余字段（如 currency、logisticsType、payType 等）
5. WHEN 重新调用 preview 接口, THE 订单确认页 SHALL 保留用户之前的优惠券选择状态，不因数据刷新而丢失

### 需求 12：OrderCreatedEvent 监听器

**用户故事：** 作为开发者，我希望订单创建事件有对应的监听器处理后续逻辑（如日志记录），以便事件驱动架构完整。

#### 验收标准

1. THE Order_System SHALL 提供 OrderCreatedListener 监听 OrderCreatedEvent 事件
2. WHEN OrderCreatedEvent 被触发, THE OrderCreatedListener SHALL 记录订单创建日志（订单号、会员ID、订单金额）
3. THE OrderCreatedListener SHALL 预留后续扩展点（如发送通知、同步外部系统）的注释说明
