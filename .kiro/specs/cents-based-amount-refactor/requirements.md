# 需求文档：全站金额分单位存储与 Transformer 精简

## 简介

将全站金额字段从"元"（decimal(10,2)）改为"分"（int）存储，消除后端 float 精度问题和 Transformer 中的 toCent() 转换。Entity 全链路以 int（分）为单位，API 直接返回分值，前端自行处理分→元展示。同时精简 OrderCheckoutTransformer，移除 formatGoodsDetail 中手动拼装的冗余字段，改用 Entity toArray() 返回数据库原始结构。

## 术语表

- **分（cent）**：金额最小单位，1元 = 100分，存储为 int 类型
- **元（yuan）**：当前存储格式，decimal(10,2)
- **Model 属性转换**：Hyperf Model 的 $casts 机制，在读写时自动转换字段格式
- **Entity toArray()**：领域实体的数组序列化方法，返回数据库字段结构

## 影响范围

### 数据库表（需要迁移的金额字段）

| 表 | 金额字段 |
|---|---|
| orders | goods_amount, shipping_fee, discount_amount, total_amount, pay_amount |
| order_items | unit_price, total_price |
| product_skus | cost_price, market_price, sale_price |
| products | min_price, max_price |
| coupons | value, min_amount |
| payments | payment_amount, paid_amount, refund_amount |
| payment_refunds | refund_amount |
| wallets | balance, frozen_balance, total_recharge, total_consume |
| wallet_transactions | amount, balance_before, balance_after |
| wallet_freeze_records | freeze_amount, frozen_amount, released_amount |
| seckill_products (seckill_session_products) | original_price, seckill_price |
| seckill_orders | original_price, seckill_price, total_amount |
| group_buys | original_price, group_price |
| group_buy_orders | original_price, group_price, total_amount |
| members | total_amount |

### 后端代码层

| 层 | 组件 | 改动 |
|---|---|---|
| Infrastructure/Model | 所有含金额字段的 Model | $casts 从 decimal:2 改为 integer |
| Domain/Entity | OrderEntity, OrderItemEntity, OrderPriceValue 等 | float→int，移除 round() |
| Domain/Strategy | NormalOrderStrategy (优惠券计算) | 金额计算改为整数运算 |
| Domain/Mapper | OrderMapper 等 | 映射类型对齐 |
| Application/Transformer | OrderCheckoutTransformer | 移除 toCent()，精简 formatGoodsDetail |
| Infrastructure/Service | ProductMockDataService (种子数据) | 价格改为分 |

### 前端代码层

| 组件 | 改动 |
|---|---|
| shopProgramMini 全局 | 所有金额展示处增加分→元转换（÷100） |

## 需求

### 需求 1：数据库金额字段改为整数存储（分）

**用户故事：** 作为开发者，我希望数据库金额字段以整数（分）存储，以便消除 decimal 浮点精度问题，简化全链路金额处理。

#### 验收标准

1. THE System SHALL 创建迁移脚本，将所有金额字段从 `decimal(10,2)` 改为 `unsignedInteger` 或 `unsignedBigInteger`
2. THE 迁移脚本 SHALL 包含数据转换逻辑：将现有数据从元转换为分（`value * 100`）
3. THE 迁移脚本 SHALL 提供 rollback 逻辑：从分转回元（`value / 100`）
4. THE System SHALL 确保 `pay_amount` 等 nullable 字段在迁移后保持 nullable 语义

### 需求 2：Model 层属性转换对齐

**用户故事：** 作为开发者，我希望 Model 的 $casts 与数据库字段类型一致，以便 ORM 读写时类型正确。

#### 验收标准

1. THE Order Model SHALL 将所有金额字段的 cast 从 `decimal:2` 改为 `integer`
2. THE OrderItem Model SHALL 将 unit_price、total_price 的 cast 从 `decimal:2` 改为 `integer`
3. THE ProductSku Model SHALL 将 cost_price、market_price、sale_price 的 cast 改为 `integer`
4. THE Coupon Model SHALL 将 value、min_amount 的 cast 改为 `integer`
5. 所有涉及金额的 Model SHALL 统一使用 `integer` cast

### 需求 3：Entity 层金额类型改为 int

**用户故事：** 作为开发者，我希望领域实体的金额属性为 int 类型（分），以便全链路类型一致，无需 float→int 转换。

#### 验收标准

1. THE OrderEntity SHALL 将所有金额属性（goodsAmount, shippingFee, discountAmount, totalAmount, payAmount, couponAmount）从 float 改为 int
2. THE OrderItemEntity SHALL 将 unitPrice、totalPrice 从 float 改为 int
3. THE OrderPriceValue SHALL 将所有金额属性从 float 改为 int，recalculate() 使用整数运算
4. THE Entity setter SHALL 移除 `round()` 调用，直接赋值 int
5. THE OrderItemEntity::syncTotalPrice() SHALL 使用整数乘法替代 bcmul

### 需求 4：优惠券计算改为整数运算

**用户故事：** 作为开发者，我希望优惠券折扣计算全程使用整数（分），以便避免浮点精度问题。

#### 验收标准

1. THE NormalOrderStrategy::applyCoupon() SHALL 使用 int（分）进行满减门槛比较和折扣计算
2. THE calculateCouponDiscount() SHALL 返回 int（分），percent 类型使用 `intdiv()` 或 `(int) round()` 确保整数结果
3. THE Coupon Model 的 value 和 min_amount SHALL 以分为单位存储和读取

### 需求 5：OrderCheckoutTransformer 精简

**用户故事：** 作为开发者，我希望 Transformer 不再手动拼装冗余字段，直接使用 Entity toArray() 返回数据库原始结构，以便减少维护成本和字段不一致风险。

#### 验收标准

1. THE OrderCheckoutTransformer SHALL 移除 toCent() 方法（金额已经是分，无需转换）
2. THE formatGoodsDetail() SHALL 精简为基于 OrderItemEntity::toArray() 的输出，仅补充 toArray() 中不包含的必要展示字段（如 store_id、store_name）
3. THE transform() 方法中的金额字段 SHALL 直接从 Entity/PriceValue 获取 int 值，不做转换
4. THE OrderItemEntity::toArray() SHALL 返回与数据库字段一致的 snake_case 结构，金额为 int（分）

### 需求 6：前端金额展示适配

**用户故事：** 作为用户，我希望小程序端正确展示金额（元），即使后端返回的是分。

#### 验收标准

1. THE 小程序端 SHALL 提供统一的分→元格式化工具函数（如 `formatPrice(cents)`）
2. THE orderConfirm.js 的 transformPreviewResponse SHALL 不再需要 toCentString 转换（后端已返回分）
3. THE 所有金额展示处 SHALL 调用格式化函数将分转为元展示

### 需求 7：OrderEntity verifyPrice 对齐

**用户故事：** 作为开发者，我希望价格校验逻辑在全链路分单位下更简洁。

#### 验收标准

1. THE OrderEntity::verifyPrice() SHALL 直接比较两个 int 值（前端传入分，后端 payAmount 也是分），移除 `round($this->getPayAmount() * 100)` 转换
2. THE OrderCommitRequest 的 total_amount 验证规则 SHALL 保持 `required|integer|min:0`（已经是分）

### 需求 8：种子数据与测试对齐

**用户故事：** 作为开发者，我希望种子数据和测试用例使用分为单位，以便与新的存储格式一致。

#### 验收标准

1. THE ProductMockDataService SHALL 将所有价格生成逻辑改为分（如 base: 449900 代替 4499）
2. THE 所有订单相关测试 SHALL 使用 int（分）作为金额输入和断言值
