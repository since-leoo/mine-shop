# 实施计划：运费功能 V2 修订

## 概述

基于已完成的 14 个任务，本计划仅覆盖 Delta 变更：freight_type 枚举扩展、商城配置重构、ShippingSetting 更新、FreightCalculationService 新增。

## 已完成任务（V1，保留记录）

- [x] 1. 创建商品表运费字段迁移
- [x] 2. 扩展商品领域层
- [x] 3. 更新商品 Request 验证
- [x] 4. 检查点 — 验证商品运费字段
- [x] 5. 扩展 mall.php 配置
- [x] 6. 扩展 ShippingSetting 值对象
- [x] 7. 更新 DomainMallSettingService::shipping()
- [x] 8. 检查点 — 验证配置读写
- [x] 9. 创建基础设施层
- [x] 10. 创建领域层
- [x] 11. 创建应用层
- [x] 12. 创建接口层
- [x] 13. 注册依赖注入
- [x] 14. 最终检查点 — 全部功能验证

## V2 Delta 任务

### 阶段一：freight_type 枚举扩展

- [x] 15. 创建 migration 扩展 freight_type 枚举
  - 新建 migration 文件，ALTER COLUMN 将 `freight_type` 枚举从 `free,flat,template` 扩展为 `free,flat,template,default`
  - 将默认值从 `free` 改为 `default`
  - _需求: 1.1_

- [x] 16. 更新商品 Request 验证规则
  - 修改 `ProductRequest::baseRules()` 中 `freight_type` 验证：`in:free,flat,template,default`
  - _需求: 1.5, 1.8_

- [x] 17. 检查点 — 验证 freight_type 枚举扩展
  - Ensure all tests pass, ask the user if questions arise.

### 阶段二：商城配置重构

- [x] 18. 重构 mall.php shipping 配置项
  - [x] 18.1 删除 `mall.shipping.default_template_enabled` 配置项
    - _需求: 2.6_
  - [x] 18.2 修改 `mall.shipping.flat_freight_amount` 为 JSON + dialog 弹窗交互
    - type 改为 `json`，meta 增加 `component=form, display=dialog, button_label=配置运费`
    - trigger 条件保持 `default_freight_type=flat`
    - default 改为 `['amount' => 0]`
    - _需求: 2.2, 2.3_
  - [x] 18.3 合并 `remote_area_surcharge` 和 `remote_area_provinces` 为单个 dialog 配置项
    - 删除独立的 `mall.shipping.remote_area_provinces` 配置项
    - 修改 `mall.shipping.remote_area_surcharge` 为 JSON + dialog，包含 surcharge 和 provinces 两个字段
    - trigger 条件保持 `remote_area_enabled=true`
    - default 改为 `['surcharge' => 0, 'provinces' => ['西藏', '新疆', '青海', '内蒙古', '宁夏', '甘肃']]`
    - _需求: 2.8, 2.9_
  - [x] 18.4 修改 `mall.shipping.default_template_config` 的 trigger 条件
    - trigger_key 改为 `mall.shipping.default_freight_type`，trigger_value 改为 `template`
    - default 改为 `['template_id' => 1]`（偏远地区默认模板）
    - _需求: 2.4, 2.5, 2.7_

- [x] 19. 更新 ShippingSetting 值对象
  - 移除 `defaultTemplateEnabled` 属性、构造函数参数和 `defaultTemplateEnabled()` 方法
  - _需求: 2.10_

- [x] 20. 更新 DomainMallSettingService::shipping()
  - 移除 `default_template_enabled` 的读取
  - `flatFreightAmount` 改为从 JSON `['amount']` 字段解析
  - `remoteAreaSurcharge` 改为从 JSON `['surcharge']` 字段解析
  - `remoteAreaProvinces` 改为从 JSON `['provinces']` 字段解析
  - 移除 `remote_area_provinces` 独立配置项的读取
  - _需求: 2.11_

- [x] 21. 检查点 — 验证商城配置重构
  - Ensure all tests pass, ask the user if questions arise.

### 阶段三：运费计算服务

- [x] 22. 创建 FreightCalculationService
  - [x] 22.1 创建 `app/Domain/Shipping/Service/FreightCalculationService.php`
    - 注入 DomainMallSettingService 和 ShippingTemplateRepository
    - 实现 `calculate()` 方法：freight_type 解析回退、free/flat/template 分支计算
    - 实现 `calculateByTemplate()` 私有方法：模板阶梯运费计算
    - 实现 `isRemoteArea()` 私有方法：偏远地区判断和加价
    - _需求: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_
  - [x] 22.2 编写 FreightCalculationService 属性测试
    - **Property 2: 运费类型解析回退**
    - **Property 3: 包邮类型运费为零**
    - **Property 4: 统一运费返回精确金额**
    - **Property 5: 模板阶梯运费计算**
    - **Property 6: 偏远地区加价**
    - **Validates: Requirements 5.2, 5.3, 5.4, 5.5, 5.6, 5.7**

- [x] 23. 集成运费计算到订单流程
  - [x] 23.1 在订单预览和订单提交逻辑中注入 FreightCalculationService
    - 在 DomainApiOrderCommandService 中调用 FreightCalculationService::calculate()
    - 订单预览和订单提交使用同一个计算方法
    - _需求: 5.8_
  - [x] 23.2 编写 ProductRequest 验证属性测试
    - **Property 1: 运费类型条件验证一致性**
    - **Validates: Requirements 1.5, 1.6, 1.7, 1.8**

- [x] 24. 最终检查点 — V2 全部功能验证
  - Ensure all tests pass, ask the user if questions arise.

## 备注

- 任务 15-24 为 V2 Delta 变更，基于已完成的 V1 任务 1-14
- 标记 `*` 的子任务为可选测试任务
- 每个属性测试引用设计文档中的正确性属性编号
- 属性测试使用 PHPUnit + 循环随机数据，每个属性至少 100 次迭代
