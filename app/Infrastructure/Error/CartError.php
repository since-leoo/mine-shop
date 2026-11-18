<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum CartError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ITEM_NOT_FOUND = 270001;
    case PRODUCT_UNAVAILABLE = 270002;

    public function template(): string
    {
        return match ($this) {
            self::ITEM_NOT_FOUND => '购物车条目不存在',
            self::PRODUCT_UNAVAILABLE => '商品不存在或已下架',
        };
    }
}
