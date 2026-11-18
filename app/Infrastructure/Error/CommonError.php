<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum CommonError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case SYSTEM_BUSY = 100001;
    case BUSINESS_REJECTED = 100002;
    case UNAUTHORIZED = 100003;
    case FORBIDDEN = 100004;
    case NOT_FOUND = 100005;
    case VALIDATION_FAILED = 100006;
    case TOKEN_EXPIRED = 100007;

    public function template(): string
    {
        return match ($this) {
            self::SYSTEM_BUSY => '系统繁忙，请稍后重试',
            self::BUSINESS_REJECTED => '操作失败',
            self::UNAUTHORIZED => '登录凭证无效，请重新登录',
            self::FORBIDDEN => '无权执行此操作',
            self::NOT_FOUND => '请求的资源不存在',
            self::VALIDATION_FAILED => '参数校验失败',
            self::TOKEN_EXPIRED => '登录已过期，请重新登录',
        };
    }
}
