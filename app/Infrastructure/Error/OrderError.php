<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum OrderError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case NOT_FOUND = 200001;
    case SUBMISSION_NOT_FOUND = 200002;
    case SUBMISSION_FAILED = 200003;
    case UNKNOWN_TRADE_EVENT = 200004;
    case LOGIN_REQUIRED = 200005;
    case ITEM_REQUIRED = 200006;
    case ADDRESS_REQUIRED = 200007;
    case SKU_UNAVAILABLE = 200008;
    case PRODUCT_OFF_SHELF = 200009;
    case PRODUCT_DISABLED = 200010;
    case TYPE_UNSUPPORTED = 200011;
    case EXTERNAL_PACKAGE_REQUIRED = 200012;
    case NOT_SHIPPABLE = 200013;
    case PACKAGE_REQUIRED = 200014;
    case NOT_COMPLETABLE = 200015;
    case NOT_CANCELLABLE = 200016;
    case NOT_PAYABLE = 200017;
    case NOT_MANUALLY_PAYABLE = 200018;
    case PRICE_CHANGED = 200019;
    case FULFILLMENT_SNAPSHOT_INCOMPLETE = 200020;
    case SUBMISSION_CONFLICT = 200021;
    case GROUP_NOT_COMPLETED = 200022;
    case STOCK_BUSY = 200023;
    case STOCK_ITEMS_EMPTY = 200024;
    case STOCK_UNAVAILABLE = 200025;
    case EXPIRED = 200026;
    case PAYMENT_STATUS_UNSUPPORTED = 200027;

    public function template(): string
    {
        return match ($this) {
            self::NOT_FOUND => '订单不存在',
            self::SUBMISSION_NOT_FOUND => '订单提交不存在',
            self::SUBMISSION_FAILED => '订单创建失败，请稍后重试',
            self::UNKNOWN_TRADE_EVENT => '未知交易事件：%s',
            self::LOGIN_REQUIRED => '请先登录后再下单',
            self::ITEM_REQUIRED => '至少选择一件商品',
            self::ADDRESS_REQUIRED => '请提供收货地址',
            self::SKU_UNAVAILABLE => 'SKU %d 不存在或已下架',
            self::PRODUCT_OFF_SHELF => '商品 %s 已下架',
            self::PRODUCT_DISABLED => '商品 %s 已禁用',
            self::TYPE_UNSUPPORTED => '不支持的订单类型：%s',
            self::EXTERNAL_PACKAGE_REQUIRED => '外部履约订单须按包裹明细登记发货',
            self::NOT_SHIPPABLE => '当前订单状态不可发货',
            self::PACKAGE_REQUIRED => '请至少提供一个包裹信息',
            self::NOT_COMPLETABLE => '当前订单状态不可完成',
            self::NOT_CANCELLABLE => '已支付订单请申请退款，当前状态不可取消',
            self::NOT_PAYABLE => '当前订单状态不可支付',
            self::NOT_MANUALLY_PAYABLE => '只有未支付且未取消的订单可以人工认款',
            self::PRICE_CHANGED => '商品价格已变动，请重新下单',
            self::FULFILLMENT_SNAPSHOT_INCOMPLETE => '外部履约快照不完整',
            self::SUBMISSION_CONFLICT => '订单提交标识冲突',
            self::GROUP_NOT_COMPLETED => '拼团尚未成功，不能发货',
            self::STOCK_BUSY => '库存繁忙，请稍后重试',
            self::STOCK_ITEMS_EMPTY => '没有可扣减的商品',
            self::STOCK_UNAVAILABLE => '库存不足或商品已下架',
            self::EXPIRED => '订单已过期',
            self::PAYMENT_STATUS_UNSUPPORTED => '订单状态不支持支付',
        };
    }
}
