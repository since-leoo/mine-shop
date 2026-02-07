# 实现计划：全站金额分单位存储与 Transformer 精简

## 概述

将全站金额从元（decimal）改为分（int）存储，精简 Transformer，前端自行处理分→元展示。按自底向上顺序：先迁移脚本 → Model → Entity/ValueObject → Strategy → Transformer → 前端 → 测试。

## Tasks

- [x] 1. 数据库迁移脚本
  - [x] 1.1 创建统一迁移脚本 `convert_all_amounts_to_cents`
    - 涵盖所有表的金额字段：orders, order_items, product_skus, products, coupons, payments, payment_refunds, wallets, wallet_transactions, wallet_freeze_records, seckill 相关表, group_buy 相关表, members
    - 每张表：UPDATE value = ROUND(value * 100) → ALTER COLUMN decimal→int/bigint
    - rollback：ALTER COLUMN int→decimal → UPDATE value = value / 100
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Model 层 $casts 改造
  - [x] 2.1 Order Model：金额字段 cast 改为 integer
    - goods_amount, shipping_fee, discount_amount, total_amount, pay_amount
    - _Requirements: 2.1_
  - [x] 2.2 OrderItem Model：金额字段 cast 改为 integer
    - unit_price, total_price
    - 移除 price 和 total_amount appends（或改为返回 int）
    - _Requirements: 2.2_
  - [x] 2.3 ProductSku Model：价格字段 cast 改为 integer
    - cost_price, market_price, sale_price
    - _Requirements: 2.3_
  - [x] 2.4 Product Model：价格字段 cast 改为 integer
    - min_price, max_price
  - [x] 2.5 Coupon Model：金额字段 cast 改为 integer
    - value, min_amount
    - _Requirements: 2.4_
  - [x] 2.6 Payment / PaymentRefund Model：金额字段 cast 改为 integer
  - [x] 2.7 Wallet / WalletTransaction / WalletFreezeRecord Model：金额字段 cast 改为 integer
  - [x] 2.8 Seckill / GroupBuy 相关 Model：金额字段 cast 改为 integer
  - [x] 2.9 Member Model：total_amount cast 改为 integer
    - _Requirements: 2.5_

- [x] 3. Checkpoint - 确保 Model 层改造完成，迁移脚本可执行

- [x] 4. Entity / ValueObject 层改造（订单域）
  - [x] 4.1 OrderPriceValue：float→int，移除 round()，recalculate 用整数运算
    - _Requirements: 3.3_
  - [x] 4.2 OrderItemEntity：unitPrice/totalPrice float→int，syncTotalPrice 用整数乘法
    - toArray() 返回 int 金额 + weight 字段
    - _Requirements: 3.2, 5.4_
  - [x] 4.3 OrderEntity：所有金额属性 float→int，移除 round()
    - verifyPrice() 直接比较 int，移除 round($this->getPayAmount() * 100)
    - _Requirements: 3.1, 3.4, 7.1_
  - [x] 4.4 OrderMapper：映射类型对齐（Model int → Entity int）
    - _Requirements: 3.1_

- [x] 5. Strategy 层改造
  - [x] 5.1 NormalOrderStrategy：applyCoupon 和 calculateCouponDiscount 改为整数运算
    - goodsAmount 为 int（分），满减门槛比较用 int
    - calculateCouponDiscount 返回 int
    - percent 类型：`(int) round($goodsAmount * (1 - $value / 1000))`
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 6. Transformer 精简
  - [x] 6.1 OrderCheckoutTransformer：移除 toCent()，金额直接输出 int
    - _Requirements: 5.1, 5.3_
  - [x] 6.2 formatGoodsDetail 改为基于 OrderItemEntity::toArray() + 补充字段
    - 移除手动拼装的 pay_price, settle_price, origin_price 等冗余字段
    - _Requirements: 5.2_
  - [x] 6.3 transform() 中移除冗余的重复金额字段（total_sale_price 等同于 total_goods_amount）
    - _Requirements: 5.3_

- [x] 7. Checkpoint - 确保后端改造完成，运行测试

- [x] 8. 前端适配
  - [x] 8.1 创建 shopProgramMini/utils/price.js 工具函数
    - formatPrice(cents) → '99.90'
    - formatPriceYuan(cents) → '¥99.90'
    - _Requirements: 6.1_
  - [x] 8.2 orderConfirm.js：移除 toCentString，金额字段直接透传
    - transformPreviewResponse / transformStoreGoods / transformSkuDetail 中金额字段直接用 Number()
    - _Requirements: 6.2_
  - [x] 8.3 订单确认页金额展示处调用 formatPrice
    - _Requirements: 6.3_
  - [x] 8.4 其他涉及金额展示的页面适配（订单列表、订单详情等）
    - _Requirements: 6.3_

- [x] 9. 种子数据与测试对齐
  - [x] 9.1 ProductMockDataService：价格改为分
    - _Requirements: 8.1_
  - [x] 9.2 订单相关测试用例：金额改为 int（分）
    - _Requirements: 8.2_

- [x] 10. Final checkpoint - 全部测试通过

## Notes

- 实现顺序：迁移脚本 → Model → Entity → Strategy → Transformer → 前端 → 测试
- percent 类型优惠券的 value 也做 *100 转换（8.50→850），计算时除以 1000
- 迁移脚本需要在事务中执行，确保数据一致性
- 前后端需要同步发布，否则金额展示会异常
- 现有数据如果有精度问题（如 99.999），迁移时用 ROUND(value * 100) 处理
