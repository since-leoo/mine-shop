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

namespace App\Domain\Infrastructure\SystemSetting\ValueObject;

/**
 * 基础信息配置值对象.
 */
final class BasicSetting
{
    public function __construct(
        private readonly string $mallName,
        private readonly string $adminLogo,
        private readonly string $adminSmallLogo,
        private readonly string $loginLogo,
        private readonly string $miniappLogo,
        private readonly string $favicon,
        private readonly string $logo,
        private readonly string $userAgreement,
        private readonly string $privacyPolicy,
        private readonly string $supportEmail,
        private readonly string $hotline,
    ) {}

    public function mallName(): string
    {
        return $this->mallName;
    }

    public function adminLogo(): string
    {
        return $this->adminLogo;
    }

    public function adminSmallLogo(): string
    {
        return $this->adminSmallLogo;
    }

    public function loginLogo(): string
    {
        return $this->loginLogo;
    }

    public function miniappLogo(): string
    {
        return $this->miniappLogo;
    }

    public function favicon(): string
    {
        return $this->favicon;
    }

    /**
     * 通用 Logo（兼容旧版）.
     */
    public function logo(): string
    {
        return $this->logo;
    }

    public function userAgreement(): string
    {
        return $this->userAgreement;
    }

    public function privacyPolicy(): string
    {
        return $this->privacyPolicy;
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
