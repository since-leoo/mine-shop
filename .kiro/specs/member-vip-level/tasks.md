# 实现计划：会员VIP等级体系

## 概述

基于现有 Member 领域的 DDD 架构，增量实现 VIP 等级配置管理、成长值体系、积分体系、注册送积分、积分等级关联和消费返积分功能。采用领域事件解耦各模块，复用已有的 MemberLevel、MemberWallet、MemberWalletTransaction 等基础设施。

## Tasks

- [x] 1. 新增成长值日志基础设施
  - [x] 1.1 创建 member_growth_logs 数据库迁移文件
    - 在 `databases/migrations/` 下创建迁移，包含 id, member_id, before_value, after_value, change_amount, source, related_type, related_id, remark, created_at 字段
    - member_id 添加索引，created_at 添加索引
    - _Requirements: 2.5_
  - [x] 1.2 创建 MemberGrowthLog 基础设施模型
    - 在 `app/Infrastructure/Model/Member/MemberGrowthLog.php` 创建 Eloquent 模型
    - 定义 fillable、casts 和 member 关联
    - _Requirements: 2.5_
  - [x] 1.3 创建 MemberGrowthLogRepository 仓储
    - 在 `app/Domain/Member/Repository/MemberGrowthLogRepository.php` 创建仓储类
    - 提供 create、findByMemberId 方法
    - _Requirements: 2.5_

- [x] 2. 实现等级配置校验与服务扩展
  - [x] 2.1 扩展 DomainMemberLevelService 添加校验和匹配方法
    - 在 `app/Domain/Member/Service/DomainMemberLevelService.php` 中添加 `validateLevelConfigs(array $levels)` 方法，校验序号唯一性和成长值门槛严格递增
    - 添加 `matchLevelByGrowthValue(int $growthValue): MemberLevel` 方法，返回成长值对应的最高等级
    - 添加 `getActiveLevels(): array` 方法，返回按序号升序排列的启用等级列表
    - _Requirements: 1.2, 1.3, 1.5, 1.6, 2.3, 2.4_
  - [x] 2.2 编写属性测试：等级配置校验
    - **Property 1: 等级配置校验——序号唯一且成长值门槛严格递增**
    - **Validates: Requirements 1.2, 1.3, 1.5**
  - [x] 2.3 编写属性测试：成长值与等级匹配
    - **Property 3: 成长值与等级匹配**
    - **Validates: Requirements 2.3, 2.4**
  - [x] 2.4 编写属性测试：等级列表查询排序
    - **Property 2: 等级列表查询排序**
    - **Validates: Requirements 1.6**

- [x] 3. 实现领域事件
  - [x] 3.1 创建 MemberGrowthChanged 事件
    - 在 `app/Domain/Member/Event/MemberGrowthChanged.php` 创建事件类
    - 包含 memberId, beforeValue, afterValue, changeAmount, source, remark 属性
    - _Requirements: 2.2_
  - [x] 3.2 创建 MemberRegistered 事件
    - 在 `app/Domain/Member/Event/MemberRegistered.php` 创建事件类
    - 包含 memberId, source 属性
    - _Requirements: 4.1_
  - [x] 3.3 创建 OrderPaidForMember 事件
    - 在 `app/Domain/Member/Event/OrderPaidForMember.php` 创建事件类
    - 包含 memberId, orderNo, payAmountCents 属性
    - _Requirements: 6.1_

- [x] 4. 实现成长值领域服务
  - [x] 4.1 创建 DomainMemberGrowthService
    - 在 `app/Domain/Member/Service/DomainMemberGrowthService.php` 创建服务
    - 实现 `addGrowthValue()` 方法：更新 Member.growth_value，创建成长值日志，派发 MemberGrowthChanged 事件
    - 实现 `deductGrowthValue()` 方法：减少成长值（最低为0），创建日志，派发事件
    - 实现 `recalculateLevel()` 方法：调用 LevelService.matchLevelByGrowthValue()，更新 Member 的 level 和 level_id
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_
  - [x] 4.2 编写属性测试：成长值变动日志完整性
    - **Property 4: 成长值变动日志完整性**
    - **Validates: Requirements 2.5**

- [ ] 5. 实现积分领域服务
  - [x] 5.1 创建 DomainMemberPointsService
    - 在 `app/Domain/Member/Service/DomainMemberPointsService.php` 创建服务
    - 实现 `calculatePurchasePoints(int $payAmountCents, float $pointRate): int` 纯计算方法
    - 实现 `grantRegisterPoints(int $memberId)` 方法：读取配置，调用 WalletService 增加积分，派发 MemberBalanceAdjusted 事件
    - 实现 `grantPurchasePoints(int $memberId, int $payAmountCents, string $orderNo)` 方法：计算返积分，调用 WalletService 增加积分
    - 实现 `deductPurchasePoints(int $memberId, int $pointsToDeduct, string $orderNo)` 方法：扣回消费返积分
    - _Requirements: 3.1, 3.2, 4.2, 5.2, 6.2, 6.3, 6.5_
  - [x] 5.2 编写属性测试：消费返积分计算公式
    - **Property 9: 消费返积分计算公式**
    - **Validates: Requirements 5.2, 5.4, 6.2**
  - [x] 5.3 编写属性测试：积分余额非负不变量
    - **Property 5: 积分余额非负不变量**
    - **Validates: Requirements 3.3, 3.5**
  - [x] 5.4 编写属性测试：注册赠送积分幂等性
    - **Property 8: 注册赠送积分幂等性**
    - **Validates: Requirements 4.5**

- [x] 6. Checkpoint - 确保所有测试通过
  - 确保所有测试通过，如有问题请向用户确认。

- [x] 7. 实现事件监听器
  - [x] 7.1 创建 LevelUpgradeListener
    - 在 `app/Domain/Member/Listener/LevelUpgradeListener.php` 创建监听器
    - 监听 MemberGrowthChanged 事件
    - 调用 DomainMemberGrowthService::recalculateLevel() 重新计算等级
    - _Requirements: 2.2, 2.3, 2.4_
  - [x] 7.2 创建 RegisterPointsListener
    - 在 `app/Domain/Member/Listener/RegisterPointsListener.php` 创建监听器
    - 监听 MemberRegistered 事件
    - 调用 DomainMemberPointsService::grantRegisterPoints()
    - _Requirements: 4.1, 4.2, 4.4_
  - [x] 7.3 创建 PurchaseRewardListener
    - 在 `app/Domain/Member/Listener/PurchaseRewardListener.php` 创建监听器
    - 监听 OrderPaidForMember 事件
    - 调用 DomainMemberPointsService::grantPurchasePoints() 发放消费返积分
    - 调用 DomainMemberGrowthService::addGrowthValue() 增加成长值
    - _Requirements: 2.1, 6.1, 6.2, 6.3_
  - [x] 7.4 在 Hyperf 事件配置中注册所有监听器
    - 更新 `config/autoload/listeners.php` 注册三个新监听器
    - _Requirements: 2.2, 4.1, 6.1_

- [x] 8. 集成注册和支付流程
  - [x] 8.1 在会员注册流程中派发 MemberRegistered 事件
    - 修改 `app/Domain/Member/Api/Command/DomainApiMemberAuthCommandService.php`
    - 在 miniProgramLogin 方法中新会员创建成功后派发 MemberRegistered 事件
    - _Requirements: 4.1, 4.2_
  - [x] 8.2 在订单支付完成流程中派发 OrderPaidForMember 事件
    - 修改 `app/Domain/Trade/Payment/DomainPayService.php`
    - 在 markPaid 成功后派发 OrderPaidForMember 事件
    - _Requirements: 2.1, 6.1_

- [x] 9. 实现等级权益查询 API
  - [x] 9.1 创建会员VIP信息查询方法
    - 在 `app/Domain/Member/Api/Query/DomainApiMemberQueryService.php` 中添加 `getVipInfo(int $memberId)` 方法
    - 返回当前等级名称、图标、成长值、升级所需成长值、权益列表
    - 实现下一等级差值计算逻辑
    - _Requirements: 7.1, 7.2, 7.3_
  - [x] 9.2 编写属性测试：下一等级差值计算
    - **Property 11: 下一等级差值计算**
    - **Validates: Requirements 7.3**

- [x] 10. Final checkpoint - 确保所有测试通过
  - 确保所有测试通过，如有问题请向用户确认。

- [x] 11. 后台会员列表新增余额列
  - [x] 11.1 在会员列表表格中新增"余额"列
    - 修改 `web/src/modules/member/views/list/index.vue`
    - 在"积分"列（`el-table-column label="积分"`）之前插入"余额"列
    - 使用 `formatYuan(row.wallet?.balance)` 格式化显示，前缀 ¥
    - 当 wallet 为 null 时显示 ¥0.00
    - _Requirements: 8.1, 8.3, 8.4_

- [x] 12. 小程序个人中心余额积分展示与跳转
  - [x] 12.1 扩展 MemberCenterTransformer 的 countsData
    - 修改 `app/Interface/Api/Transformer/MemberCenterTransformer.php`
    - 在 countsData 数组开头插入余额数据项 `['num' => $profile['balance'] ?? 0, 'name' => '余额', 'type' => 'balance']`
    - 保留现有的积分和优惠券数据项
    - _Requirements: 9.1, 9.2, 9.3_
  - [x] 12.2 小程序个人中心添加余额和积分点击跳转
    - 修改 `shopProgramMiniNew/pages/usercenter/index.js`
    - 在 countsData 点击事件中，余额项跳转到 `/pages/usercenter/wallet-transactions/index?type=balance`
    - 积分项跳转到 `/pages/usercenter/wallet-transactions/index?type=points`
    - 移除"我的余额"菜单项的"开发中" toast，改为跳转到余额流水页面
    - _Requirements: 9.4, 9.5_

- [x] 13. 实现小程序端流水查询 API
  - [x] 13.1 创建 API 流水查询应用服务
    - 创建 `app/Application/Api/Member/AppApiMemberWalletQueryService.php`
    - 注入 `MemberWalletTransactionRepository`
    - 实现 `transactions(int $memberId, string $walletType, int $page, int $pageSize): array` 方法
    - _Requirements: 10.1, 10.2, 10.3_
  - [x] 13.2 创建流水查询请求验证类
    - 创建 `app/Interface/Api/Request/V1/WalletTransactionRequest.php`
    - 验证 wallet_type 必填且为 balance 或 points 枚举值
    - page 和 page_size 可选，整数类型
    - _Requirements: 10.4_
  - [x] 13.3 创建 API 流水查询控制器
    - 创建 `app/Interface/Api/Controller/V1/WalletController.php`
    - 路由前缀 `/api/v1/member/wallet`，添加 TokenMiddleware
    - 实现 `GET /transactions` 端点，从 CurrentMember 获取会员ID
    - _Requirements: 10.1, 10.2, 10.3, 10.4_
  - [x] 13.4 编写属性测试：流水记录过滤正确性
    - **Property 13: 流水记录过滤正确性**
    - **Validates: Requirements 10.1**
  - [x] 13.5 编写属性测试：流水记录时间倒序
    - **Property 14: 流水记录时间倒序**
    - **Validates: Requirements 10.2**

- [x] 14. 实现小程序流水记录页面
  - [x] 14.1 创建流水记录页面文件
    - 创建 `shopProgramMiniNew/pages/usercenter/wallet-transactions/index.js`
    - 创建 `shopProgramMiniNew/pages/usercenter/wallet-transactions/index.wxml`
    - 创建 `shopProgramMiniNew/pages/usercenter/wallet-transactions/index.wxss`
    - 创建 `shopProgramMiniNew/pages/usercenter/wallet-transactions/index.json`
    - 通过 URL 参数 type 区分余额/积分流水
    - 页面标题动态设置（"余额明细" / "积分明细"）
    - _Requirements: 10.5, 10.6_
  - [x] 14.2 实现流水列表渲染和分页加载
    - 调用 `/api/v1/member/wallet/transactions` 接口获取数据
    - 列表项展示：变动类型标签、金额（+/- 前缀）、来源描述、时间
    - 实现 onPullDownRefresh 下拉刷新
    - 实现 onReachBottom 触底加载更多
    - _Requirements: 10.5, 10.6_
  - [x] 14.3 在 app.json 中注册流水记录页面路由
    - 在 `shopProgramMiniNew/app.json` 的 pages 数组中添加 `pages/usercenter/wallet-transactions/index`
    - _Requirements: 10.5_
  - [x] 14.4 创建流水查询 API 服务文件
    - 创建 `shopProgramMiniNew/services/usercenter/fetchWalletTransactions.js`
    - 封装流水查询 API 调用
    - _Requirements: 10.1_

- [x] 15. Final checkpoint - 确保需求 8-10 所有功能正常
  - 确保所有测试通过，如有问题请向用户确认。

## 备注

- 标记 `*` 的任务为可选测试任务，可跳过以加速 MVP 交付
- 每个任务引用了具体的需求编号以确保可追溯性
- Checkpoint 任务用于增量验证
- 属性测试验证通用正确性属性，单元测试验证具体示例和边界条件
