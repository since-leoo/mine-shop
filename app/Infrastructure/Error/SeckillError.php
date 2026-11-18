<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum SeckillError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ACTIVITY_NOT_FOUND = 240001;
    case PRODUCT_NOT_FOUND = 240002;
    case SESSION_NOT_FOUND = 240003;
    case SESSION_NOT_FOUND_WITH_ID = 240004;
    case SESSION_ACTIVITY_MISMATCH = 240005;
    case ACTIVITY_NOT_CANCELLABLE = 240006;
    case ACTIVITY_START_STATUS_INVALID = 240007;
    case ACTIVITY_DISABLED = 240008;
    case ACTIVITY_ALREADY_ENDED = 240009;
    case SESSION_START_STATUS_INVALID = 240010;
    case SESSION_DISABLED = 240011;
    case SESSION_ALREADY_ENDED = 240012;
    case ACTIVITY_NOT_EDITABLE = 240013;
    case ACTIVITY_WARMUP_NOT_EDITABLE = 240014;
    case ACTIVITY_NOT_DELETABLE = 240015;
    case ACTIVITY_HAS_SESSIONS = 240016;
    case ACTIVITY_WARMUP_NOT_DELETABLE = 240017;
    case ACTIVITY_NOT_ENABLEABLE = 240018;
    case PRODUCT_ALREADY_IN_SESSION = 240019;
    case SESSION_INACCESSIBLE = 240020;
    case STARTED_SESSION_PRODUCT_IMMUTABLE = 240021;
    case WARMUP_SESSION_PRODUCT_IMMUTABLE = 240022;
    case SESSION_NOT_EDITABLE = 240023;
    case SESSION_NOT_DELETABLE = 240024;
    case SESSION_REQUIRED = 240025;
    case PRODUCT_INVALID = 240026;
    case SESSION_UNAVAILABLE = 240027;
    case PRODUCT_SESSION_MISMATCH = 240028;
    case PRODUCT_UNAVAILABLE = 240029;
    case QUANTITY_RANGE_INVALID = 240030;
    case MEMBER_LEVEL_REQUIRED = 240031;
    case PRICE_INVALID = 240032;
    case SOLD_QUANTITY_EXCEEDS_STOCK = 240033;
    case SESSION_PERIOD_INVALID = 240034;
    case STOCK_INSUFFICIENT = 240035;
    case PURCHASE_LIMIT_EXCEEDED = 240036;
    case PRODUCT_OR_ACTIVITY_UNAVAILABLE = 240037;

    public function template(): string
    {
        return match ($this) {
            self::ACTIVITY_NOT_FOUND => '活动不存在',
            self::PRODUCT_NOT_FOUND => '商品不存在',
            self::SESSION_NOT_FOUND => '场次不存在',
            self::SESSION_NOT_FOUND_WITH_ID => '场次不存在: ID=%d',
            self::SESSION_ACTIVITY_MISMATCH => '秒杀场次不属于当前活动',
            self::ACTIVITY_NOT_CANCELLABLE => '当前活动状态不允许取消',
            self::ACTIVITY_START_STATUS_INVALID => '只有待开始的活动才能启动',
            self::ACTIVITY_DISABLED => '未启用的活动不能启动',
            self::ACTIVITY_ALREADY_ENDED => '活动已经结束',
            self::SESSION_START_STATUS_INVALID => '只有待开始的场次才能启动',
            self::SESSION_DISABLED => '未启用的场次不能启动',
            self::SESSION_ALREADY_ENDED => '场次已经结束',
            self::ACTIVITY_NOT_EDITABLE => '当前活动状态不允许编辑',
            self::ACTIVITY_WARMUP_NOT_EDITABLE => '活动下有场次即将开始（30分钟内），无法编辑',
            self::ACTIVITY_NOT_DELETABLE => '当前活动状态不允许删除',
            self::ACTIVITY_HAS_SESSIONS => '该活动下还有场次，无法删除',
            self::ACTIVITY_WARMUP_NOT_DELETABLE => '活动下有场次即将开始（30分钟内），无法删除',
            self::ACTIVITY_NOT_ENABLEABLE => '当前活动状态不允许启用',
            self::PRODUCT_ALREADY_IN_SESSION => '该商品已在此场次中',
            self::SESSION_INACCESSIBLE => '场次不存在或无权访问',
            self::STARTED_SESSION_PRODUCT_IMMUTABLE => '场次已开始，无法修改商品',
            self::WARMUP_SESSION_PRODUCT_IMMUTABLE => '场次即将开始（30分钟内），无法修改商品',
            self::SESSION_NOT_EDITABLE => '当前场次状态不允许编辑（场次已开始或即将开始）',
            self::SESSION_NOT_DELETABLE => '当前场次状态不允许删除（场次已开始、有销量或即将开始）',
            self::SESSION_REQUIRED => '缺少秒杀场次信息',
            self::PRODUCT_INVALID => '商品信息非法',
            self::SESSION_UNAVAILABLE => '秒杀场次不存在或已结束',
            self::PRODUCT_SESSION_MISMATCH => '该商品不在当前秒杀场次中',
            self::PRODUCT_UNAVAILABLE => '秒杀商品库存不足或已下架',
            self::QUANTITY_RANGE_INVALID => '最小购买数量不能大于最大购买数量',
            self::MEMBER_LEVEL_REQUIRED => '要求会员等级时必须指定等级ID',
            self::PRICE_INVALID => '秒杀价不能高于原价',
            self::SOLD_QUANTITY_EXCEEDS_STOCK => '已售数量不能大于总库存',
            self::SESSION_PERIOD_INVALID => '场次开始时间必须早于结束时间',
            self::STOCK_INSUFFICIENT => '库存不足',
            self::PURCHASE_LIMIT_EXCEEDED => '超出限购数量，每人限购 %d 件',
            self::PRODUCT_OR_ACTIVITY_UNAVAILABLE => '秒杀商品不存在或活动已结束',
        };
    }
}
