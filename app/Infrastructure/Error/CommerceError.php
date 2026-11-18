<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum CommerceError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ORDER_NOT_FOUND = 310001;
    case FULFILLMENT_UNAVAILABLE = 310002;
    case PROCUREMENT_ORDER_INELIGIBLE = 310003;
    case PROCUREMENT_QUANTITY_INVALID = 310004;
    case PROCUREMENT_ITEMS_REQUIRED = 310005;
    case PROCUREMENT_SNAPSHOT_INVALID = 310006;
    case PROCUREMENT_ITEMS_MISMATCH = 310007;
    case PROCUREMENT_AFTER_SALE_CONFLICT = 310008;
    case SHIPMENT_DETAILS_REQUIRED = 310009;
    case SHIPMENT_ITEM_INVALID = 310010;
    case SHIPMENT_PROVIDER_MISMATCH = 310011;
    case SHIPMENT_QUANTITY_EXCEEDED = 310012;
    case IMPORT_CLAIM_FAILED = 310013;
    case AFTER_SALE_NOT_FOUND = 310014;
    case AFTER_SALE_PROVIDER_MISMATCH = 310015;
    case PROMOTION_FULFILLMENT_UNSUPPORTED = 310016;
    case FULFILLMENT_CHANGED = 310017;
    case SHIPPING_QUOTE_INVALID = 310018;
    case SNAPSHOT_INCOMPLETE = 310019;
    case ORDER_AMOUNT_NOT_BUILT = 310020;
    case IMPORT_KEY_INVALID = 310021;
    case DELETED_IMPORT_REUSED = 310022;
    case IMPORT_FIELDS_REQUIRED = 310023;
    case IMPORT_SKU_CODE_REQUIRED = 310024;
    case IMPORT_SKU_CODE_DUPLICATED = 310025;
    case PRODUCT_PROVIDER_MISMATCH = 310026;
    case NEW_SKU_CODE_REQUIRED = 310027;
    case NEW_SKU_PRICE_REQUIRED = 310028;
    case SKU_CODE_OWNED_BY_OTHER_PRODUCT = 310029;
    case SYNC_SKU_OWNER_MISMATCH = 310030;
    case PROVIDER_INVALID = 310031;
    case SKU_AMOUNT_INVALID = 310032;
    case MERCHANT_PACKAGE_REQUIRED = 310033;
    case PACKAGE_ALLOCATION_REQUIRED = 310034;
    case PACKAGE_KEY_REQUIRED = 310035;
    case PACKAGE_IDEMPOTENCY_CONFLICT = 310036;
    case ORDER_NOT_SHIPPABLE = 310037;
    case SHIPMENT_AFTER_SALE_CONFLICT = 310038;

    public function template(): string
    {
        return match ($this) {
            self::ORDER_NOT_FOUND => '订单不存在',
            self::FULFILLMENT_UNAVAILABLE => '商品履约服务暂不可用，请稍后重试',
            self::PROCUREMENT_ORDER_INELIGIBLE => '销售订单未支付、已取消、已退款或已履约，不能新增采购',
            self::PROCUREMENT_QUANTITY_INVALID => '采购保护SKU和数量必须为正整数',
            self::PROCUREMENT_ITEMS_REQUIRED => '采购保护明细不能为空',
            self::PROCUREMENT_SNAPSHOT_INVALID => '采购商品缺少对应履约快照或归属不一致',
            self::PROCUREMENT_ITEMS_MISMATCH => '采购明细与销售订单商品或数量不一致',
            self::PROCUREMENT_AFTER_SALE_CONFLICT => '采购商品已申请售后或已退款，需先核实售后结果',
            self::SHIPMENT_DETAILS_REQUIRED => '包裹物流信息与发货明细不能为空',
            self::SHIPMENT_ITEM_INVALID => '包裹数量无效或订单明细不属于该订单',
            self::SHIPMENT_PROVIDER_MISMATCH => '包裹明细不属于当前履约能力',
            self::SHIPMENT_QUANTITY_EXCEEDED => '累计发货数量超过订单数量',
            self::IMPORT_CLAIM_FAILED => '无法领取商品导入记录',
            self::AFTER_SALE_NOT_FOUND => '售后单不存在',
            self::AFTER_SALE_PROVIDER_MISMATCH => '售后单不属于当前履约能力',
            self::PROMOTION_FULFILLMENT_UNSUPPORTED => '外部履约商品暂不支持活动库存下单',
            self::FULFILLMENT_CHANGED => '商品履约信息已变化，请重新结算',
            self::SHIPPING_QUOTE_INVALID => '履约报价运费无效',
            self::SNAPSHOT_INCOMPLETE => '履约服务未返回完整商品快照',
            self::ORDER_AMOUNT_NOT_BUILT => '订单金额尚未构建',
            self::IMPORT_KEY_INVALID => '商品导入幂等键不能为空或超过512字节',
            self::DELETED_IMPORT_REUSED => '已导入商品已删除，不能以相同身份重复导入',
            self::IMPORT_FIELDS_REQUIRED => '导入商品必须包含名称、本地分类和SKU',
            self::IMPORT_SKU_CODE_REQUIRED => '导入SKU需要有效的本地编码',
            self::IMPORT_SKU_CODE_DUPLICATED => '导入SKU编码重复',
            self::PRODUCT_PROVIDER_MISMATCH => '商品不属于当前履约能力',
            self::NEW_SKU_CODE_REQUIRED => '新增SKU需要有效的本地编码',
            self::NEW_SKU_PRICE_REQUIRED => '新增SKU缺少销售价',
            self::SKU_CODE_OWNED_BY_OTHER_PRODUCT => 'SKU编码已属于其他商品，禁止跨商品复用',
            self::SYNC_SKU_OWNER_MISMATCH => '同步SKU不属于当前商品，新增规格须使用受控增规接口',
            self::PROVIDER_INVALID => '无效的外部履约能力标识',
            self::SKU_AMOUNT_INVALID => 'SKU金额必须为非负整数分',
            self::MERCHANT_PACKAGE_REQUIRED => '请提供自营商品包裹',
            self::PACKAGE_ALLOCATION_REQUIRED => '没有待发自营商品，或多包裹缺少逐项数量',
            self::PACKAGE_KEY_REQUIRED => '包裹幂等键不能为空',
            self::PACKAGE_IDEMPOTENCY_CONFLICT => '同一外部包裹不能改变发货明细或运单',
            self::ORDER_NOT_SHIPPABLE => '当前订单状态不可发货',
            self::SHIPMENT_AFTER_SALE_CONFLICT => '包裹商品存在售后，须核对后再同步履约',
        };
    }
}
