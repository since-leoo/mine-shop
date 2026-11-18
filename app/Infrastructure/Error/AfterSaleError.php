<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum AfterSaleError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case NOT_FOUND = 260001;
    case TYPE_INVALID = 260002;
    case QUANTITY_INVALID = 260003;
    case AMOUNT_INVALID = 260004;
    case REFUND_ONLY_RETURN_UNSUPPORTED = 260005;
    case RESHIP_TYPE_INVALID = 260006;
    case EXCHANGE_RECEIPT_TYPE_INVALID = 260007;
    case REFUND_RECORD_NOT_FOUND = 260008;
    case REFUND_PAYMENT_MISMATCH = 260009;
    case REFUND_CALLBACK_MISMATCH = 260010;
    case REFUND_STATUS_UNKNOWN = 260011;
    case REVIEW_AMOUNT_EXCEEDED = 260012;
    case OFFLINE_AUTO_REFUND_UNSUPPORTED = 260013;
    case REFUNDABLE_PAYMENT_NOT_FOUND = 260014;
    case REFUND_APPLICATION_AMOUNT_EXCEEDED = 260015;
    case REFUND_BALANCE_EXCEEDED = 260016;
    case ORDER_TYPE_UNSUPPORTED = 260017;
    case LOAD_FAILED = 260018;
    case ORDER_ITEM_INELIGIBLE = 260019;
    case ACTIVE_REQUEST_EXISTS = 260020;
    case ORDER_STATUS_UNSUPPORTED = 260021;
    case APPLICATION_PERIOD_EXPIRED = 260022;
    case ORDER_ITEM_MISMATCH = 260023;
    case ALLOWANCE_EXCEEDED = 260024;
    case APPROVAL_STATUS_INVALID = 260025;
    case REJECTION_STATUS_INVALID = 260026;
    case CANCELLATION_STATUS_INVALID = 260027;
    case BUYER_RETURN_STATUS_INVALID = 260028;
    case REFUND_START_STATUS_INVALID = 260029;
    case REFUND_COMPLETE_STATUS_INVALID = 260030;
    case REFUND_FAILURE_STATUS_INVALID = 260031;
    case SELLER_RECEIPT_STATUS_INVALID = 260032;
    case RESHIP_STATUS_INVALID = 260033;
    case EXCHANGE_RECEIPT_STATUS_INVALID = 260034;

    public function template(): string
    {
        return match ($this) {
            self::NOT_FOUND => '售后单不存在',
            self::TYPE_INVALID => '售后类型不合法',
            self::QUANTITY_INVALID => '售后数量必须大于 0',
            self::AMOUNT_INVALID => '售后金额不能小于 0',
            self::REFUND_ONLY_RETURN_UNSUPPORTED => '仅退款售后不支持买家退货',
            self::RESHIP_TYPE_INVALID => '只有换货售后才能执行补发',
            self::EXCHANGE_RECEIPT_TYPE_INVALID => '只有换货售后才能确认收货',
            self::REFUND_RECORD_NOT_FOUND => '退款记录不存在',
            self::REFUND_PAYMENT_MISMATCH => '退款支付记录不匹配',
            self::REFUND_CALLBACK_MISMATCH => '退款通知订单或金额不匹配',
            self::REFUND_STATUS_UNKNOWN => '未知退款状态，保留待确认',
            self::REVIEW_AMOUNT_EXCEEDED => '审核退款金额不能超过本次申请的可退金额',
            self::OFFLINE_AUTO_REFUND_UNSUPPORTED => '线下支付订单不支持原路自动退款，请先联系财务处理线下退款',
            self::REFUNDABLE_PAYMENT_NOT_FOUND => '没有可退款的支付记录',
            self::REFUND_APPLICATION_AMOUNT_EXCEEDED => '退款金额超过本次售后申请上限',
            self::REFUND_BALANCE_EXCEEDED => '退款金额超过可退余额',
            self::ORDER_TYPE_UNSUPPORTED => '当前订单状态不支持该售后类型',
            self::LOAD_FAILED => '获取售后单失败',
            self::ORDER_ITEM_INELIGIBLE => '当前订单商品不可申请售后',
            self::ACTIVE_REQUEST_EXISTS => '该订单商品已存在进行中的售后单',
            self::ORDER_STATUS_UNSUPPORTED => '当前订单状态不支持申请售后',
            self::APPLICATION_PERIOD_EXPIRED => '订单已超过售后申请期',
            self::ORDER_ITEM_MISMATCH => '售后商品不属于订单',
            self::ALLOWANCE_EXCEEDED => '售后数量或金额超过该订单商品剩余可退范围',
            self::APPROVAL_STATUS_INVALID => '只有待审核的售后单才能审核通过',
            self::REJECTION_STATUS_INVALID => '只有待审核的售后单才能审核拒绝',
            self::CANCELLATION_STATUS_INVALID => '只有待审核的售后单才能撤销',
            self::BUYER_RETURN_STATUS_INVALID => '只有待买家退货状态才能提交退货物流',
            self::REFUND_START_STATUS_INVALID => '只有待退款状态才能发起退款',
            self::REFUND_COMPLETE_STATUS_INVALID => '只有退款处理中状态才能标记退款完成',
            self::REFUND_FAILURE_STATUS_INVALID => '只有退款处理中状态才能标记退款失败',
            self::SELLER_RECEIPT_STATUS_INVALID => '只有待商家收货状态才能确认收货',
            self::RESHIP_STATUS_INVALID => '只有待补发状态才能执行补发',
            self::EXCHANGE_RECEIPT_STATUS_INVALID => '只有已补发状态才能确认换货收货',
        };
    }
}
