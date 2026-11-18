<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum SystemSettingError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case GROUP_NOT_FOUND = 180001;
    case KEY_NOT_FOUND = 180002;

    public function template(): string
    {
        return match ($this) {
            self::GROUP_NOT_FOUND => '配置分组 %s 不存在',
            self::KEY_NOT_FOUND => '系统配置 %s 不存在',
        };
    }
}
