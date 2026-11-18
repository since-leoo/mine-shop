<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum InventoryError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case PROCUREMENT_OPERATION_INVALID = 190001;
    case PROCUREMENT_ITEMS_REQUIRED = 190002;
    case PROTECTED_RELEASE_PENDING = 190003;
    case RELEASE_MISMATCH = 190004;
    case QUANTITY_INVALID = 190005;
    case ITEMS_REQUIRED = 190006;
    case SKU_PROVIDER_MISMATCH = 190007;
    case PROJECTION_OWNER_MISMATCH = 190008;
    case SNAPSHOT_VERSION_CONFLICT = 190009;
    case SNAPSHOT_INVALID = 190010;
    case RECONCILIATION_ORDER_INVALID = 190011;
    case PROCUREMENT_ORDER_NOT_FOUND = 190012;

    case STOCK_UNAVAILABLE = 190013;

    case RESERVATION_MISMATCH = 190014;

    case PROJECTION_MISSING = 190015;

    case RECONCILIATION_UNCONFIRMED = 190016;

    case PROJECTION_PROVIDER_MISMATCH = 190017;

    case PROTECT_FAILED = 190018;

    case CLEAR_PROTECTION_FAILED = 190019;

    case ACKNOWLEDGE_MISMATCH = 190020;

    public function template(): string
    {
        return match ($this) {
            self::ACKNOWLEDGE_MISMATCH => '采购确认与原始库存预占不一致',
            self::CLEAR_PROTECTION_FAILED => '无法解除采购保护：已经确认采购或明细不一致',
            self::PROTECT_FAILED => '采购提交保护失败：原预占已释放、已采购或明细不一致',
            self::PROJECTION_PROVIDER_MISMATCH => '库存投影履约能力不匹配',
            self::RECONCILIATION_UNCONFIRMED => '对账包含未确认采购的预占',
            self::PROJECTION_MISSING => '库存投影丢失，需核对持久订单预占后恢复，禁止自动初始化',
            self::RESERVATION_MISMATCH => '库存预占已释放或请求不一致',
            self::STOCK_UNAVAILABLE => '库存不足或商品已下架',
            self::PROCUREMENT_OPERATION_INVALID => '采购库存操作SKU和数量无效',
            self::PROCUREMENT_ITEMS_REQUIRED => '采购库存操作明细不能为空',
            self::PROTECTED_RELEASE_PENDING => '外部采购结果未确认，受保护库存等待释放重试',
            self::RELEASE_MISMATCH => '库存释放与原预占不一致',
            self::QUANTITY_INVALID => '库存数量必须为正整数',
            self::ITEMS_REQUIRED => '商品不能为空',
            self::SKU_PROVIDER_MISMATCH => '库存SKU不属于当前履约能力',
            self::PROJECTION_OWNER_MISMATCH => '库存投影归属不一致',
            self::SNAPSHOT_VERSION_CONFLICT => '同一库存快照版本的数量不一致',
            self::SNAPSHOT_INVALID => '库存SKU、数量或版本无效',
            self::RECONCILIATION_ORDER_INVALID => '对账订单号无效',
            self::PROCUREMENT_ORDER_NOT_FOUND => '采购对应的销售订单不存在',
        };
    }
}
