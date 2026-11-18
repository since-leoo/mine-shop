<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum LogisticsError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ORDER_TRACKING_NOT_FOUND = 195001;
    case RETURN_TRACKING_NOT_FOUND = 195002;
    case RESHIP_TRACKING_NOT_FOUND = 195003;

    public function template(): string
    {
        return match ($this) {
            self::ORDER_TRACKING_NOT_FOUND => '订单物流信息不存在',
            self::RETURN_TRACKING_NOT_FOUND => '售后退货物流信息不存在',
            self::RESHIP_TRACKING_NOT_FOUND => '售后补发物流信息不存在',
        };
    }
}
