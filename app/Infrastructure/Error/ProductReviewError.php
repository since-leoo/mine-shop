<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum ProductReviewError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case ORDER_ITEM_ALREADY_REVIEWED = 420001;
    case RATING_OUT_OF_RANGE = 420002;
    case CONTENT_REQUIRED = 420003;
    case REVIEW_NOT_PENDING = 420004;
    case REVIEW_NOT_FOUND = 420005;

    public function template(): string
    {
        return match ($this) {
            self::ORDER_ITEM_ALREADY_REVIEWED => '该订单商品已评价',
            self::RATING_OUT_OF_RANGE => '评分必须在1到5之间',
            self::CONTENT_REQUIRED => '评价内容不能为空',
            self::REVIEW_NOT_PENDING => '只能审核待审核状态的评价',
            self::REVIEW_NOT_FOUND => '评价不存在',
        };
    }
}
