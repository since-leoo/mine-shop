<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum CouponError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case NOT_FOUND = 220001;
    case USER_COUPON_NOT_FOUND = 220002;
    case RECEIVE_LIMIT_REACHED = 220003;
    case EFFECTIVE_TIME_INVALID = 220004;
    case INACTIVE = 220005;
    case NOT_STARTED = 220006;
    case EXPIRED = 220007;
    case STOCK_INSUFFICIENT = 220008;
    case EXPIRY_INVALID = 220009;
    case USED_COUPON_NOT_EXPIRABLE = 220010;
    case USED_COUPON_NOT_USABLE = 220011;
    case CREATE_FAILED = 220012;
    case USER_COUPON_CREATE_FAILED = 220013;
    case USED_OR_EXPIRED = 220014;
    case ISSUED_COUPON_NOT_DELETABLE = 220015;
    case MEMBER_IDS_EMPTY = 220016;
    case UNAVAILABLE_FOR_ORDER = 220017;
    case INACTIVE_FOR_ORDER = 220018;
    case MINIMUM_AMOUNT_NOT_MET = 220019;

    public function template(): string
    {
        return match ($this) {
            self::NOT_FOUND => '优惠券不存在',
            self::USER_COUPON_NOT_FOUND => '用户优惠券不存在',
            self::RECEIVE_LIMIT_REACHED => '已达到领取上限',
            self::EFFECTIVE_TIME_INVALID => '优惠券生效时间不合法',
            self::INACTIVE => '优惠券未启用',
            self::NOT_STARTED => '优惠券未开始',
            self::EXPIRED => '优惠券已过期',
            self::STOCK_INSUFFICIENT => '优惠券库存不足',
            self::EXPIRY_INVALID => '过期时间无效',
            self::USED_COUPON_NOT_EXPIRABLE => '仅未使用的优惠券可以置为过期',
            self::USED_COUPON_NOT_USABLE => '仅未使用的优惠券可以置为已使用',
            self::CREATE_FAILED => '创建优惠券失败',
            self::USER_COUPON_CREATE_FAILED => '创建用户优惠券失败',
            self::USED_OR_EXPIRED => '优惠券已使用或过期',
            self::ISSUED_COUPON_NOT_DELETABLE => '已有发放记录，无法删除',
            self::MEMBER_IDS_EMPTY => '没有可用的会员ID',
            self::UNAVAILABLE_FOR_ORDER => '优惠券 %d 不可用或已使用',
            self::INACTIVE_FOR_ORDER => '优惠券 %d 已失效',
            self::MINIMUM_AMOUNT_NOT_MET => '优惠券 %s 需满 %d 分可用，当前商品金额 %d 分',
        };
    }
}
