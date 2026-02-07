# 设计文档：运费功能（V2 修订）

## 概述

本设计涵盖四部分：商品运费字段扩展（新增 `default` 枚举）、商城全局运费配置（简化交互）、运费模板 CRUD（已完成）、订单运费计算服务（新增）。遵循项目现有 DDD 分层架构。

## 架构

### 数据流

```
商品运费：Product.freight_type → 决定运费来源
  ├─ free     → 包邮（检查偏远地区加价）
  ├─ flat     → Product.flat_freight_amount
  ├─ template → ShippingTemplate.rules（按 region + charge_type 计算）
  └─ default  → 回退到商城系统配置 mall.shipping.default_freight_type
                 ├─ free     → 包邮（检查偏远地区加价）
                 ├─ flat     → mall.shipping.flat_freight_amount
                 └─ template → mall.shipping.default_template_config.template_id

偏远地区加价：
  mall.shipping.remote_area_enabled = true
  && 收货省份 ∈ mall.shipping.remote_area_provinces
  → 运费 += mall.shipping.remote_area_surcharge
```

### 分层职责

| 层 | 组件 | 职责 |
|---|---|---|
| Interface | ShippingTemplateController | HTTP 路由、Request 验证 |
| Interface | ShippingTemplateRequest | 字段验证规则 |
| Application | AppShippingTemplateCommandService | 事务编排 |
| Application | AppShippingTemplateQueryService | 查询编排 |
| Domain | DomainShippingTemplateService | 业务逻辑 |
| Domain | ShippingTemplateEntity | 实体 |
| Domain | ShippingTemplateMapper | Model ↔ Entity |
| Domain | **FreightCalculationService** | **运费计算（新增）** |
| Infrastructure | ShippingTemplateRepository | 数据持久化 |
| Infrastructure | ShippingTemplate (Model) | Eloquent Model |

## 组件设计

### 1. 数据库变更（Delta）

#### 商品表 freight_type 枚举扩展（新 Migration）

```php
// 新增 migration: alter_freight_type_add_default_to_products_table
Schema::table('products', function (Blueprint $table) {
    // MySQL ALTER COLUMN 修改 enum 值
    DB::statement("ALTER TABLE products MODIFY COLUMN freight_type ENUM('free','flat','template','default') DEFAULT 'default' COMMENT '运费类型'");
});
```

注意：需要新建 migration 文件，不修改已有 migration。

### 2. 商城配置变更（Delta）

#### mall.php shipping 分组修改

**删除项：**
- `mall.shipping.default_template_enabled` — 整个配置项删除

**修改项：**

```php
'mall.shipping.flat_freight_amount' => [
    'label' => '统一运费金额',
    'description' => '选择统一运费时的固定金额（单位：分）。',
    'type' => 'json',
    'meta' => [
        'component' => 'form',
        'display' => 'dialog',
        'trigger_key' => 'mall.shipping.default_freight_type',
        'trigger_value' => 'flat',
        'button_label' => '配置运费',
        'fields' => [
            ['key' => 'amount', 'label' => '运费金额（分）', 'component' => 'number', 'required' => true],
        ],
    ],
    'default' => ['amount' => 0],
    'sort' => 56,
],
```

```php
'mall.shipping.remote_area_surcharge' => [
    'label' => '偏远地区加价配置',
    'description' => '偏远地区额外运费和省份配置。',
    'type' => 'json',
    'meta' => [
        'component' => 'form',
        'display' => 'dialog',
        'trigger_key' => 'mall.shipping.remote_area_enabled',
        'trigger_value' => true,
        'button_label' => '配置加价',
        'fields' => [
            ['key' => 'surcharge', 'label' => '加价金额（分）', 'component' => 'number', 'required' => true],
            ['key' => 'provinces', 'label' => '偏远地区省份', 'component' => 'tags', 'required' => true,
             'placeholder' => '输入省份名称后回车'],
        ],
    ],
    'default' => [
        'surcharge' => 0,
        'provinces' => ['西藏', '新疆', '青海', '内蒙古', '宁夏', '甘肃'],
    ],
    'sort' => 58,
],
```

**删除项（合并到 remote_area_surcharge dialog）：**
- `mall.shipping.remote_area_provinces` — 合并到 `remote_area_surcharge` 弹窗中

```php
'mall.shipping.default_template_config' => [
    'label' => '默认运费模板',
    'description' => '全局默认运费模板配置。',
    'type' => 'json',
    'meta' => [
        'component' => 'form',
        'display' => 'dialog',
        'trigger_key' => 'mall.shipping.default_freight_type',  // 改为直接依赖 freight_type
        'trigger_value' => 'template',
        'button_label' => '模板配置',
        'fields' => [
            ['key' => 'template_id', 'label' => '运费模板', 'component' => 'select', 'required' => true,
             'description' => '选择已创建的运费模板'],
        ],
    ],
    'default' => ['template_id' => 1],  // 默认给一个偏远地区模板 ID
    'sort' => 61,
],
```

### 3. ShippingSetting 值对象变更（Delta）

移除 `defaultTemplateEnabled` 属性和方法。
`remoteAreaSurcharge` 和 `remoteAreaProvinces` 的数据来源从独立配置项改为从 `remote_area_surcharge` JSON 中解析。
`flatFreightAmount` 的数据来源从独立 integer 改为从 `flat_freight_amount` JSON 的 `amount` 字段解析。

```php
final class ShippingSetting
{
    public function __construct(
        private readonly string $defaultMethod,
        private readonly bool $enablePickup,
        private readonly string $pickupAddress,
        private readonly int $freeShippingThreshold,
        private readonly array $supportedProviders,
        private readonly string $defaultFreightType = 'free',
        private readonly int $flatFreightAmount = 0,
        private readonly bool $remoteAreaEnabled = false,
        private readonly int $remoteAreaSurcharge = 0,
        private readonly array $remoteAreaProvinces = [],
        // 移除: private readonly bool $defaultTemplateEnabled = false,
        private readonly array $defaultTemplateConfig = [],
    ) {}
}
```

### 4. DomainMallSettingService::shipping() 变更（Delta）

```php
public function shipping(): ShippingSetting
{
    $flatFreightConfig = $this->normalizeArray($this->value('mall.shipping.flat_freight_amount', []));
    $remoteAreaConfig = $this->normalizeArray($this->value('mall.shipping.remote_area_surcharge', []));

    return $this->shipping ??= new ShippingSetting(
        (string) $this->value('mall.shipping.default_method', 'express'),
        (bool) $this->value('mall.shipping.enable_pickup', true),
        (string) $this->value('mall.shipping.pickup_address', ''),
        (int) $this->value('mall.shipping.free_shipping_threshold', 0),
        $this->normalizeStringArray($this->value('mall.shipping.supported_providers', [])),
        (string) $this->value('mall.shipping.default_freight_type', 'free'),
        (int) ($flatFreightConfig['amount'] ?? 0),
        (bool) $this->value('mall.shipping.remote_area_enabled', false),
        (int) ($remoteAreaConfig['surcharge'] ?? 0),
        $this->normalizeStringArray($remoteAreaConfig['provinces'] ?? []),
        // 移除: default_template_enabled
        $this->normalizeArray($this->value('mall.shipping.default_template_config', [])),
    );
}
```

### 5. ProductRequest 验证变更（Delta）

```php
// baseRules() 中修改
'freight_type' => ['required', 'in:free,flat,template,default'],
// flat_freight_amount 和 shipping_template_id 规则不变（已有 required_if 条件）
```

### 6. 商品表 Migration 变更（Delta）

新建 migration 文件修改 `freight_type` 枚举值，新增 `default` 选项并将默认值改为 `default`。

### 7. FreightCalculationService（新增）

位置：`app/Domain/Shipping/Service/FreightCalculationService.php`

```php
final class FreightCalculationService
{
    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly ShippingTemplateRepository $templateRepository,
    ) {}

    /**
     * 计算单个商品的运费.
     *
     * @param string $freightType 商品的 freight_type
     * @param int $flatFreightAmount 商品的 flat_freight_amount
     * @param int|null $shippingTemplateId 商品的 shipping_template_id
     * @param string $province 收货省份
     * @param int $quantity 商品数量
     * @param int $weight 商品重量(g)
     * @param int $volume 商品体积(cm³)
     * @return int 运费金额（分）
     */
    public function calculate(
        string $freightType,
        int $flatFreightAmount,
        ?int $shippingTemplateId,
        string $province,
        int $quantity = 1,
        int $weight = 0,
        int $volume = 0,
    ): int {
        $shipping = $this->mallSettingService->shipping();

        // 解析实际运费类型
        $resolvedType = $freightType;
        $resolvedFlatAmount = $flatFreightAmount;
        $resolvedTemplateId = $shippingTemplateId;

        if ($freightType === 'default') {
            $resolvedType = $shipping->defaultFreightType();
            $resolvedFlatAmount = $shipping->flatFreightAmount();
            $resolvedTemplateId = $shipping->defaultTemplateConfig()['template_id'] ?? null;
        }

        // 按类型计算基础运费
        $freight = match ($resolvedType) {
            'free' => 0,
            'flat' => $resolvedFlatAmount,
            'template' => $this->calculateByTemplate($resolvedTemplateId, $province, $quantity, $weight, $volume),
            default => 0,
        };

        // 偏远地区加价
        if ($shipping->remoteAreaEnabled() && $this->isRemoteArea($province, $shipping->remoteAreaProvinces())) {
            $freight += $shipping->remoteAreaSurcharge();
        }

        return $freight;
    }

    private function calculateByTemplate(?int $templateId, string $province, int $quantity, int $weight, int $volume): int
    {
        if ($templateId === null) {
            return 0;
        }

        $template = $this->templateRepository->findById($templateId);
        if ($template === null) {
            return 0;
        }

        $entity = ShippingTemplateMapper::fromModel($template);
        $rules = $entity->getRules();
        $chargeType = $entity->getChargeType();

        // 找到匹配收货地区的规则
        $matchedRule = $this->findMatchingRule($rules, $province);
        if ($matchedRule === null) {
            return 0;
        }

        // 根据 charge_type 确定计费单位值
        $unitValue = match ($chargeType) {
            'weight' => $weight,
            'quantity' => $quantity,
            'volume' => $volume,
            default => $quantity,
        };

        // 计算阶梯运费
        return $this->calculateStepFreight($matchedRule, $unitValue);
    }

    private function findMatchingRule(array $rules, string $province): ?array
    {
        // 遍历规则，匹配省份对应的 region_id
        foreach ($rules as $rule) {
            $regionIds = $rule['region_ids'] ?? [];
            // 省份匹配逻辑（需要省份名称到行政区划代码的映射）
            // 简化实现：直接匹配
            if (in_array($province, $regionIds, true)) {
                return $rule;
            }
        }
        // 未匹配到则取第一条规则作为默认
        return $rules[0] ?? null;
    }

    private function calculateStepFreight(array $rule, int $unitValue): int
    {
        $firstUnit = $rule['first_unit'] ?? 1;
        $firstPrice = $rule['first_price'] ?? 0;
        $additionalUnit = $rule['additional_unit'] ?? 1;
        $additionalPrice = $rule['additional_price'] ?? 0;

        if ($unitValue <= $firstUnit) {
            return $firstPrice;
        }

        $remaining = $unitValue - $firstUnit;
        $additionalCount = $additionalUnit > 0 ? (int) ceil($remaining / $additionalUnit) : 0;

        return $firstPrice + ($additionalCount * $additionalPrice);
    }

    /**
     * @param string[] $remoteProvinces
     */
    private function isRemoteArea(string $province, array $remoteProvinces): bool
    {
        return in_array($province, $remoteProvinces, true);
    }
}
```

### 8. 订单集成（Delta）

订单预览和订单提交流程中，调用 `FreightCalculationService::calculate()` 计算运费。
两个流程使用同一个服务方法，确保运费金额一致。

调用点：
- 订单预览：`DomainApiOrderCommandService` 中的 preview 逻辑
- 订单提交：`DomainApiOrderCommandService` 中的 submit 逻辑

## 数据模型

### freight_type 枚举值

| 值 | 含义 | 商品表单 UI |
|---|---|---|
| `free` | 全国包邮 | 不显示额外输入 |
| `flat` | 统一运费 | 显示运费金额输入框（分） |
| `template` | 运费模板 | 显示运费模板下拉选择 |
| `default` | 系统默认 | 不显示额外输入，使用商城配置 |

## 正确性属性

*属性是系统在所有有效执行中应保持为真的特征或行为——本质上是关于系统应该做什么的形式化陈述。属性是人类可读规范与机器可验证正确性保证之间的桥梁。*

### Property 1: 运费类型条件验证一致性

*For any* 商品请求数据和任意 `freight_type` 值，当 `freight_type=flat` 时 `flat_freight_amount` 必须为 required 且为有效整数，当 `freight_type=template` 时 `shipping_template_id` 必须为 required 且存在，当 `freight_type=default` 或 `freight_type=free` 时两者均不要求。只有 `free`、`flat`、`template`、`default` 四个值通过验证，其他任意字符串均被拒绝。

**Validates: Requirements 1.5, 1.6, 1.7, 1.8**

### Property 2: 运费类型解析回退

*For any* 商品，当 `freight_type` 不为 `default` 时，FreightCalculationService 使用商品自身的运费配置（freight_type、flat_freight_amount、shipping_template_id）；当 `freight_type=default` 时，使用商城系统配置（`mall.shipping.default_freight_type`、对应的金额或模板 ID）。

**Validates: Requirements 5.2, 5.3**

### Property 3: 包邮类型运费为零

*For any* 解析后运费类型为 `free` 的商品（无论来自商品自身还是系统回退），在不考虑偏远地区加价的情况下，FreightCalculationService 返回的基础运费 SHALL 为 0。

**Validates: Requirements 5.4**

### Property 4: 统一运费返回精确金额

*For any* 解析后运费类型为 `flat` 的商品，FreightCalculationService 返回的基础运费 SHALL 等于对应的 `flat_freight_amount`（商品级或系统级）。

**Validates: Requirements 5.5**

### Property 5: 模板阶梯运费计算

*For any* 有效的运费模板和正整数计费单位值，FreightCalculationService 的阶梯计算结果 SHALL 满足：当 unitValue ≤ first_unit 时返回 first_price；当 unitValue > first_unit 时返回 first_price + ceil((unitValue - first_unit) / additional_unit) * additional_price。

**Validates: Requirements 5.6**

### Property 6: 偏远地区加价

*For any* 订单，当 `remote_area_enabled=true` 且收货省份在 `remote_area_provinces` 列表中时，最终运费 SHALL 等于基础运费 + `remote_area_surcharge`；当省份不在列表中或 `remote_area_enabled=false` 时，最终运费 SHALL 等于基础运费。

**Validates: Requirements 5.7**

## 错误处理

| 场景 | 异常类型 | 消息 |
|---|---|---|
| 运费模板不存在 | BusinessException(NOT_FOUND) | 运费模板不存在 |
| 删除被商品引用的模板 | BusinessException(FORBIDDEN) | 该模板正在被商品使用，无法删除 |
| freight_type=template 但 shipping_template_id 无效 | ValidationException | shipping_template_id 不存在 |
| freight_type=flat 但 flat_freight_amount 未填 | ValidationException | flat_freight_amount 必填 |
| freight_type 值不在枚举范围内 | ValidationException | freight_type 必须为 free/flat/template/default |
| 运费计算时模板 ID 对应的模板不存在 | 返回运费 0 | 静默降级，不抛异常 |

## 测试策略

### 单元测试

- 验证 `mall.php` 配置结构变更（删除 `default_template_enabled`，trigger 条件变更）
- 验证 `ShippingSetting` 构造函数不再接受 `defaultTemplateEnabled` 参数
- 验证 `ProductRequest` 的 `freight_type` 验证规则包含 `default`
- 验证 `FreightCalculationService` 各分支的具体计算结果

### 属性测试

使用 PHPUnit 手动实现属性测试（PHP 生态无成熟 PBT 库，使用循环 + 随机数据模拟），每个属性至少 100 次迭代。

- **Property 1**: 生成随机 freight_type 值和对应的请求数据，验证条件验证规则的正确性
  - Tag: **Feature: shipping-freight-feature, Property 1: 运费类型条件验证一致性**
- **Property 2**: 生成随机商品配置（freight_type 随机为 free/flat/template/default），验证解析逻辑
  - Tag: **Feature: shipping-freight-feature, Property 2: 运费类型解析回退**
- **Property 3**: 生成随机商品（freight_type=free 或 default+系统配置=free），验证运费为 0
  - Tag: **Feature: shipping-freight-feature, Property 3: 包邮类型运费为零**
- **Property 4**: 生成随机 flat_freight_amount 值，验证返回值精确匹配
  - Tag: **Feature: shipping-freight-feature, Property 4: 统一运费返回精确金额**
- **Property 5**: 生成随机模板规则和 unitValue，验证阶梯计算公式
  - Tag: **Feature: shipping-freight-feature, Property 5: 模板阶梯运费计算**
- **Property 6**: 生成随机省份和偏远地区配置，验证加价逻辑
  - Tag: **Feature: shipping-freight-feature, Property 6: 偏远地区加价**
