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
 * 内容合规配置值对象.
 */
final class ContentSetting
{
    /**
     * @param string[] $prohibitedKeywords
     */
    public function __construct(
        private readonly array $prohibitedKeywords,
        private readonly string $privacyPolicyUrl,
        private readonly string $termsUrl,
        private readonly string $complianceEmail,
    ) {}

    /**
     * @return string[]
     */
    public function prohibitedKeywords(): array
    {
        return $this->prohibitedKeywords;
    }

    public function privacyPolicyUrl(): string
    {
        return $this->privacyPolicyUrl;
    }

    public function termsUrl(): string
    {
        return $this->termsUrl;
    }

    public function complianceEmail(): string
    {
        return $this->complianceEmail;
    }
}
