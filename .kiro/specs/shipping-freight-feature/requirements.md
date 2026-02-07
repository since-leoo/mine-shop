# 需求文档：运费功能

## 简介

本文档定义商品运费计算功能的需求。包含三个层面：
1. 商品表增加运费类型字段
2. 商城后台「配送与物流」配置中增加运费类型全局设置
3. 运费模板 CRUD 管理（已有数据库表 `shipping_templates`，需补全后端服务和管理接口）

## 术语

- **freight_type**: 运费类型，枚举值：`free`（全国包邮）、`flat`（统一运费）、`template`（运费模板）
- **ShippingTemplate**: 运费模板实体，定义按重量/件数/体积的阶梯运费规则
- **remote_area_surcharge**: 偏远地区加价，在全国包邮模式下可选开启

## 需求

### 需求 1：商品表增加运费类型字段

**用户故事：** 作为商城管理员，我希望每个商品可以独立设置运费类型，以便灵活控制不同商品的运费策略。

#### 验收标准

1. 商品表 SHALL 新增 `freight_type` 字段，枚举值为 `free`、`flat`、`template`，默认 `free`
2. 商品表 SHALL 新增 `flat_freight_amount` 字段（unsigned int，单位分），默认 0，仅 `freight_type=flat` 时生效
3. 当 `freight_type=template` 时，SHALL 使用已有的 `shipping_template_id` 字段关联运费模板
4. ProductEntity、ProductInput、ProductDto SHALL 同步增加 `freight_type` 和 `flat_freight_amount` 属性
5. 商品创建/编辑 Request SHALL 验证 `freight_type` 为 `in:free,flat,template`
6. 当 `freight_type=flat` 时，`flat_freight_amount` SHALL 为 `required|integer|min:0|max:99999`
7. 当 `freight_type=template` 时，`shipping_template_id` SHALL 为 `required|integer|exists:shipping_templates,id`

### 需求 2：商城配置 — 运费类型全局设置

**用户故事：** 作为商城管理员，我希望在「配送与物流」中设置全局默认运费策略，作为商品未单独配置时的兜底。

#### 验收标准

1. `config/autoload/mall.php` 的 shipping 分组 SHALL 新增以下配置项：
   - `mall.shipping.default_freight_type`：默认运费类型，select 组件，选项 `free/flat/template`，默认 `free`
   - `mall.shipping.flat_freight_amount`：统一运费金额（分），integer，默认 0，trigger 条件为 `default_freight_type=flat`
   - `mall.shipping.remote_area_enabled`：偏远地区加价开关，boolean，默认 false
   - `mall.shipping.remote_area_surcharge`：偏远地区加价金额（分），integer，默认 0，trigger 条件为 `remote_area_enabled=true`
   - `mall.shipping.remote_area_provinces`：偏远地区省份列表，json tags 组件，trigger 条件为 `remote_area_enabled=true`
   - `mall.shipping.default_template_enabled`：默认运费模板开关，boolean，默认 false
   - `mall.shipping.default_template_config`：运费模板配置，json form，display=dialog，trigger 条件为 `default_template_enabled=true`，按钮文案「模板配置」
2. ShippingSetting 值对象 SHALL 新增对应属性和方法
3. DomainMallSettingService::shipping() SHALL 读取新增配置项

### 需求 3：运费模板 CRUD

**用户故事：** 作为商城管理员，我希望管理运费模板，以便为不同商品配置灵活的运费规则。

#### 验收标准

1. SHALL 创建 ShippingTemplate Model（对应已有的 `shipping_templates` 表）
2. SHALL 创建 ShippingTemplateEntity，包含属性：id, name, charge_type, rules, free_rules, is_default, status
3. SHALL 创建 ShippingTemplateMapper（Model ↔ Entity 转换）
4. SHALL 创建 ShippingTemplateRepository（CRUD 操作）
5. SHALL 创建 DomainShippingTemplateService（领域服务）
6. SHALL 创建 AppShippingTemplateCommandService 和 AppShippingTemplateQueryService（应用服务）
7. SHALL 创建 Admin ShippingTemplateController，提供以下接口：
   - `GET /admin/shipping/templates` — 列表（分页）
   - `GET /admin/shipping/templates/{id}` — 详情
   - `POST /admin/shipping/templates` — 创建
   - `PUT /admin/shipping/templates/{id}` — 更新
   - `DELETE /admin/shipping/templates/{id}` — 删除
8. SHALL 创建 ShippingTemplateRequest 验证：
   - `name`：`required|string|max:100`
   - `charge_type`：`required|in:weight,quantity,volume`
   - `rules`：`required|array|min:1`，每条规则包含 `region_ids`(array)、`first_unit`(integer)、`first_price`(integer,分)、`additional_unit`(integer)、`additional_price`(integer,分)
   - `free_rules`：`nullable|array`，每条包含 `region_ids`(array)、`free_by_amount`(integer,分)、`free_by_quantity`(integer)
   - `is_default`：`nullable|boolean`
   - `status`：`required|in:active,inactive`

### 需求 4：运费模板 rules JSON 结构设计

**用户故事：** 作为开发者，我需要明确运费模板的 JSON 数据结构，以便前后端统一实现。

#### 验收标准

1. `rules` 字段 SHALL 为数组，每个元素结构：
```json
{
  "region_ids": [110000, 120000],
  "first_unit": 1,
  "first_price": 800,
  "additional_unit": 1,
  "additional_price": 200
}
```
   - `region_ids`：地区编码数组（省级行政区划代码）
   - `first_unit`：首件/首重/首体积数量
   - `first_price`：首费（分）
   - `additional_unit`：续件/续重/续体积数量
   - `additional_price`：续费（分）

2. `free_rules` 字段 SHALL 为数组，每个元素结构：
```json
{
  "region_ids": [110000, 120000],
  "free_by_amount": 9900,
  "free_by_quantity": 3
}
```
   - `region_ids`：地区编码数组
   - `free_by_amount`：满额包邮门槛（分），0 表示不限
   - `free_by_quantity`：满件包邮门槛，0 表示不限

3. 当 `charge_type=weight` 时，unit 单位为克(g)
4. 当 `charge_type=quantity` 时，unit 单位为件
5. 当 `charge_type=volume` 时，unit 单位为立方厘米(cm³)
