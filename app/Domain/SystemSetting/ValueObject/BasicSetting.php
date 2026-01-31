<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Domain\SystemSetting\ValueObject;

/**
 * 基础信息配置值对象.
 */
final class BasicSetting
{
    public function __construct(
        private readonly string $mallName,
        private readonly string $logo,
        private readonly string $supportEmail,
        private readonly string $hotline,
    ) {}

    public function mallName(): string
    {
        return $this->mallName;
    }

    public function logo(): string
    {
        return $this->logo;
    }

    public function supportEmail(): string
    {
        return $this->supportEmail;
    }

    public function hotline(): string
    {
        return $this->hotline;
    }
}

