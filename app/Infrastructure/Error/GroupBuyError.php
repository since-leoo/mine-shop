<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum GroupBuyError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case NOT_FOUND = 230001;
    case NOT_FOUND_WITH_ID = 230002;
    case ENABLE_CONDITIONS_NOT_MET = 230003;
    case START_STATUS_INVALID = 230004;
    case ALREADY_ENDED = 230005;
    case SALES_INCREMENT_INVALID = 230006;
    case STOCK_INSUFFICIENT = 230007;
    case RELEASE_QUANTITY_INVALID = 230008;
    case TITLE_REQUIRED = 230009;
    case SKU_MISMATCH = 230010;
    case ACTIVITY_UNAVAILABLE = 230011;
    case ACTIVITY_STOCK_INSUFFICIENT = 230012;
    case MEMBER_ALREADY_JOINED = 230013;
    case GROUP_NOT_JOINABLE = 230014;
    case GROUP_ACTIVITY_MISMATCH = 230015;
    case REFUND_PENDING = 230016;
    case NOT_EDITABLE = 230017;
    case NOT_DELETABLE = 230018;
    case SINGLE_ITEM_REQUIRED = 230019;
    case COUPON_UNSUPPORTED = 230020;
    case DURATION_EXCEEDED = 230021;
    case PEOPLE_RANGE_INVALID = 230022;
    case PRICE_INVALID = 230023;
    case OFFLINE_REFUND_REQUIRES_REVIEW = 230024;
    case PRODUCT_UNAVAILABLE = 230025;
    case CREATE_FAILED = 230026;
    case UPDATE_FAILED = 230027;
    case DELETE_FAILED = 230028;
    case TOGGLE_FAILED = 230029;

    public function template(): string
    {
        return match ($this) {
            self::NOT_FOUND => '团购活动不存在',
            self::NOT_FOUND_WITH_ID => '团购活动不存在: ID=%d',
            self::ENABLE_CONDITIONS_NOT_MET => '活动不满足启用条件',
            self::START_STATUS_INVALID => '只有待开始的活动才能启动',
            self::ALREADY_ENDED => '活动已经结束',
            self::SALES_INCREMENT_INVALID => '销量增加数量必须大于0',
            self::STOCK_INSUFFICIENT => '库存不足',
            self::RELEASE_QUANTITY_INVALID => '释放数量必须大于0',
            self::TITLE_REQUIRED => '活动标题不能为空',
            self::SKU_MISMATCH => 'SKU不属于当前团购活动',
            self::ACTIVITY_UNAVAILABLE => '当前团购活动不可用',
            self::ACTIVITY_STOCK_INSUFFICIENT => '团购库存不足',
            self::MEMBER_ALREADY_JOINED => '会员已参加该活动',
            self::GROUP_NOT_JOINABLE => '无法加入该拼团',
            self::GROUP_ACTIVITY_MISMATCH => '拼团不属于当前活动或已经成团',
            self::REFUND_PENDING => '订单已有其他退款申请，等待退款完成后重试',
            self::NOT_EDITABLE => '当前活动状态不允许编辑（活动已开始或即将开始）',
            self::NOT_DELETABLE => '当前活动状态不允许删除（活动进行中有销量或即将开始）',
            self::SINGLE_ITEM_REQUIRED => '拼团订单仅支持单个商品',
            self::COUPON_UNSUPPORTED => '拼团订单不支持使用优惠券',
            self::DURATION_EXCEEDED => '活动时长不能超过30天',
            self::PEOPLE_RANGE_INVALID => '最多成团人数不能小于最少成团人数',
            self::PRICE_INVALID => '团购价必须小于原价',
            self::OFFLINE_REFUND_REQUIRES_REVIEW => '未成团订单需人工核实线下退款: %s',
            self::PRODUCT_UNAVAILABLE => '拼团商品不存在或活动已结束',
            self::CREATE_FAILED => '创建团购活动失败',
            self::UPDATE_FAILED => '更新团购活动失败',
            self::DELETE_FAILED => '删除团购活动失败',
            self::TOGGLE_FAILED => '切换状态失败',
        };
    }
}
