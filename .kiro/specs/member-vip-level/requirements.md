# 需求文档：会员VIP等级体系

## 简介

本功能为商城系统构建完整的会员VIP等级体系，涵盖等级配置管理、成长值体系、积分体系、注册赠送积分、积分等级关联以及消费返积分等核心场景。系统已有基础的会员模型（Member）、钱包模型（MemberWallet，支持 balance 和 points 两种类型）、等级模型（MemberLevel）以及系统设置（SystemSetting）。本需求在现有架构上扩展，实现完整的VIP等级与积分成长闭环。

## 术语表

- **VIP等级体系（VIP_Level_System）**：基于成长值划分的会员等级制度，不同等级享有不同权益
- **成长值（Growth_Value）**：会员通过消费、签到等行为累积的数值，用于判定VIP等级
- **积分（Points）**：会员通过注册、消费等行为获得的虚拟货币，可用于兑换或抵扣
- **积分钱包（Points_Wallet）**：存储会员积分余额的钱包实体，类型为 `points`
- **等级配置（Level_Config）**：在系统设置中定义的VIP等级规则，包含等级名称、成长值门槛、折扣率、积分倍率等
- **消费返积分（Purchase_Points_Reward）**：会员消费后按配置比例获得积分奖励
- **积分比率（Points_Ratio）**：每消费1元对应获得的积分数量
- **等级积分倍率（Level_Point_Rate）**：不同VIP等级享有的积分获取倍率加成
- **等级折扣率（Level_Discount_Rate）**：不同VIP等级享有的商品折扣比例
- **管理员（Administrator）**：通过后台管理系统操作的运营人员
- **会员（Member）**：注册并使用商城的终端用户
- **后台会员列表（Admin_Member_List）**：管理员在后台管理系统中查看的会员数据表格
- **小程序个人中心（Mini_Program_User_Center）**：会员在微信小程序中的个人中心页面
- **流水记录API（Wallet_Transaction_API）**：面向小程序端的钱包流水查询接口
- **小程序流水页面（Mini_Program_Transaction_Page）**：会员在小程序中查看余额或积分变动明细的页面

## 需求

### 需求 1：VIP等级配置管理

**用户故事：** 作为管理员，我希望在系统设置中配置VIP等级规则，以便灵活定义各等级的门槛和权益。

#### 验收标准

1. THE VIP_Level_System SHALL 提供至少包含等级序号、等级名称、成长值门槛、折扣率、积分倍率的等级配置结构
2. WHEN 管理员创建新的等级配置时，THE VIP_Level_System SHALL 验证等级序号的唯一性并按序号升序排列
3. WHEN 管理员设置成长值门槛时，THE VIP_Level_System SHALL 确保高等级的成长值门槛严格大于低等级的成长值门槛
4. WHEN 管理员更新等级配置时，THE VIP_Level_System SHALL 持久化配置并清除相关缓存
5. IF 管理员提交的等级配置中存在成长值门槛重叠或逆序，THEN THE VIP_Level_System SHALL 拒绝保存并返回具体的校验错误信息
6. WHEN 管理员查询等级配置列表时，THE VIP_Level_System SHALL 返回所有等级配置并按等级序号升序排列

### 需求 2：成长值体系

**用户故事：** 作为会员，我希望通过消费等行为积累成长值，以便提升我的VIP等级并享受更多权益。

#### 验收标准

1. WHEN 会员完成一笔订单支付时，THE VIP_Level_System SHALL 根据订单实付金额（单位：分）增加对应的成长值
2. WHEN 会员的成长值发生变化时，THE VIP_Level_System SHALL 重新计算该会员的VIP等级
3. WHEN 会员的成长值达到更高等级的门槛时，THE VIP_Level_System SHALL 自动将会员升级到对应等级
4. WHEN 会员的成长值因退款等原因减少且低于当前等级门槛时，THE VIP_Level_System SHALL 自动将会员降级到匹配的等级
5. THE VIP_Level_System SHALL 记录每次成长值变动的来源、变动值和变动后的总成长值

### 需求 3：积分体系

**用户故事：** 作为会员，我希望通过各种行为获得积分，以便在后续消费中使用积分进行抵扣或兑换。

#### 验收标准

1. THE Points_Wallet SHALL 记录会员的积分余额、累计获得积分和累计消费积分
2. WHEN 积分发生变动时，THE Points_Wallet SHALL 创建一条积分流水记录，包含变动类型、变动数量、变动前余额和变动后余额
3. IF 积分扣减操作导致余额不足，THEN THE Points_Wallet SHALL 拒绝该操作并返回余额不足的错误信息
4. WHEN 管理员手动调整会员积分时，THE Points_Wallet SHALL 记录操作人信息和调整原因
5. THE Points_Wallet SHALL 确保积分余额在任何时刻均为非负整数

### 需求 4：注册赠送积分

**用户故事：** 作为新注册会员，我希望注册后自动获得赠送积分，以便体验积分功能并提升首次消费意愿。

#### 验收标准

1. WHEN 新会员完成注册时，THE VIP_Level_System SHALL 读取系统设置中的注册赠送积分数量（`mall.member.register_points`）
2. WHEN 注册赠送积分数量大于零时，THE VIP_Level_System SHALL 自动向该会员的积分钱包中增加对应数量的积分
3. WHEN 注册积分赠送成功时，THE VIP_Level_System SHALL 创建一条来源为"注册赠送"的积分流水记录
4. IF 系统设置中注册赠送积分数量为零或未配置，THEN THE VIP_Level_System SHALL 跳过积分赠送步骤且不产生流水记录
5. THE VIP_Level_System SHALL 确保同一会员仅在首次注册时获得一次注册赠送积分

### 需求 5：积分等级关联

**用户故事：** 作为会员，我希望VIP等级越高获得的积分倍率越大，以便激励我持续消费提升等级。

#### 验收标准

1. THE VIP_Level_System SHALL 为每个VIP等级配置一个积分倍率（`point_rate`），默认值为 1.0
2. WHEN 计算会员消费返积分时，THE VIP_Level_System SHALL 将基础积分乘以该会员当前等级的积分倍率
3. WHEN 会员等级发生变化时，THE VIP_Level_System SHALL 立即使用新等级的积分倍率计算后续积分奖励
4. THE VIP_Level_System SHALL 确保积分倍率计算结果向下取整为整数

### 需求 6：消费返积分

**用户故事：** 作为会员，我希望每次消费后按比例获得积分奖励，以便积累积分用于后续消费。

#### 验收标准

1. WHEN 会员完成订单支付时，THE VIP_Level_System SHALL 读取系统设置中的积分兑换比例（`mall.member.points_ratio`，表示每消费1元获得的基础积分数）
2. WHEN 计算消费返积分时，THE VIP_Level_System SHALL 按公式计算：返还积分 = floor(实付金额（元） × 积分比率 × 等级积分倍率)
3. WHEN 消费返积分计算完成且结果大于零时，THE VIP_Level_System SHALL 将积分存入会员的积分钱包并创建来源为"消费奖励"的积分流水记录
4. IF 计算得到的返还积分为零，THEN THE VIP_Level_System SHALL 跳过积分发放且不产生流水记录
5. WHEN 订单发生全额退款时，THE VIP_Level_System SHALL 扣回该订单对应的消费返还积分

### 需求 7：等级权益展示

**用户故事：** 作为会员，我希望查看自己的VIP等级信息和权益详情，以便了解当前等级的优惠和升级所需条件。

#### 验收标准

1. WHEN 会员查询个人VIP信息时，THE VIP_Level_System SHALL 返回当前等级名称、等级图标、当前成长值、升级所需成长值和当前等级权益列表
2. WHEN 会员查询等级列表时，THE VIP_Level_System SHALL 返回所有等级的名称、成长值门槛和权益摘要
3. THE VIP_Level_System SHALL 计算并返回当前成长值距离下一等级的差值

### 需求 8：后台会员列表展示余额与积分

**用户故事：** 作为管理员，我希望在后台会员列表中直接看到每个会员的账户余额和积分余额，以便快速了解会员资产状况而无需逐一查看详情。

#### 验收标准

1. THE Admin_Member_List SHALL 在表格中显示"余额"列，展示会员的账户余额（单位：元，保留两位小数）
2. THE Admin_Member_List SHALL 在表格中显示"积分"列，展示会员的积分钱包余额
3. WHEN 会员列表数据加载时，THE Admin_Member_List SHALL 同时加载会员的 balance 钱包和 points 钱包关联数据
4. WHEN 会员没有对应钱包记录时，THE Admin_Member_List SHALL 将余额和积分显示为 0

### 需求 9：小程序个人中心余额积分展示

**用户故事：** 作为会员，我希望在小程序个人中心页面醒目地看到我的余额和积分，以便随时了解自己的资产状况。

#### 验收标准

1. THE Mini_Program_User_Center SHALL 在个人中心页面的 countsData 区域展示余额和积分数据项
2. WHEN 个人中心页面加载时，THE Mini_Program_User_Center SHALL 从 API 获取会员的余额（单位：分）和积分余额
3. THE Mini_Program_User_Center SHALL 将余额从分转换为元并保留两位小数后展示
4. WHEN 会员点击余额数据项时，THE Mini_Program_User_Center SHALL 跳转到余额流水记录页面
5. WHEN 会员点击积分数据项时，THE Mini_Program_User_Center SHALL 跳转到积分流水记录页面

### 需求 10：余额与积分流水记录查询

**用户故事：** 作为会员，我希望在小程序中查看余额和积分的变动明细，以便了解每笔资产变动的来源和时间。

#### 验收标准

1. WHEN 会员访问流水记录页面时，THE Wallet_Transaction_API SHALL 根据钱包类型（balance 或 points）返回该会员的流水记录列表
2. THE Wallet_Transaction_API SHALL 对流水记录按创建时间倒序排列并支持分页查询
3. THE Wallet_Transaction_API SHALL 返回每条流水记录的变动类型、变动金额、变动前余额、变动后余额、来源描述和创建时间
4. WHEN 请求的钱包类型参数缺失或无效时，THE Wallet_Transaction_API SHALL 返回参数校验错误信息
5. THE Mini_Program_Transaction_Page SHALL 展示流水记录列表，每条记录显示变动类型标签、金额变动（正数显示为+，负数显示为-）、来源描述和时间
6. THE Mini_Program_Transaction_Page SHALL 支持下拉刷新和触底加载更多功能
