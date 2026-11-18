<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum ReviewAccessError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ORDER_FORBIDDEN = 330001;
    case ORDER_NOT_COMPLETED = 330002;
    case ITEM_NOT_FOUND = 330003;
    case DISABLED = 330004;

    public function template(): string
    {
        return match ($this) {
            self::ORDER_FORBIDDEN => '无权评价该订单',
            self::ORDER_NOT_COMPLETED => '只能对已完成的订单进行评价',
            self::ITEM_NOT_FOUND => '订单商品不存在',
            self::DISABLED => '商品评价功能未启用',
        };
    }
}
