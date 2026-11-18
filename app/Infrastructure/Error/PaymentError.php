<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum PaymentError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case METHOD_UNSUPPORTED = 210001;
    case REFUND_MERCHANT_MISMATCH = 210002;
    case ALREADY_PAID = 210003;
    case PAID_RECORD_NOT_CANCELLABLE = 210004;
    case UNPAID_RECORD_NOT_REFUNDABLE = 210005;
    case REFUND_EXCEEDS_PAID_AMOUNT = 210006;
    case CHANNEL_STATUS_UNCONFIRMED = 210007;
    case CHANNEL_PAID_AWAITING_RECONCILIATION = 210008;
    case RECEIPT_ALREADY_EXISTS = 210009;
    case PENDING_RECEIPT_EXISTS = 210010;
    case WECHAT_DISABLED = 210011;
    case WECHAT_PAYMENT_PENDING = 210012;
    case WALLET_UNAVAILABLE = 210013;
    case CALLBACK_ORDER_MISSING = 210014;
    case ANOTHER_PAYMENT_COMPLETED = 210015;
    case RECORD_AMOUNT_MISMATCH = 210016;
    case BALANCE_DISABLED = 210017;
    case BALANCE_LIMIT_EXCEEDED = 210018;
    case MANUAL_AMOUNT_INVALID = 210019;
    case WECHAT_REQUEST_INVALID = 210020;
    case WECHAT_IDENTITY_MISMATCH = 210021;
    case WECHAT_AMOUNT_MISMATCH = 210022;
    case RECORD_NOT_FOUND_BY_PAYMENT_NO = 210023;
    case RECORD_NOT_FOUND_BY_ORDER_NO = 210024;
    case RECORD_NOT_FOUND_BY_ORDER_ID = 210025;
    case RECORD_NOT_FOUND_BY_THIRD_PARTY_NO = 210026;

    public function template(): string
    {
        return match ($this) {
            self::METHOD_UNSUPPORTED => '不支持的支付方式',
            self::REFUND_MERCHANT_MISMATCH => '退款通知商户不匹配',
            self::ALREADY_PAID => '支付记录已支付，不能重复支付',
            self::PAID_RECORD_NOT_CANCELLABLE => '已支付的记录不能取消',
            self::UNPAID_RECORD_NOT_REFUNDABLE => '只有已支付的记录才能退款',
            self::REFUND_EXCEEDS_PAID_AMOUNT => '退款金额不能超过实付金额',
            self::CHANNEL_STATUS_UNCONFIRMED => '支付渠道状态不是确定终态',
            self::CHANNEL_PAID_AWAITING_RECONCILIATION => '渠道已支付，等待支付通知或对账',
            self::RECEIPT_ALREADY_EXISTS => '订单已有收款记录，请先核实支付情况',
            self::PENDING_RECEIPT_EXISTS => '订单存在待确认的支付流水，请先核实原支付渠道状态后再人工认款',
            self::WECHAT_DISABLED => '微信支付未启用',
            self::WECHAT_PAYMENT_PENDING => '已发起微信支付，请完成或取消原支付订单',
            self::WALLET_UNAVAILABLE => '钱包被冻结或可用余额不足',
            self::CALLBACK_ORDER_MISSING => '支付通知缺少订单号',
            self::ANOTHER_PAYMENT_COMPLETED => '订单已由另一笔支付完成，需对账',
            self::RECORD_AMOUNT_MISMATCH => '支付记录不存在或金额不匹配',
            self::BALANCE_DISABLED => '余额支付未启用',
            self::BALANCE_LIMIT_EXCEEDED => '余额支付超过配置限额: %s',
            self::MANUAL_AMOUNT_INVALID => '人工认款金额必须大于零',
            self::WECHAT_REQUEST_INVALID => '微信支付需要有效的会员 openid 和正数金额',
            self::WECHAT_IDENTITY_MISMATCH => '微信支付通知身份不匹配',
            self::WECHAT_AMOUNT_MISMATCH => '微信支付通知金额或币种不匹配',
            self::RECORD_NOT_FOUND_BY_PAYMENT_NO => '支付记录不存在: payment_no=%s',
            self::RECORD_NOT_FOUND_BY_ORDER_NO => '支付记录不存在: order_no=%s',
            self::RECORD_NOT_FOUND_BY_ORDER_ID => '支付记录不存在: order_id=%d',
            self::RECORD_NOT_FOUND_BY_THIRD_PARTY_NO => '支付记录不存在: third_party_no=%s',
        };
    }
}
