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
 * 配送配置值对象.
 */
final class ShippingSetting
{
    /**
     * @param string[] $supportedProviders
     */
    public function __construct(
        private readonly string $defaultMethod,
        private readonly bool $enablePickup,
        private readonly string $pickupAddress,
        private readonly float $freeShippingThreshold,
        private readonly array $supportedProviders,
    ) {}

    public function defaultMethod(): string
    {
        return $this->defaultMethod;
    }

    public function enablePickup(): bool
    {
        return $this->enablePickup;
    }

    public function pickupAddress(): string
    {
        return $this->pickupAddress;
    }

    public function freeShippingThreshold(): float
    {
        return $this->freeShippingThreshold;
    }

    /**
     * @return string[]
     */
    public function supportedProviders(): array
    {
        return $this->supportedProviders;
    }

    public function isProviderSupported(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        return \in_array($code, $this->supportedProviders, true);
    }
}
